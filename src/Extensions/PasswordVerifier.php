<?php

namespace NSWDPC\Authentication\Extensions;

use NSWDPC\Authentication\Rules\PasswordRuleCheck;
use SilverStripe\Security\PasswordValidator;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Member;
use SilverStripe\Core\Extension;

/**
 * Extends {@link SilverStripe\Security\PasswordValidator} to provide verifiers of basic passwords
 * @extends \SilverStripe\Core\Extension<(\SilverStripe\Security\PasswordValidator & static)>
 */
class PasswordVerifier extends Extension
{
    /**
     * @return void
     */
    public function updateValidatePassword(string $password, Member $member, ValidationResult $validationResult, PasswordValidator $passwordValidator)
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
