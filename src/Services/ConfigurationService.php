<?php

namespace NSWDPC\Authentication\Services;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Security\PasswordValidator;

/**
 * Helper class to handle configuration of MFA options
 * Read the documentation for configuration instructions
 */
class ConfigurationService
{
    use Configurable;

    /**
     * @config
     */
    private static string $ss_mfa_secret_key = '';

}
