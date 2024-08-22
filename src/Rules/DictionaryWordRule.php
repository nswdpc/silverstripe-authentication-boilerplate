<?php

namespace NSWDPC\Authentication\Rules;

use NSWDPC\Authentication\Exceptions\PasswordVerificationException;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Security\Member;

/**
 * Checks if a password is a dictionary word
 */
class DictionaryWordRule extends AbstractPasswordRule
{
    use Configurable;

    /**
     * @config
     */
    private static string $locale = "en_AU";

    /**
     * @config
     */
    private static string $template_var = "DICTIONARY_WORD_RULE";

    /**
     * @config
     */
    private static string $template_value = "Your password cannot be a dictionary word";

    /**
     * By default, allow rule checks can run
     */
    public function canRun(): bool
    {
        return extension_loaded('enchant');
    }

    /**
     * Perform password check using Enchant
     * Note that this is case insensitive so "English" will fail but "english" will pass
     * @throws PasswordVerificationException
     * @returns boolean
     */
    public function check(string $password, Member $member = null): bool
    {
        $locale = $this->config()->get('locale');
        $broker = enchant_broker_init();
        if (!enchant_broker_dict_exists($broker, $locale)) {
            throw new \Exception(_t(self::class . ".GENERAL_EXCEPTION", "The password cannot be validated"));
        } else {
            $dictionary = enchant_broker_request_dict($broker, $locale);
            $suggestions = [];
            $check = enchant_dict_quick_check($dictionary, $password, $suggestions);
            if($check) {
                throw new PasswordVerificationException(_t(self::class . ".DICTIONARY_WORD_FAIL", "Dictionary words are not allowed in the password"));
            }
        }

        return true;
    }
}
