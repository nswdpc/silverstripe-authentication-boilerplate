<?php

namespace NSWDPC\Authentication\Rules;

use NSWDPC\Authentication\Exceptions\PasswordVerificationException;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Security\Member;

/**
 * Checks if a password provided is within a list of words based on site context / member details, based on configuration
 * @see 5.1.1.2 Memorized Secret Verifiers
 */
class ContextualWordRule extends AbstractPasswordRule
{
    use Configurable;

    /**
     * @config
     */
    private static $context_strings = [];

    /**
     * @config
     */
    private static $min_length = 4;

    /**
     * @config
     */
    private static $template_var = "CONTEXTUAL_WORD_RULE";

    /**
     * @config
     */
    private static $template_value = "Your password cannot contain a word related to this service";

    /**
     * Allow classes extending this rule to use base level strings along with their own
     */
    public function getContextStrings(Member $member)
    {
        $config = SiteConfig::current_site_config();
        $site_strings = [
            $config->Title,
            $config->Tagline,
            $member->Email,
            $member->FirstName,
            $member->Surname
        ];

        $configured_context_strings = $this->config()->get('context_strings');

        // array of strings that are contextual
        $site_strings = array_merge($site_strings, $configured_context_strings);
        $context_strings = [];

        $min_length = $this->config()->get('min_length');

        // split strings into chunks that only contain alphanumeric chrs
        foreach($site_strings as $string) {
            if(!is_string($string)) {
                $string = "";
            }
            $parts = preg_split("/[^a-zA-Z0-9]/", $string);
            $parts = array_filter(
                $parts,
                function ($value) use ($min_length) {
                    // filter out items that are not > min_length length
                    return strlen($value) >= $min_length;
                }
            );
            // add filtered parts to context strings
            $context_strings = array_merge($context_strings, $parts);
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
    public function check($password, Member $member = null): bool
    {
        $words = $this->getContextStrings($member);
        $valid = true;
        foreach($words as $word) {
            /**
             * needle = word
             * haystack = password
             * Test whether the word appears in the password
             */
            if(strpos(strtolower($password), strtolower($word)) !== false) {
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
