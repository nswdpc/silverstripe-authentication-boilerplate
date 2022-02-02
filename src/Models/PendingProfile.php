<?php

namespace NSWDPC\Authentication;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Permission;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\ValidationException;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use OTPHP\Factory as TOTPFactory;
use SilverStripe\TOTP\TOTPAware;
use SilverStripe\MFA\Service\EncryptionAdapterInterface;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Security\RandomGenerator;
use SilverStripe\ORM\FieldType\DBField;

/**
 * Pending profile model for registered members
 * When a member registers, they have a record created with relevant flags based on configuration
 * A model admin exists to allow certain administration members control over these pending profiles
 * When a member is verified, their record is deleted
 * @author James <james.ellis@dpc.nsw.gov.au>
 */
class PendingProfile extends DataObject implements PermissionProvider
{
    use TOTPAware;
    use Configurable;

    private static $require_admin_approval = true;
    private static $require_self_verification = false;
    private static $code_lifetime = 86400;
    private static $digest = 'sha256';
    private static $digits = 6;
    private static $epoch = 0;

    private static $db = [
        'ProvisioningData' => 'Text',
        'RequireAdminApproval' => 'Boolean',
        'NotifiedRequireAdminApproval' => 'Boolean',
        'IsAdminApproved' => 'Boolean',
        'RequireSelfVerification' => 'Boolean',
        'IsSelfVerified' => 'Boolean',
    ];

    private static $table_name = 'PendingProfile';

    private static $default_sort = 'Created DESC';

    private static $has_one = [
        'Member' => Member::class
    ];

    private static $summary_fields = [
        'Member.Created' => 'Created',
        'Member.LastEdited' => 'Edited',
        'Member.Title' => 'User',
        'Member.Email' => 'Email',
        'RequireAdminApproval.Nice' => 'Requires approval',
        'IsAdminApproved.Nice' => 'Approved',
        'RequireSelfVerification.Nice' => 'Requires self-verification',
        'IsSelfVerified.Nice' => 'Self-verified'
    ];

    private static $defaults = [
        'RequireAdminApproval' => 0,
        'NotifiedRequireAdminApproval' => 0,
        'RequireSelfVerification' => 0,
        'IsAdminApproved' => 0,
        'IsSelfVerified' => 0
    ];

    /*
     * Returns link to edit this dataobject in the CMS
     * Refer: https://github.com/dnadesign/silverstripe-elemental/issues/718
     */
    public function CMSEditLink()
    {
        $model_admin = PendingProfileAdmin::singleton();
        $class = str_replace('\\', '-', self::class);
        return $model_admin->Link("/{$class}/EditForm/field/{$class}/item/{$this->ID}/edit");
    }

    public function getTitle() {
        $title = "Pending profile";
        if($member = $this->Member()) {
            $title .= " for " . $member->getTitle();
        } else {
            $title .= "#{$this->ID}";
        }
        return $title;
    }

    /**
     * Returns members who can approve profiles
     * @return DataList
     */
    public static function getApprovers() : SS_List {
        $members = Permission::get_members_by_permission('PENDINGPROFILE_EDIT');
        return $members;
    }

    /**
     * Permissions that can be assigned to groups or roles
     * @returns array
     */
    public function providePermissions()
    {
        return [
            'PENDINGPROFILE_EDIT' => [
                'name' => _t(
                    __CLASS__ . '.EditPermissionLabel',
                    'Edit a pending profile'
                ),
                'category' => _t(
                    __CLASS__ . '.Category',
                    'Pending profiles'
                ),
            ],
            'PENDINGPROFILE_DELETE' => [
                'name' => _t(
                    __CLASS__ . '.DeletePermissionLabel',
                    'Delete a pending profile'
                ),
                'category' => _t(
                    __CLASS__ . '.Category',
                    'Pending profiles'
                ),
            ],
            'PENDINGPROFILE_CREATE' => [
                'name' => _t(
                    __CLASS__ . '.CreatePermissionLabel',
                    'Create a pending profile for a user'
                ),
                'category' => _t(
                    __CLASS__ . '.Category',
                    'Pending profiles'
                ),
            ]
        ];
    }


