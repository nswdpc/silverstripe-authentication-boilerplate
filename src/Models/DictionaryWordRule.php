<?php

namespace NSWDPC\Passwords;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Security\Member;

/**
 * Checks if a password is a dictionary word
 * @author James <james.ellis@dpc.nsw.gov.au>
 */
class DictionaryWordRule extends AbstractPasswordRule {

    use Configurable;

    private static $locale = "en_AU";

    private static $template_var = "DICTIONARY_WORD_RULE";

    private static $template_value = "Your password cannot be a dictionary word";

    /**
     * By default, allow rule checks can run
     * @return boolean
     */
    public function canRun() {
        return extension_loaded('enchant');
    }

    /**
     * Perform password check using Enchant
     * Note that this is case insensitive so "English" will fail but "english" will pass
     * @throws PasswordVerificationException
     * @returns boolean
     */
    public function check($password, Member $member = null) {
        $locale = $this->config()->get('locale');
        $broker = enchant_broker_init();
        if (!enchant_broker_dict_exists($broker, $locale)) {
            throw new \Exception( _t("NSWDPC\\Passwords.GENERAL_EXCEPTION", "The password cannot be validated") );
        } else {
            $dictionary = enchant_broker_request_dict($broker, $locale);
            $check = enchant_dict_quick_check($dictionary, $password, $suggestions );
            if($check) {
                throw new PasswordVerificationException( _t("NSWDPC\\Passwords.DICTIONARY_WORD_FAIL", "Dictionary words are not allowed in the password") );
            }
        }
        return true;
    }
}
