<?php

namespace NSWDPC\MFA;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\View\ArrayData;
use SilverStripe\Core\Extension;
use Silverstripe\SiteConfig\SiteConfig;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use Page;

/**
 * A page to display and handle requests about MFA help
 * See docs/en/index.md for details
 * @author James <james.ellis@dpc.nsw.gov.au>
 */
class AuthenticationHelpPage extends Page {

    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'AuthenticationHelpPage';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'AuthenticationHelpHeading' => 'Varchar(255)',
        'AuthenticationHelpContent' => 'HTMLText',
        'AuthenticationHelpShowAbove' => 'Boolean'
    ];

    /**
     * Singular name for CMS
     * @var string
     */
    private static $singular_name = 'Authentication Help Page';
    /**
     * Plural name for CMS
     * @var string
     */
    private static $plural_name = 'Authentication Help Pages';

    public function requireDefaultRecords() {
        parent::requireDefaultRecords();

        $page = AuthenticationHelpPage::get()->first();
        if($page && $page->exists()) {
            return;
        }

        // grab some content for writing
        $data = new ArrayData([
            'MFARequired' => $this->MFARequired(),
            'MFAGracePeriodExpires' => $this->MFAGracePeriodExpires(),
        ]);
        $content = $data->renderWith('NSWDPC/MFA/DefaultHTMLContent');

        // create a default page, save the templated content but do not publish
        $page = AuthenticationHelpPage::create();
        $page->Title = _t('NSWDPC_MFA.PageTitle', 'Multi-Factor Authentication');
        $page->AuthenticationHelpHeading = _t('NSWDPC_MFA.SubHeading', 'Setting up MFA');
        $page->AuthenticationHelpContent = trim($content);
        $page->write();
    }

    /**
     * Settings Tab Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab(
            'Root.MultiFactorAuthentication',
            [
                TextField::create(
                    'AuthenticationHelpHeading',
                    _t('NSWDPC_MFA.AuthenticationHelpHeading', 'Heading')
                ),
                HTMLEditorField::create(
                    'AuthenticationHelpContent',
                    _t('NSWDPC_MFA.AuthenticationHelpContent', 'Content')
                ),
                CheckboxField::create(
                    'AuthenticationHelpShowAbove',
                    'Show this content above page content'
                )
            ]
        );
        return $fields;
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->AuthenticationHelpContent = trim($this->AuthenticationHelpContent);
    }

    public function MFARequired() {
        $config = SiteConfig::current_site_config();
        return $config->MFARequired;
    }

    public function MFAGracePeriodExpires() {
        $config = SiteConfig::current_site_config();
        return $config->MFAGracePeriodExpires;
    }

}