    public function canEdit($member = null)
    {
        return Permission::checkMember($member, 'PENDINGPROFILE_EDIT');
    }

    public function canView($member = null)
    {
        return $this->canEdit($member)
                || $this->canCreate($member)
                || $this->canDelete($member);
    }

    public function canCreate($member = null, $context = [])
    {
        $can = Permission::check('PENDINGPROFILE_CREATE');
        return $can;
    }

    public function canDelete($member = null)
    {
        return Permission::check('PENDINGPROFILE_DELETE');
    }

    /*
     * Return whether this record is marked as verified
     * @todo mark as RequireAdminApproval if in configuration
     * @todo set up user self approval as required if in configuration
     * There are 4 options:
     *  1. user requires self approval AND admin approval
     *  2. user requires self approval
     *  2. user requires admin approval
     *  4. user requires no approval (trusted system)
     */
    public function isCompletelyVerified()
    {
        if ($this->RequireAdminApproval == 0
            && $this->RequireSelfVerification == 0) {
            // neither required
            return true;

        } else if(
            ($this->RequireAdminApproval == 1 && $this->IsAdminApproved == 0)
            ||
            ($this->RequireSelfVerification == 1 && $this->IsSelfVerified == 0)
            ) {
            // one of the verifications is missing
            return false;
        } elseif ($this->RequireAdminApproval == 1
            && $this->RequireSelfVerification == 1
            && $this->IsAdminApproved == 1
            && $this->IsSelfVerified == 1) {
            // requires both and both completed
            return true;
        } elseif ($this->RequireAdminApproval == 1
            && $this->IsAdminApproved == 1) {
            // only admin approval required
            return true;
        } elseif ($this->RequireSelfVerification == 1
            && $this->IsSelfVerified == 1) {
            // only self verification required
            return true;
        } else {
            return false;
        }
    }

    public function requiresPromptForSelfVerification()
    {
        return $this->RequireSelfVerification == 1
                && $this->IsSelfVerified == 0;
    }

    public function requiresPromptForAdministrationApproval()
    {
        return $this->RequireAdminApproval == 1
                && $this->IsAdminApproved == 0;
    }

    public function completeSelfVerification()
    {
        $this->IsSelfVerified = 1;
        $this->write();
    }

    /**
     * Find or create a pending profile for the Member
     * @return self
     */
    public static function forMember(Member $member)
    {
        $profile = PendingProfile::get()->filter(['MemberID'=>$member->ID])->first();
        return $profile;
    }

    /**
     * Create a pending profile for the Member, this is actioned by the profile owner upon registration
     * @return self
     */
    private static function createForMember(Member $member)
    {
        $profile = PendingProfile::create();
        $profile->IsAdminApproved = 0;
        $profile->IsSelfVerified = 0;
        // initial setup takes up project config
        $profile->RequireAdminApproval = Config::inst()->get(PendingProfile::class, 'require_admin_approval');
        $profile->RequireSelfVerification = Config::inst()->get(PendingProfile::class, 'require_self_verification');
        $profile->MemberID = $member->ID;
        $profile->write();

        self::sendAdministrationApprovalRequiredEmail($profile);

        return $profile;
    }

    /**
     * @returns mixed
     */
    private static function sendAdministrationApprovalRequiredEmail(PendingProfile $profile) {
        try {
            if($profile->RequireAdminApproval == 1
                && !$profile->NotifiedRequireAdminApproval // and not previously notified
                && !$profile->IsAdminApproved) {
                $notifier = Injector::inst()->create(Notifier::class);
                $result = $notifier->sendAdministrationApprovalRequired($profile);
                if($result) {
                    $profile->NotifiedRequireAdminApproval = 1;
                    $profile->write();
                }
            }
        } catch (\Exception $e) {
            Logger::log("Failed to send pending profile 'approval required' notifications: " . $e->getMessage(), "WARNING");
        }
        return false;
    }

