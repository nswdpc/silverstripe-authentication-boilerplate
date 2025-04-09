<?php

namespace NSWDPC\Authentication\Models;

use NSWDPC\Authentication\Admin\PendingProfileAdmin;
use NSWDPC\Authentication\Exceptions\VerificationFailureException;
use NSWDPC\Authentication\Services\Logger;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\CheckboxField;
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
 * @property ?string $ProvisioningData
 * @property bool $RequireAdminApproval
 * @property bool $NotifiedRequireAdminApproval
 * @property bool $IsAdminApproved
 * @property bool $RequireSelfVerification
 * @property bool $IsSelfVerified
 * @property int $VerificationsAttempted
 * @property int $VerificationsFailed
 * @property int $MemberID
 * @method \SilverStripe\Security\Member Member()
 */
class PendingProfile extends DataObject implements PermissionProvider
{
    use TOTPAware;
    use Configurable;

    /**
     * @config
     */
    private static bool $require_admin_approval = true;

    /**
     * @config
     */
    private static bool $require_self_verification = false;

    /**
     * @config
     */
    private static bool $redirect_when_pending = false;

    /**
     * @config
     */
    private static int $code_lifetime = 86400;

    /**
     * @config
     */
    private static string $digest = 'sha256';

    /**
     * @config
     */
    private static int $digits = 6;

    /**
     * @config
     */
    private static int $epoch = 0;

    /**
     * @config
     */
    private static int $verification_limit = 3;

    /**
     * @config
     */
    private static array $db = [
        'ProvisioningData' => 'Text',
        'RequireAdminApproval' => 'Boolean',
        'NotifiedRequireAdminApproval' => 'Boolean',
        'IsAdminApproved' => 'Boolean',
        'RequireSelfVerification' => 'Boolean',
        'IsSelfVerified' => 'Boolean',
        'VerificationsAttempted' => 'Int',
        'VerificationsFailed' => 'Int'
    ];

    /**
     * @config
     */
    private static array $indexes = [
        'RequireAdminApproval' => true,
        'NotifiedRequireAdminApproval' => true,
        'IsAdminApproved' => true,
        'RequireSelfVerification' => true,
        'IsSelfVerified' => true,
    ];

    /**
     * @config
     */
    private static string $table_name = 'PendingProfile';

    /**
     * @config
     */
    private static string $default_sort = 'Created DESC';

    /**
     * @config
     */
    private static array $has_one = [
        'Member' => Member::class
    ];

    /**
     * @config
     */
    private static array $summary_fields = [
        'Member.Created' => 'Created',
        'Member.LastEdited' => 'Edited',
        'Member.Title' => 'User',
        'Member.Email' => 'Email',
        'RequireAdminApproval.Nice' => 'Requires approval',
        'IsAdminApproved.Nice' => 'Approved',
        'RequireSelfVerification.Nice' => 'Requires self-verification',
        'IsSelfVerified.Nice' => 'Self-verified',
        'VerificationsAttempted' => 'Verifications attempted',
        'VerificationsFailed' => 'Verifications failed'
    ];

    /**
     * @config
     */
    private static array $defaults = [
        'RequireAdminApproval' => 0,
        'NotifiedRequireAdminApproval' => 0,
        'RequireSelfVerification' => 0,
        'IsAdminApproved' => 0,
        'IsSelfVerified' => 0,
        'VerificationsAttempted' => 0,
        'VerificationsFailed' => 0
    ];

    /*
     * Returns link to edit this dataobject in the CMS
     * Refer: https://github.com/dnadesign/silverstripe-elemental/issues/718
     */
    public function CMSEditLink()
    {
        $model_admin = PendingProfileAdmin::singleton();
        $class = str_replace('\\', '-', self::class);
        if ($this->exists()) {
            return $model_admin->Link("/{$class}/EditForm/field/{$class}/item/{$this->ID}/edit");
        } else {
            return $model_admin->Link("/{$class}/EditForm/field/{$class}/item/new");
        }
    }

