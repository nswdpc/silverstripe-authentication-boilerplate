<?php

namespace NSWDPC\Authentication\Extensions;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Member;

/**
 * File handling
 * @extends \SilverStripe\Core\Extension<(\SilverStripe\Assets\File & static)>
 */
class AssetExtension extends Extension
{
    /**
     * Handle pending members accessing files
     * @param Member|null $member
     */
    public function canView($member): ?bool
    {
        if ($this->getOwner() instanceof Folder) {
            return null;
        }

        if ($member && $member->getIsPending()) {
            return false;
        }

        return null;
    }

}
