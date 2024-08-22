<?php

namespace NSWDPC\Authentication\Extensions;

use NSWDPC\Authentication\Models\PendingProfile;
use NSWDPC\Authentication\Models\Notifier;
use NSWDPC\Authentication\Services\Logger;
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
use SilverStripe\Core\Config\Config;


/**
 * Provides profile handling extension methods and fields
 */
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
        /** @var \SilverStripe\Security\Member $owner */
        $owner = $this->getOwner();
        $profile = PendingProfile::forMember($owner);
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
        $profile = PendingProfile::forMember($this->getOwner());
        return $profile && $profile->requiresPromptForSelfVerification();
    }

    public function getProfileRequiresAdministrationApproval() {
        $profile = PendingProfile::forMember($this->getOwner());
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
            ($pendingProfile = $this->getOwner()->PendingProfile())
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
        $this->getOwner()->storeChangedFields( $this->getOwner()->getChangedFields(false, DataObject::CHANGE_VALUE) );
    }

    /**
     * When the Member is deleted, delete any linked {@link PendingProfile}
     */
    public function onBeforeDelete() {
        parent::onBeforeDelete();
        if($profile = PendingProfile::forMember($this->getOwner())) {
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
        return PendingProfile::findOrMake($this->getOwner());
    }

    /**
     * Remove a linked PendingProfile from the member
     */
    public function removePending() : bool {
        if($profile = PendingProfile::forMember($this->getOwner())) {
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
        return $this->getOwner()->sendRegistrationApprovalEmail(false, $controller);
    }

    /**
     * Send the registration approval email for this member
     * @param boolean $initial whether this is the initial prompt
     */
    public function sendRegistrationApprovalEmail($initial, Controller $controller) {
        $notifier = Notifier::create();
        return $notifier->sendSelfRegistrationToken( $this->getOwner(), $initial, $controller );
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

        $notifier = Notifier::create();

        // Handle email notification change
        if( Config::inst()->get( Notifier::class, 'notify_email_change') ) {

            // unset from later profile notification
            $emailKey = array_search('Email', $params['restrictFields']);
            if($emailKey !== false) {
                unset($params['restrictFields'][$emailKey]);
            }

            // Check email change
            if(!empty($this->changed_fields['Email'])
                && isset($this->changed_fields['Email']['before'])
                && isset($this->changed_fields['Email']['after']) ) {

                try {
                    // Send to previous
                    $notifier->sendChangeEmailNotification(
                        $this->getOwner(),
                        true, // this email is going to previous address
                        $this->changed_fields['Email']['before'],// send to this email
                        $this->changed_fields['Email']['after']
                    );
                } catch(\Exception $e) {
                    // Log
                    Logger::log("Failed to send change email notification to before email:" . $e->getMessage(), "WARNING");
                }

                try {
                    // Send to new
                    $notifier->sendChangeEmailNotification(
                        $this->getOwner(),
                        false, // this email is going to new address
                        $this->changed_fields['Email']['after'],// send to this email
                        $this->changed_fields['Email']['before']
                    );
                } catch(\Exception $e) {
                    // Log
                    Logger::log("Failed to send change email notification to after email:" . $e->getMessage(), "WARNING");
                }
            }

        }

        // Ignore 'Password' if the notify_password_change notification is active
        if($this->getOwner()->config()->get('notify_password_change')) {
            $key = array_search('Password', $params['restrictFields']);
            if($key !== false) {
                unset($params['restrictFields'][$key]);
            }
        }

        // no fields were marked as changed
        if(empty($params['restrictFields'])) {
            return null;
        }

        $fields = $this->getOwner()->getFrontEndFields($params);
        $what = new ArrayList();
        foreach($fields as $field) {
            $title = $field->Title();
            $what->push([
                'Value' => sprintf( _t('NSWDPC\Authentication.FIELD_CHANGED', '\'%s\' was updated on your profile'), $title )
            ]);
        }

        return $notifier->sendChangeNotification(
            $this->getOwner(), // about this member
            $what,// what has changed
            $this->getOwner() // send to same member
        );

    }
}
