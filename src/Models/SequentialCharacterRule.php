<?php

namespace NSWDPC\Passwords;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Security\Member;

/**
 * Checks a password for sequential characters
 * @author James <james.ellis@dpc.nsw.gov.au>
 */
class SequentialCharacterRule extends AbstractPasswordRule {

    use Configurable;

    /**
     * Alphabets to chunk up and check
     * Add other alphabets in configuration
     */
    private static $alphabets = [
        '0123456789',
        'abcdefghijklmnopqrstuvwxyz'
    ];

    private static $template_var = "SEQUENTIAL_CHARACTER_RULE";

    private static $template_value = "Your password cannot contain sequential characters e.g abcd";

    /**
     * e.g abcd, 1234
     * Set to 3 to match abc, tuv, 345 and the like with a higher probability of false positives
     */
    private static $length = 4;

    /**
     * Perform password check
     * @throws PasswordVerificationException
     * @returns boolean
     */
    public function check($password, Member $member = null) {
        $alphabets = $this->config()->get('alphabets');
        $length = $this->config()->get('length');
        if(!empty($alphabets) && is_array($alphabets)) {
            foreach($alphabets as $alphabet) {
                // split each alphabet
                $chunks = mb_str_split($alphabet, 1);
                foreach($chunks as $k=>$character) {
                    $pattern = $character;
                    for($c = 1;$c < $length; $c++) {
                        if(isset($chunks[ $k + $c ])) {
                            $pattern .= $chunks[ $k + $c ];
                        }
                    }

                    // in each chunk, check the found pattern in the password
                    if(mb_strlen($pattern) == $length) {
                        $count = mb_substr_count($password, $pattern);
                        if($count > 0) {
                            throw new PasswordVerificationException(
                                sprintf (
                                    _t(
                                        "NSWDPC\\Passwords.SEQUENTIAL_CHARACTER_FAIL",
                                        "The sequential characters '%s' are not allowed in the password"
                                    ),
                                    $pattern
                                )
                            );
                        }
                    }
                }
            }
        }
        return true;
    }

}
