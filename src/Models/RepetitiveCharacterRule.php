<?php

namespace NSWDPC\Passwords;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Security\Member;

/**
 * Checks a password for sequential characters
 * @author James <james.ellis@dpc.nsw.gov.au>
 */
class RepetitiveCharacterRule extends AbstractPasswordRule {

    use Configurable;

    /**
     * @config
     */
    private static $length = 3;//e.g aaa

    /**
     * @config
     */
    private static $template_var = "REPETITIVE_CHARACTER_RULE";

    /**
     * @config
     */
    private static $template_value = "Your password cannot contain repetitive characters (e.g aaa, 999)";

    /**
     * Perform password check
     * @throws PasswordVerificationException
     * @returns boolean
     */
    public function check($password, Member $member = null) {
        $pattern = '/(.)\1{2,}/';
        $result = preg_match( $pattern, $password, $matches);
        if($result > 0) {
            $match = isset($matches[0]) ? $matches[0] : "";
            throw new PasswordVerificationException( _t("NSWDPC\\Passwords.REPETITIVE_CHARACTER_FAIL", "Repetitive characters are not allowed in the password") );
        }
        return true;
    }
}
