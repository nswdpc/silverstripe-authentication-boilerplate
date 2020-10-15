<?php

namespace NSWDPC\Passwords;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Security\Member;

/**
 * Checks if a password provided is within a list of words based on site context / member details, based on configuration
 * @see 5.1.1.2 Memorized Secret Verifiers
 * @author James <james.ellis@dpc.nsw.gov.au>
 */
class ContextualWordRule extends AbstractPasswordRule {

    use Configurable;

    private static $context_strings = [];

    private static $min_length = 3;

    private static $template_var = "CONTEXTUAL_WORD_RULE";

    private static $template_value = "Your password cannot contain a word related to this service";

    /**
     * Allow classes extending this rule to use base level strings along with their own
     */
    public function getContextStrings(Member $member) {
        $config = SiteConfig::current_site_config();
        $site_strings = [
            $config->Title,
            $config->Tagline,
            $member->Email,
            $member->FirstName,
            $member->Surname
        ];

        // array of strings that are contextual
        $context_strings = [];

        // with each site string, remove parts that should be in there
        foreach($site_strings as $string) {
            $parts = preg_split("/[^a-zA-Z0-9]/", $string);
            array_filter(
                $parts,
                function($value) {
                    // filter out items that are not > min_length length
                    return strlen($value) > $this->config()->get('min_length');
                }
            );
            $context_strings = array_merge($context_strings, $parts);
        }

        // sites can provide their own context words
        $configured_context_strings = $this->config()->get('context_strings');
        if(is_array($configured_context_strings)) {
            array_filter(
                $configured_context_strings,
                function($value) {
                    // filter out items that are not > min_length length
                    return strlen($value) > $this->config()->get('min_length');
                }
            );
            // merge them in, ensure site config strings are retained
            $context_strings = array_merge($configured_context_strings, $context_strings);
        }

        // return a unique array of values
        $context_strings = array_unique($context_strings);

        return $context_strings;
    }

    /**
     * Perform password check agsinst contextual words
     * @throws PasswordVerificationException
     * @param string $password
     * @param Member $member
     * @returns boolean
     */
    public function check($password, Member $member = null) {
        $words = $this->getContextStrings($member);
        $valid = true;
        foreach($words as $word) {
            if(strpos( strtolower($password), strtolower($word) ) !== false) {
                $valid = false;
                break;
            }
        }
        if(!$valid) {
            // at least one banned word detected
            throw new PasswordVerificationException(
                _t(
                    "NSWDPC\\Passwords.PASSWORD_STRENGTH_FAIL",
                    "The password provided contains disallowed words, please try a different password"
                )
            );
        }

        return true;
    }
}
