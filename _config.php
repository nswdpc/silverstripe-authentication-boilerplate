<?php
use SilverStripe\Core\Environment;
use SilverStripe\Core\Config\Config;
use NSWDPC\MFA\ConfigurationService;

Environment::setEnv('SS_MFA_SECRET_KEY', Config::inst()->get(ConfigurationService::class, 'ss_mfa_secret_key'));
