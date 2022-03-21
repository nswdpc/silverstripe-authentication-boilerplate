<?php

namespace NSWDPC\Authentication;

use SilverStripe\Assets\File;
use SilverStripe\Core\Extension;

/**
 * File handling
 * @author James
 */
class AssetExtension extends Extension {

    /**
     * Pending members cannot view files
     * @param Member $member
     */
    public function canView($member) {
        if($member && $member->getIsPending()) {
            return false;
        }
        return null;
    }

}
