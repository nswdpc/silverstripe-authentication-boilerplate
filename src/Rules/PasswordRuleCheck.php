<?php

namespace NSWDPC\Authentication\Rules;

use NSWDPC\Authentication\Exceptions\PasswordVerificationException;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Security\Member;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\PasswordValidator;
use SilverStripe\ORM\ValidationResult;

class PasswordRuleCheck {

    use Configurable;

    use Injectable;

    /**
     * @var array
     * @config
     */
    private static $checks = [
        DictionaryWordRule::class,
        SequentialCharacterRule::class,
        RepetitiveCharacterRule::class,
        ContextualWordRule::class
    ];

    /**
     * Process all configured checks
     */
    public function runChecks($password, Member $member = null, ValidationResult $validation_result, PasswordValidator $validator = null) {
        $checks = $this->config()->get('checks');
        if(!is_array($checks)) {
            return;
        }

        foreach($checks as $rule) {
            $inst = Injector::inst()->create( $rule );
            if(!$inst instanceof AbstractPasswordRule || !$inst->canRun()) {
                // ignore
                continue;
            }
            try {
                $result = $inst->check($password, $member);
            } catch (PasswordVerificationException $exception) {
                // throws a PasswordVerificationException if check fails
                $validation_result->addError($exception->getMessage(), ValidationResult::TYPE_ERROR, 'PASSWORD_VERIFICATION_FAILED');
            } catch (\Exception $exception) {
                $validation_result->addError('The password could not be verified at the current time', ValidationResult::TYPE_ERROR, 'PASSWORD_VERIFICATION_FAILED_GENERIC');
            }
        }

    }
}
