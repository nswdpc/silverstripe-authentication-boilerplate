<?php

namespace NSWDPC\Authentication\Exceptions;

use NSWDPC\Authentication\Rules\AbstractPasswordRule;

/**
 * Custom exception when a password does not meet verification rules
 * Use getRule() to return the rule triggering the exception
 * @author James
 */
class PasswordVerificationException extends \Exception
{
    protected ?AbstractPasswordRule $rule = null;

    public function setRule(AbstractPasswordRule $rule) {
        $this->rule = $rule;
    }

    public function getRule(): ?AbstractPasswordRule
    {
        return $this->rule;
    }
}
