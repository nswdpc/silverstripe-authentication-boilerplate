<?php

namespace NSWDPC\Authentication\Rules;

use NSWDPC\Authentication\Exceptions\PasswordVerificationException;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Security\Member;

/**
 * Checks a password for sequential characters
 * @author James
 */
class RepetitiveCharacterRule extends AbstractPasswordRule
{
    use Configurable;

    protected string $validationCode = "REPETITIVE_CHARACTER_RULE";

    /**
     * @config
     */
    private static int $length = 3;//e.g aaa

    /**
     * @config
     */
    private static string $template_var = "REPETITIVE_CHARACTER_RULE";

    /**
     * @config
     */
    private static string $template_value = "Your password cannot contain repetitive characters (e.g aaa, 999)";

    /**
     * Perform password check
     * @throws PasswordVerificationException
     * @returns boolean
     */
    #[\Override]
    public function check(string $password, Member $member = null): bool
    {
        $min = 2;
        $length = (int)static::config()->get('length') - 1;
        if ($length < $min) {
            $length = $min;
        }

        $pattern = '/(.)\1{' . $length . ',}/';
        $result = preg_match($pattern, $password, $matches);
        if ($result > 0) {
            $match = $matches[0] ?? "";
            $exception = new PasswordVerificationException(_t(self::class . ".REPETITIVE_CHARACTER_FAIL", "Repetitive characters are not allowed in the password"));
            $exception->setRule($this);
            throw $exception;
        }

        return true;
    }
}
