<?php

declare(strict_types=1);

namespace NSWDPC\Authentication\Rules;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Security\Member;

/**
 * Checks a password for sequential characters
 */
abstract class AbstractPasswordRule
{
    use Configurable;

    // the validation code used in ValidationResult messages
    protected string $validationCode = '';

    abstract public function check(string $password, ?Member $member = null): bool;

    public function getValidationCode(): string
    {
        return $this->validationCode;
    }

    /**
     * By default, allow rule checks can run
     * @return boolean
     */
    public function canRun()
    {
        return true;
    }

}