    #[\Override]
    public function getTitle(): string
    {
        $title = "Pending profile";
        if ($member = $this->Member()) {
            $title .= " for " . $member->getTitle();
        } else {
            $title .= "#{$this->ID}";
        }

        return $title;
    }

    /**
     * Returns members who can approve profiles
     */
    public static function getApprovers(): SS_List
    {
        return Permission::get_members_by_permission('PENDINGPROFILE_EDIT');
    }

    /**
     * Permissions that can be assigned to groups or roles
     */
    #[\Override]
    public function providePermissions(): array
    {
        return [
            'PENDINGPROFILE_EDIT' => [
                'name' => _t(
                    self::class . '.EditPermissionLabel',
                    'Edit a pending profile'
                ),
                'category' => _t(
                    self::class . '.Category',
                    'Pending profiles'
                ),
            ],
            'PENDINGPROFILE_DELETE' => [
                'name' => _t(
                    self::class . '.DeletePermissionLabel',
                    'Delete a pending profile'
                ),
                'category' => _t(
                    self::class . '.Category',
                    'Pending profiles'
                ),
            ],
            'PENDINGPROFILE_CREATE' => [
                'name' => _t(
                    self::class . '.CreatePermissionLabel',
                    'Create a pending profile for a user'
                ),
                'category' => _t(
                    self::class . '.Category',
                    'Pending profiles'
                ),
            ]
        ];
    }


    #[\Override]
    public function canEdit($member = null)
    {
        return Permission::checkMember($member, 'PENDINGPROFILE_EDIT');
    }

    #[\Override]
    public function canView($member = null)
    {
        return $this->canEdit($member)
                || $this->canCreate($member)
                || $this->canDelete($member);
    }

    #[\Override]
    public function canCreate($member = null, $context = [])
    {
        return Permission::check('PENDINGPROFILE_CREATE');
    }

