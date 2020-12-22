<?php

namespace NSWDPC\Authentication;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\FieldList;
use Silverstripe\Control\Controller;

class ProfileExtension extends DataExtension {

    private $changed_fields = [];

    private static $db = [
        'IsPending' => 'Boolean',
    ];

    private static $belongs_to = [
        'PendingProfile' => PendingProfile::class
    ];

    /**
     * Handle calls to IsPending
     * @return boolean
     */
    public function getIsPending() {
        return $this->IsProfilePending();
    }

    //TODO whenever IsPending is set to 1, create a Pending Profile with default values

    /**
     * Returns whether the profile is pending or not based on IsPending value
     * and existence of profile with specific values
     * @return boolean
     */
    public function IsProfilePending() {
        $profile = PendingProfile::forMember($this->owner);
        return $this->owner->getField('IsPending') == 1
            && $profile
            && $profile->exists()
            && ($profile->requiresPromptForSelfVerification()
                || $profile->requiresPromptForAdministrationApproval());
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
        $fields->removeByName('IsPending');
        $fields->addFieldToTab(
            "Root.Profile",
            ReadonlyField::create(
                'IsPending',
                _t('NSWDPC\Authentication.PENDING_QUESTION', "Pending?"),
                $this->IsProfilePendingNice()
            )->setDescription( _t('NSWDPC\Authentication.VIEW_PENDING_PROFILES_ADMIN', "View the 'Pending Profiles' area to create and manage pending profiles") )
        );
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
        $profile = PendingProfile::forMember($this->owner);
        if($profile) {
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
     * This creates a PendingProfile record
     * @return PendingProfile
     */
    public function makePending($initial = false) {
        $profile = PendingProfile::findOrMake($this->owner);
        return $profile;
    }

    /**
     * Remove pending flags and profile from a Member
     */
    public function removePending() {
        $profile = PendingProfile::forMember($this->owner);
        if($profile) {
            $profile->delete();
        }
        $this->owner->IsPending = 0;
        $this->owner->write();
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
     * @return void
     */
    public function sendRegistrationApprovalEmail($initial = false, Controller $controller) {
        $notifier = Notifier::create();
        return $notifier->sendSelfRegistrationToken( $this->owner, $initial, $controller );
    }

    /**
     * Notify the current member of changes to their profile
     * @param array $changes - if empty the changed fields stored in {@link self::onBeforeWrite()} are used
     * @return boolean
     */
    public function notifyProfileChange($changes = []) {
        if(empty($changes)) {
            // Automated changes
            if(empty($this->changed_fields) || !is_array($this->changed_fields)) {
                return;
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
