<?php

namespace NSWDPC\Authentication\Extensions;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Member;

/**
 * File handling
 */
class AssetExtension extends Extension
{
    /**
     * Handle pending members accessing files
     * @param Member|null $member
     * @return mixed
     */
    public function canView($member)
    {
        if($this->owner instanceof Folder) {
            return null;
        }
        if($member && $member->getIsPending()) {
            return false;
        }
        return null;
    }

}
