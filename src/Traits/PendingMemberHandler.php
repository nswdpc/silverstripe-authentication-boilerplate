<?php

namespace NSWDPC\Authentication;

use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\InheritedPermissions;
use SilverStripe\Security\Security;
use SilverStripe\Security\Member;

/**
 * Common pending member handling
 * {@link \NSWDPC\Authentication\SiteTreeExtension}
 * @author James
 */
trait PendingMemberHandler {

    /**
     * Handle redirect for pending members. If turned off in configuration,
     * no redirect will occur
     *
     * @return mixed
     */
    protected function handlePromptForVerificationCode(Controller $controller, DataObject $record) {
        if(!PendingProfile::config()->get('redirect_when_pending')) {
            return null;
        }
        $member = Security::getCurrentUser();
        if($member && $member->getIsPending() && !$this->hasAnyoneViewPermission($record)) {
            $links = $member->extend('promptForVerificationCodeLink');
            if(is_array($links)) {
                $redirectLink = array_pop($links);
                if($redirectLink) {
                    return $controller->redirect( $redirectLink );
                }
            }
        }
        return null;
    }

    /**
     * Check if the member is pending and can/cannot view
     * For pending members, records without ANYONE permission cannot be viewed
     * @return bool
     */
    protected function checkPendingMemberCanView(?Member $member, DataObject $record) : bool {
        if($member && $member->getIsPending() && !$this->hasAnyoneViewPermission($record)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Determine whether a SiteTree record can be viewed by anyone, taking into
     * account site access settings and parent settings
     * @return bool
     */
    protected function hasAnyoneViewPermission(DataObject $record) : bool {
        if($record->CanViewType === InheritedPermissions::ANYONE) {
            // this record sets permissions
            return true;
        } else if ($record->CanViewType === InheritedPermissions::INHERIT) {
            // inheriting from parent or site config
            if( ($parent = $record->Parent()) && $parent->exists() ) {
                // record has parent
                return $this->hasAnyoneViewPermission($parent);
            } else {
                // inherit from site config
                $siteConfig = $record->getSiteConfig();
                return $siteConfig && $siteConfig->CanViewType === InheritedPermissions::ANYONE;
            }
        } else {
            // not
            return false;
        }
    }

}