    /**
     * Find or create a pending profile for the Member
     * @return self
     */
    public static function findOrMake(Member $member)
    {
        $profile = self::forMember($member);
        if (!$profile) {
            $profile = self::createForMember($member);
            // when a profile is created, the member becomes pending
            $member->IsPending = 1;
            $member->write();
        } else {
            self::sendAdministrationApprovalRequiredEmail($profile);
        }
        return $profile;
    }

    /**
     * Update RequireAdminApproval and RequireSelfVerification values with currently configured settings
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if(empty($this->MemberID )) {
            throw new ValidationException("Please select a user");
        }

        if($this->exists()) {
            // check if a profile already exists from the member selected
            $member = Member::get()->byId( $this->MemberID );
            if($member) {
                $profile = self::forMember($member);
                if($profile && $profile->ID != $this->ID) {
                    throw new ValidationException("The user selected already has a pending profile, please edit that profile or select a different user");
                }
            }
        }

    }

    /**
     * Actions to run after record is written
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        $member = $this->Member();
        if ($member
            && $member->IsPending == 0
            && ($this->requiresPromptForSelfVerification()
                || $this->requiresPromptForAdministrationApproval())) {
            // Ensure member record is correctly updated based on profile settings
            $member->IsPending = 1;
            $member->write();
        }

    }

    /**
     * Get a list of members that could be made pending
     * @return DataList
     */
    protected function getApplicableMembers()
    {
        // currently pending members
        $pending_members = PendingProfile::get()->column('MemberID');
        $members = Member::get()
                    ->sort('Surname ASC, Firstname ASC');
        if(!empty($pending_members)) {
            $members = $members->exclude('ID', $pending_members);
        }
        return $members;
    }

    /**
     * Create a random secret
     * @returns string
     */
    protected function generateRandomSecret()
    {
        $generator = new RandomGenerator();
        return $generator->randomToken('sha256');
    }

    /**
     * Create an approval code using the TOTP module, store the provisioning URI
     * The code is provided to the user who verifies it within an allowed window
     * If they fail they can request a new code
     * @return int
     */
    public function createApprovalCode()
    {
        $key = $this->getEncryptionKey();
        if (empty($key)) {
            Logger::log("Someone tried to create an approval code during registration via TOTP but the system has no MFA encryption key defined", "ERROR");
            throw new ValidationException( _t('auth.CANNOT_COMPLETE_REGISTRATION', 'Sorry, an error occurred and this action cannot be completed at the current time. Please try again later.') );
        }

        $period = $this->config()->get('code_lifetime');
        $digest = $this->config()->get('digest');
        $digits = $this->config()->get('digits');
        $epoch = $this->config()->get('epoch');
        $secret = Base32::encodeUpper($this->generateRandomSecret());

        $otp = TOTP::create($secret, $period, $digest, $digits, $epoch);
        $otp->setLabel($this->Member()->Email);

        // store the provision url for later verification
        $provisioning_uri = $otp->getProvisioningUri();

        // Store the encrypted provisioning URI, for later recreation
        $data = Injector::inst()->get(EncryptionAdapterInterface::class)->encrypt(
            $provisioning_uri,
            $key
        );

        $this->ProvisioningData = $data;
        $this->write();

        return $otp->now();
    }


    /**
     * Given a code, verify it against the provisioning URI stored
     * This is called when a user wants to verify with their code
     * @param string $code
     * @return boolean
     */
    public function verifySelfApprovalCode(string $code)
    {
        $key = $this->getEncryptionKey();
        if (empty($key)) {

            Logger::log("Someone tried to verify an approval code via TOTP but the system has no MFA encryption key defined", "ERROR");
            throw new ValidationException( _t('auth.CANNOT_VERIFY_CODE', 'Sorry, an error occurred and this action cannot be completed at the current time. Please try again later.') );

            throw new ValidationException('No encryption key defined');
        }

        if(!$this->ProvisioningData) {
            // oops
            throw new ValidationException('Cannot verify you at this time');
        }

        // get the provisioning uri from the profile's data
        $provisioning_uri = Injector::inst()->get(EncryptionAdapterInterface::class)->decrypt(
            $this->ProvisioningData,
            $key
        );
        $verified = false;
        if ($provisioning_uri) {
            $window = 1;//TODO configurable
            $otp = TOTPFactory::loadFromProvisioningUri($provisioning_uri);
            $verified = $otp->verify($code, time(), $window);
            if ($verified) {
                // if they are verified, the data is no longer needed
                $this->ProvisioningData = null;
                $this->write();
            }
        }
        return $verified;
    }