    #[\Override]
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
    public function isCompletelyVerified(): bool
    {
        if ($this->RequireAdminApproval == 0
            && $this->RequireSelfVerification == 0) {
            // neither required
            return true;

        } elseif (
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

    public function requiresPromptForSelfVerification(): bool
    {
        return $this->RequireSelfVerification == 1
                && $this->IsSelfVerified == 0;
    }

    public function requiresPromptForAdministrationApproval(): bool
    {
        return $this->RequireAdminApproval == 1
                && $this->IsAdminApproved == 0;
    }

    /**
     * Flag this profile as self-completed
     */
    public function completeSelfVerification(): void
    {
        $this->IsSelfVerified = true;
        $this->ProvisioningData = null;
        $this->write();
    }

    /**
     * Find or create a pending profile for the Member
     * @return self
     */
    public static function forMember(Member $member): ?PendingProfile
    {
        $profile = PendingProfile::get()->filter(['MemberID' => $member->ID])->first();
        return ($profile && $profile->exists() ? $profile : null);
    }

    /**
     * Create a pending profile for the Member, this is actioned by the profile owner upon registration
     */
    public static function createForMember(Member $member): PendingProfile
    {
        $profile = PendingProfile::create();
        $profile->IsAdminApproved = false;
        $profile->IsSelfVerified = false;
        // initial setup takes up project config
        $profile->RequireAdminApproval = Config::inst()->get(PendingProfile::class, 'require_admin_approval');
        $profile->RequireSelfVerification = Config::inst()->get(PendingProfile::class, 'require_self_verification');
        $profile->MemberID = $member->ID;
        $profile->write();

        self::sendAdministrationApprovalRequiredEmail($profile);

        return $profile;
    }

    /**
     * Send approval required email
     */
    private static function sendAdministrationApprovalRequiredEmail(PendingProfile $profile): bool
    {
        try {
            if ($profile->RequireAdminApproval == 1
                && !$profile->NotifiedRequireAdminApproval // and not previously notified
                && !$profile->IsAdminApproved) {
                $notifier = Injector::inst()->create(Notifier::class);
                $result = $notifier->sendAdministrationApprovalRequired($profile);
                if ($result) {
                    $profile->NotifiedRequireAdminApproval = true;
                    $profile->write();
                    return true;
                }
            }
        } catch (\Exception $exception) {
            Logger::log("Failed to send pending profile 'approval required' notifications: " . $exception->getMessage(), "WARNING");
        }

        return false;
    }

    /**
     * Find or create a pending profile for the Member
     * When created, it is created with the default rules
     * If the profile exists, an admin approval email *may* be sent if required
     */
    public static function findOrMake(Member $member): self
    {
        $profile = self::forMember($member);
        if (!$profile instanceof \NSWDPC\Authentication\Models\PendingProfile) {
            $profile = self::createForMember($member);
        } else {
            self::sendAdministrationApprovalRequiredEmail($profile);
        }

        return $profile;
    }

    /**
     * Update RequireAdminApproval and RequireSelfVerification values with currently configured settings
     */
    #[\Override]
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (empty($this->MemberID)) {
            throw \SilverStripe\ORM\ValidationException::create("Please select a user");
        }

        if ($this->exists()) {
            // check if a profile already exists from the member selected
            $member = Member::get()->byId($this->MemberID);
            if ($member) {
                $profile = self::forMember($member);
                if ($profile && $profile->ID != $this->ID) {
                    throw \SilverStripe\ORM\ValidationException::create("The user selected already has a pending profile, please edit that profile or select a different user");
                }
            }
        }

    }

    /**
     * Get a list of members that could be made pending
     */
    protected function getApplicableMembers(): DataList
    {
        // currently pending members
        $pending_members = PendingProfile::get()->column('MemberID');
        $members = Member::get()
                    ->sort('Surname ASC, Firstname ASC');
        if (!empty($pending_members)) {
            $members = $members->exclude('ID', $pending_members);
        }

        return $members;
    }

    /**
     * Create a random secret
     * @returns string
     */
    protected function generateRandomSecret(): string
    {
        $generator = new RandomGenerator();
        return $generator->randomToken('sha256');
    }

    /**
     * Create an approval code using the TOTP module, store the provisioning URI
     * The code is provided to the user who verifies it within an allowed window
     * If they fail they can request a new code
     */
    public function createApprovalCode(): string
    {
        $key = $this->getEncryptionKey();
        if ($key === '') {
            Logger::log("Someone tried to create an approval code during registration via TOTP but the system has no MFA encryption key defined", "ERROR");
            throw new VerificationFailureException(_t(self::class . '.CANNOT_COMPLETE_REGISTRATION', 'Sorry, an error occurred and this action cannot be completed at the current time. Please try again later.'));
        }

        $member = $this->Member();
        if (!$member || !$member->isInDB()) {
            Logger::log("Someone tried to create an approval code without a linked, existing, member record", "ERROR");
            throw new VerificationFailureException(_t(self::class . '.CANNOT_COMPLETE_REGISTRATION', 'Sorry, an error occurred and this action cannot be completed at the current time. Please try again later.'));
        }

        $period = $this->config()->get('code_lifetime');
        $digest = $this->config()->get('digest');
        $digits = $this->config()->get('digits');
        $epoch = $this->config()->get('epoch');
        $secret = Base32::encodeUpper($this->generateRandomSecret());

        $otp = TOTP::create($secret, $period, $digest, $digits, $epoch);
        $otp->setLabel($member->Email);

        // store the provision url for later verification
        $provisioning_uri = $otp->getProvisioningUri();

        // Store the encrypted provisioning URI, for later recreation
        $data = Injector::inst()->get(EncryptionAdapterInterface::class)->encrypt(
            $provisioning_uri,
            $key
        );

        // Initial provision/verification state
        $this->ProvisioningData = $data;
        $this->VerificationsAttempted = 0;
        $this->VerificationsFailed = 0;
        $this->write();

        return $otp->now();
    }


    /**
     * Given a code, verify it against the provisioning URI stored
     * This is called when a user wants to verify with their code
     * @param string $code the code to verify
     * @throws VerificationFailureException
     */
    public function verifySelfApprovalCode(string $code): bool
    {
        try {
            $verified = false;

            // Check if max attempts reached
            if ($this->hasMaxVerificationAttempts()) {
                throw new VerificationFailureException(
                    _t(
                        self::class . '.CANNOT_VERIFY_REACHED_LIMIT',
                        'Sorry, your account cannot be verified. You will need to request a new verification code and try again.'
                    )
                );
            }

            // increment an attempt
            $this->VerificationsAttempted += 1;

            $key = $this->getEncryptionKey();
            if ($key === '') {
                Logger::log("Profile {#$this->ID} tried to verify an approval code via TOTP but the system has no MFA encryption key defined", "ERROR");
                throw new VerificationFailureException(
                    _t(
                        self::class . '.CANNOT_VERIFY_CODE',
                        'Sorry, an error occurred and this action cannot be completed at the current time. Please try again later.'
                    )
                );
            }

            if (!$this->ProvisioningData) {
                // oops
                Logger::log("Profile {#$this->ID} tried to verify an approval code but they have no verification data", "NOTICE");
                throw new VerificationFailureException(
                    _t(
                        self::class . '.CANNOT_VERIFY_CODE_NO_PROVISIONING_DATA',
                        'Sorry, your account cannot be verified at the current time. Please try again later.'
                    )
                );
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
            }

            if (!$verified) {
                $this->VerificationsFailed += 1;
            }

            return $verified;
        } catch (VerificationFailureException $e) {
            // rethrow these exceptions
            throw new VerificationFailureException($e->getMessage());
        } catch (\Exception $e) {
            // general exception
            Logger::log("Profile {#$this->ID} verifySelfApprovalCode error=" . $e->getMessage(), "NOTICE");
            throw new VerificationFailureException(
                _t(
                    self::class . '.CANNOT_VERIFY_CODE_GENERAL_EXCEPTION',
                    'Sorry, your account cannot be verified at the current time. Please try again later.'
                )
            );
        } finally {
            // update this profile record regardless of result
            $this->write();
        }
    }

    /**
     * Return whether the maximum allowed attempts has been reached
     */
    public function hasMaxVerificationAttempts(): bool
    {
        return $this->VerificationsAttempted >= static::config()->get('verification_limit');
    }

    /**
     * Return CMS fields
     */
    #[\Override]
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
                ->setDescription($members->count() . ' users to choose from');

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
                'IsSelfVerified',
                'VerificationsAttempted',
                'VerificationsFailed'
            ]);

        } elseif ($member->exists()) {

            $memberValue = $member->getTitle();
            if ($memberEmail = $member->Email) {
                $memberValue .= " ({$memberEmail})";
            }

            $fields->removeByName([
                'RequireAdminApproval',
                'IsAdminApproved',
                'NotifiedRequireAdminApproval',
                'MemberID',
                'RequireSelfVerification',
                'IsSelfVerified',
                'VerificationsAttempted',
                'VerificationsFailed',
                'Created',
                'LastEdited'
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
                        )->performReadonlyTransformation()
                    )->setTitle('Administrator approval'),

                    CompositeField::create(
                        CheckboxField::create(
                            'IsSelfVerified',
                            'User has self-verified'
                        )->setDescription("Unchecking this box will require the owner to self-verify, provided 'Require self-verification is checked'."),
                        CheckboxField::create(
                            'RequireSelfVerification',
                            'Require self-verification',
                            $this->RequireSelfVerification == 1 ? "yes" : "no"
                        ),
                        ReadonlyField::create(
                            'VerificationsAttempted',
                            'Verifications attempted'
                        ),
                        ReadonlyField::create(
                            'VerificationsFailed',
                            'Verifications failed'
                        )
                    )->setTitle('Self verification'),

                    CompositeField::create(
                        ReadonlyField::create(
                            'MemberValue',
                            'User',
                            $memberValue
                        ),
                        ReadonlyField::create(
                            'Created',
                            'Profile created'
                        ),
                        ReadonlyField::create(
                            'LastEdited',
                            'Profile last edited'
                        )
                    )->setTitle('Details')
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
