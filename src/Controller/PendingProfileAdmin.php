<?php

namespace NSWDPC\Authentication;

use SilverStripe\Reports\Report;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Security\Permission;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataList;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\TextField;
use SilverStripe\Core\Convert;
use SilverStripe\Admin\ModelAdmin;

/**
 * Pending profile admin, to  allow creation and management of pending profiles
 * @author James <james.ellis@dpc.nsw.gov.au>
 */
class PendingProfileAdmin extends ModelAdmin
{

    private static $managed_models = [
        PendingProfile::class
    ];

    private static $menu_icon_class = 'font-icon-torsos-all';
    private static $url_segment = 'pending-profiles';
    private static $menu_title = 'Pending Profiles';

}
