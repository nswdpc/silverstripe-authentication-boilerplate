<?php

namespace NSWDPC\Authentication;

use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\LabelField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLVarchar;
use SilverStripe\Control\Controller;

class ProfileExtension extends DataExtension {

    /**
     * @var array
     */
    private $changed_fields = [];

    /**
     * @var array
     * @config
     */
    private static $belongs_to = [
        'PendingProfile' => PendingProfile::class
    ];

    /**
     * Determine if Member is considered pending
     * In that a PendingProfile exists and it requires approval of some sort
     * @return boolean
     */
    public function getIsPending() : bool {
        $profile = PendingProfile::forMember($this->owner);
        return $profile
            && $profile->exists()
            && ($profile->requiresPromptForSelfVerification()
                || $profile->requiresPromptForAdministrationApproval());
    }

    /**
     * Returns whether the profile is pending or not based on IsPending value
     * and existence of profile with specific values
     * @return boolean
     */
    public function IsProfilePending() {
        return $this->getIsPending();
    }

    public function getProfileRequiresSelfVerification() {
        $profile = PendingProfile::forMember($this->owner);
        return $profile && $profile->requiresPromptForSelfVerification();
    }

    public function getProfileRequiresAdministrationApproval() {
        $profile = PendingProfile::forMember($this->owner);
        return $profile && $profile->requiresPromptForAdministrationApproval();
    }

    /**
     * For use in fields
     */
    public function IsProfilePendingNice() {
        return $this->IsProfilePending() ?
            _t('NSWDPC\Members.PENDING_YES', 'Yes')
            : _t('NSWDPC\Members.PENDING_YES', 'No');
    }

    /**
     * Update summary data for gridfield tables
     */
    public function updateSummaryFields(&$fields) {
        $fields = array_merge($fields, [
            'IsProfilePendingNice' => _t('NSWDPC\Authentication.IS_PENDING', "Pending")
        ]);
    }

    /**
     * Update CMS fields for the member
     */
    public function updateCMSFields(FieldList $fields)
    {

        if(
            ($pendingProfile = $this->owner->PendingProfile())
            && $pendingProfile->exists()
        ) {

            $link = $pendingProfile->CMSEditLink();
            $value = _t(
                PendingProfile::class . ".MEMBER_HAS_PENDING_PROFILE",
                "View this member's pending profile."
            );
            $title = _t(
                PendingProfile::class . ".PENDING",
                "Pending profile"
            );

            $fields->addFieldToTab(
                "Root.Profile",
                CompositeField::create(
                    LiteralField::create(
                        "HasPendingProfile",
                        DBField::create_field(
                            DBHTMLVarchar::class,
                            "<p class=\"message warning\"><a href=\"{$link}\">{$value}</a></p>"
                        )
                    )
                )->setTitle($title)
            );

        }
    }

    /**
     * Take action prior to Member write()
     */
    public function onBeforeWrite()
    {
        // Store field that were changed while writing
        $this->owner->storeChangedFields( $this->owner->getChangedFields(false, DataObject::CHANGE_VALUE) );
    }

    /**
     * When the Member is deleted, delete any linked {@link PendingProfile}
     */
    public function onBeforeDelete() {
        parent::onBeforeDelete();
        if($profile = PendingProfile::forMember($this->owner)) {
            $profile->delete();
        }
    }

    /**
     * Store for later use
     * @param array $fields
     */
    public function storeChangedFields($fields) {
        $this->changed_fields = $fields;
    }

    /**
     * Mark a user as pending
     * This can create a PendingProfile record based on configuration
     * @return PendingProfile
     */
    public function makePending($initial = false) : ?PendingProfile {
        return PendingProfile::findOrMake($this->owner);
    }

    /**
     * Remove a linked PendingProfile from the member
     */
    public function removePending() : bool {
        if($profile = PendingProfile::forMember($this->owner)) {
            $profile->delete();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Handle reprompt of a user requiring them to enter a new activation code
     * @return boolean
     */
    public function rePromptForActivationCode(Controller $controller) {
        return $this->owner->sendRegistrationApprovalEmail(false, $controller);
    }

    /**
     * Send the registration approval email for this member
     * @param boolean $initial whether this is the initial prompt
     */
    public function sendRegistrationApprovalEmail($initial, Controller $controller) {
        $notifier = Notifier::create();
        return $notifier->sendSelfRegistrationToken( $this->owner, $initial, $controller );
    }

    /**
     * Notify the current member of changes to their profile
     * @param array $changes - if empty the changed fields stored in {@link self::onBeforeWrite()} are used
     * @return boolean|null
     */
    public function notifyProfileChange($changes = []) {
        if(empty($changes)) {
            // Automated changes
            if(empty($this->changed_fields) || !is_array($this->changed_fields)) {
                return null;
            }
            $params = [
                'restrictFields' => array_keys($this->changed_fields)
            ];
        } else {
            // Manual changes
            $params = [
                // restrict on changes
                'restrictFields' => $changes
            ];
        }

        // Ignore 'Password' if the notify_password_change notification is active
        if($this->owner->config()->get('notify_password_change')) {
            $key = array_search('Password', $params['restrictFields']);
            if($key !== false) {
                unset($params['restrictFields'][$key]);
            }
        }

        // no fields were marked as changed
        if(empty($params['restrictFields'])) {
            return null;
        }

        $fields = $this->owner->getFrontEndFields($params);
        $what = new ArrayList();
        foreach($fields as $field) {
            $title = $field->Title();
            $what->push([
                'Value' => sprintf( _t('NSWDPC\Authentication.FIELD_CHANGED', '\'%s\' was updated on your profile'), $title )
            ]);
        }
        $notifier = Notifier::create();
        return $notifier->sendChangeNotification(
            $this->owner, // about this member
            $what,// what has changed
            $this->owner // send to same member
        );

    }
}
