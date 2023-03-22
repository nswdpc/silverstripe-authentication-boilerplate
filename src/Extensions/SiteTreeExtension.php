<?php

namespace NSWDPC\Authentication;

use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Member;

/**
 * Extension for SiteTree handling when silverstripe/cms is installed
 * @author James
 */
class SiteTreeExtension extends Extension {

    use PendingMemberHandler;

    /**
     * Handle redirect if a the signed in Member is pending
     * @param ContentController $controller
     * @return mixed
     */
    public function contentcontrollerInit($controller) {
        return $this->handlePromptForVerificationCode($controller, $this->owner);
    }

    /**
     * Handle pending members accessing SiteTree records
     * @return mixed
     * @param Member $member
     */
    public function canView($member) {
        if($this->checkPendingMemberCanView($member, $this->owner) === false) {
            return false;
        }
        return null;
    }

}
