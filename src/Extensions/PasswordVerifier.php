<?php

namespace NSWDPC\Authentication\Extensions;

use NSWDPC\Authentication\Rules\PasswordRuleCheck;
use SilverStripe\Security\PasswordValidator;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Member;
use SilverStripe\Core\Extension;

/**
 * Extends {@link SilverStripe\Security\PasswordValidator} to provide verifiers of basic passwords
 */
class PasswordVerifier extends Extension
{
    /**
     * @param string $password
     * @param ValidationResult $validation_result
     * @param PasswordValidator $validator
     * @param Member $member
     * @return void
     */
    public function updateValidatePassword(string $password, ValidationResult $validation_result, PasswordValidator $validator, Member $member)
    {

        if(!$validation_result->isValid()) {
            // no need to continue with validation here as the password is already invalid for some reason
            return;
        }

        // $validation_result will contain errors if the password is not verified
        $checker = PasswordRuleCheck::create();
        $checker->runChecks($password, $member, $validation_result, $validator);

    }

}
