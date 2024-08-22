<?php

namespace NSWDPC\Authentication\Admin;

use NSWDPC\Authentication\Models\PendingProfile;
use SilverStripe\Reports\Report;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Security\Permission;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataList;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Core\Convert;
use SilverStripe\Admin\ModelAdmin;

/**
 * Pending profile admin, to  allow creation and management of pending profiles
 */
class PendingProfileAdmin extends ModelAdmin
{

    /**
     * @var array
     * @config
     */
    private static $managed_models = [
        PendingProfile::class
    ];

    /**
     * @var string
     * @config
     */
    private static $menu_icon_class = 'font-icon-torsos-all';

    /**
     * @var string
     * @config
     */
    private static $url_segment = 'pending-profiles';

    /**
     * @var string
     * @config
     */
    private static $menu_title = 'Pending Profiles';

    public function getEditForm($id = null, $fields = null) {
        $form = parent::getEditForm($id, $fields);
        if($this->modelClass == PendingProfile::class) {
            $gf = $form->Fields()->fieldByName($this->sanitiseClassName($this->modelClass));
            if($gf instanceof GridField) {
                $config = $gf->getConfig();
                $field = $config->getComponentByType(GridFieldDetailForm::class);
                $field->setItemRequestClass(PendingProfile_ItemRequest::class);
            }
        }
        return $form;
    }
}