    /**
     * Return CMS fields
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('ProvisioningData');
        $member = $this->Member();
        if (!$this->exists()) {

            // new record, select a member to apply this pending profile to
            $members = $this->getApplicableMembers();
            $field = DropdownField::create(
                'MemberID',
                'User',
                $members->map('ID', 'Email')->toArray()
            )->setHasEmptyDefault(true)
                ->setDescription( $members->count() . ' users to choose from');

            $fields->addFieldsToTab(
                'Root.Main',
                [
                    $field,
                    CheckboxField::create(
                        'RequireAdminApproval',
                        'Require administration approval',
                        $this->config()->get('require_admin_approval') ? 1 : 0
                    ),
                    CheckboxField::create(
                        'RequireSelfVerification',
                        'Require self verification',
                        $this->config()->get('require_self_verification') ? 1 : 0
                    ),
                ]
            );

            $fields->removeByName([
                'IsAdminApproved',
                'NotifiedRequireAdminApproval',
                'IsSelfVerified'
            ]);

        } elseif ($member->exists()) {

            $fields->removeByName([
                'RequireAdminApproval',
                'IsAdminApproved',
                'NotifiedRequireAdminApproval',
                'MemberID',
                'RequireSelfVerification',
                'IsSelfVerified'
            ]);

            $fields->addFieldsToTab(
                'Root.Main',
                [
                    LiteralField::create(
                        'ApprovalNotice',
                        '<p class="message notice">'
                        . ($this->IsAdminApproved == 0 ?
                            "Please review this profile prior to approving it." :
                            "Please review this profile prior to unapproving it.")
                        . '</p>'
                    ),

                    CompositeField::create(
                        HeaderField::create(
                            'AdminApprovalHeader',
                            'Administrator approval'
                        ),
                        CheckboxField::create(
                            'IsAdminApproved',
                            'Approved'
                        )->performReadonlyTransformation(),

                        CheckboxField::create(
                            'RequireAdminApproval',
                            'Require administration approval'
                        ),

                        CheckboxField::create(
                            'NotifiedRequireAdminApproval',
                            'Notification to approvers was sent'
                        )->performReadonlyTransformation(),
                    ),

                    CompositeField::create(
                        HeaderField::create(
                            'SelfApprovalHeader',
                            'Self verification'
                        ),
                        CheckboxField::create(
                            'IsSelfVerified',
                            'User has self-verified'
                        )->setDescription('Unchecking this box will require the owner to self-verify, provided \'Require self-verification is checked\'.'),

                        CheckboxField::create(
                            'RequireSelfVerification',
                            'Require self verification',
                            $this->RequireSelfVerification == 1 ? "yes" : "no"
                        )
                    ),

                    ReadonlyField::create(
                        'MemberValue',
                        'User',
                        $member->getTitle() . " " . $member->Email
                    ),
                    ReadonlyField::create(
                        'Created',
                        'Profile created'
                    ),
                    ReadonlyField::create(
                        'LastEdited',
                        'Profile last edited'
                    )
                ]
            );
        } else {
            // member doesn't exist, record can safely be deleted
            $fields->removeByName([
                'RequireAdminApproval',
                'IsAdminApproved',
                'MemberID',
                'RequireSelfVerification',
                'IsSelfVerified'
            ]);

            $fields->addFieldToTab(
                'Root.Main',
                LiteralField::create(
                    'MemberDeleted',
                    '<p class="message info">The user linked related to this pending profile record is no longer available</p>'
                )
            );
        }

        return $fields;
    }

}
