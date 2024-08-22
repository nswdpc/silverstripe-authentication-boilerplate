<?php

namespace NSWDPC\Authentication\Extensions;

use NSWDPC\Authentication\Models\PendingProfile;
use NSWDPC\Authentication\Traits\PendingMemberHandler;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\InheritedPermissions;
use SilverStripe\Security\Security;
use SilverStripe\Security\Member;

/**
 * Extension for SiteTree handling when silverstripe/cms is installed
 */
class SiteTreeExtension extends Extension
{
    /**
     * Handle redirect if a the signed in Member is pending
     * @return mixed
     */
    public function contentcontrollerInit(ContentController $controller)
    {
        return $this->handlePromptForVerificationCode($controller, $this->getOwner());
    }

    /**
     * Handle pending members accessing SiteTree records
     * @param Member $member
     */
    public function canView($member): ?bool
    {
        if($this->checkPendingMemberCanView($member, $this->getOwner()) === false) {
            return false;
        }

        return null;
    }

    /**
     * Handle redirect for pending members. If turned off in configuration,
     * no redirect will occur
     */
    protected function handlePromptForVerificationCode(Controller $controller, SiteTree $record): ?\SilverStripe\Control\HTTPResponse
    {
        if(!PendingProfile::config()->get('redirect_when_pending')) {
            return null;
        }

        $member = Security::getCurrentUser();
        if($member && $member->getIsPending() && !$this->hasAnyoneViewPermission($record)) {
            $links = $member->extend('promptForVerificationCodeLink');
            if(is_array($links)) {
                $redirectLink = array_pop($links);
                if($redirectLink) {
                    return $controller->redirect($redirectLink);
                }
            }
        }

        return null;
    }

    /**
     * Check if the member is pending and can/cannot view
     * For pending members, records without ANYONE permission cannot be viewed
     */
    protected function checkPendingMemberCanView(?Member $member, SiteTree $record): bool
    {
        if($member && $member->getIsPending() && !$this->hasAnyoneViewPermission($record)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Determine whether a SiteTree record can be viewed by anyone, taking into
     * account site access settings and parent settings
     */
    protected function hasAnyoneViewPermission(SiteTree $record): bool
    {
        if($record->CanViewType === InheritedPermissions::ANYONE) {
            // this record sets permissions
            return true;
        } elseif ($record->CanViewType === InheritedPermissions::INHERIT) {
            // inheriting from parent or site config
            if(($parent = $record->Parent()) && $parent->exists()) {
                // record has parent
                \PHPStan\dumpType($parent);
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
