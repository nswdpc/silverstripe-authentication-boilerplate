<?php

namespace NSWDPC\Authentication\Extensions;

use NSWDPC\Authentication\Rules\PasswordRuleCheck;
use SilverStripe\Security\Validation\PasswordValidator;
use SilverStripe\Security\Member;
use SilverStripe\Core\Extension;

/**
 * Extends {@link \SilverStripe\Security\Validation\PasswordValidator} to provide verifiers of basic passwords
 * @extends \SilverStripe\Core\Extension<never>
 */
class PasswordVerifier extends Extension
{
    public function updateValidatePassword(string $password, Member $member, \SilverStripe\Core\Validation\ValidationResult $validationResult, PasswordValidator $passwordValidator)
    {

        if (!$validationResult->isValid()) {
            // no need to continue with validation here as the password is already invalid for some reason
            return;
        }

        // $validation_result will contain errors if the password is not verified
        $checker = PasswordRuleCheck::create();
        $checker->runChecks($password, $member, $validationResult, $passwordValidator);

    }

}
