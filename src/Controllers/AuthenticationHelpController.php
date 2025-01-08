<?php

namespace NSWDPC\Authentication\Controllers;

use NSWDPC\Authentication\Models\AuthenticationHelpPage;
use SilverStripe\View\ArrayData;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * This controller exists to handle requests to the configured {@link SilverStripe\TOTP\RegisterHandler} user_help_link value
 * If the {@link AuthenticationHelpPage} exists it will redirect to that, otherwise a 404 will be shown
 */
class AuthenticationHelpController extends Controller
{
    /**
     * @config
     */
    private static array $allowed_actions = [
        'index'
    ];

    /**
     * Find the AuthenticationHelpPage and redirect to it, otherwise 404
     *
     * @return HTTPResponse
     */
    public function index(HTTPRequest $request)
    {
        // If the silverstripe/cms module exists, defer to the AuthenticationHelpPage
        $page = AuthenticationHelpPage::get()->first();
        if (!$page || !$page->exists()) {
            // return a 404
            return $this->httpError(404, _t(self::class . '.PageNotFound', 'Page not found'));
        } else {
            // redirect to this page, provided the content exists
            return $this->redirect($page->AbsoluteLink());
        }

    }
}
