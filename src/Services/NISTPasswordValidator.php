<?php

namespace NSWDPC\Authentication\Services;

use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Security\Member;
use SilverStripe\Security\Validation\EntropyPasswordValidator;
use SilverStripe\Security\Validation\RulesPasswordValidator;
use Symfony\Component\Validator\Constraints\PasswordStrength;

/**
 * Provide a basic password validator using NIST.gov guidelines.
 * This validator extends the core {@link SilverStripe\Security\Validation\EntropyPasswordValidator}
 * which provides entropy checks and historic count checks (if configured)
 *
 * Note that this password validator should be used in conjunction
 * with other verifiers and authentication processes, namely:
 *
 * - check password against breached password corpuses
 * - rule checks, such as dictionary word, repetitive characters, variations on the site name
 * - use MFA
 *
 * As these extra checks are provided by other modules,
 * they should be added as extensions via the `updateValidatePassword` method
 *
 * @author James
 */
class NISTPasswordValidator extends EntropyPasswordValidator
{
    private static int $password_strength = PasswordStrength::STRENGTH_STRONG;

    /**
     * @var int
     * The minimum possible length
     */
    public const PASSWORD_MINIMUM_LENGTH = 8;

    /**
     * @inheritdoc
     */
    protected ?int $minLength = 12;

    /**
     * Default minimum number of characters for a valid password.
     */
    private static int $min_length = 12;

    /**
     * Historical password count can be configured at the project level
     * @inheritdoc
     */
    private static int $historic_count = 0;

    /**
     * @inheritdoc
     */
    protected ?int $historicalPasswordCount = 0;

    /**
     * @inheritdoc
     * Enforce minimum length defined by constant value, if configuration sets
     * the length under that value
     */
    public function getMinLength(): int
    {
        $minLength = $this->minLength > 0 ? $this->minLength : $this->config()->get('min_length');

        if ($minLength < self::PASSWORD_MINIMUM_LENGTH) {
            $minLength = self::PASSWORD_MINIMUM_LENGTH;
        }

        return $minLength;
    }

    /**
     * @inheritdoc
     */
    public function setMinLength(int $minLength): static
    {
        if ($minLength < self::PASSWORD_MINIMUM_LENGTH) {
            $minLength = self::PASSWORD_MINIMUM_LENGTH;
        }

        $this->minLength = $minLength;
        return $this;
    }

    #[\Override]
    public function validate(string $password, Member $member): ValidationResult
    {
        $validationResult = ValidationResult::create();

        $minLength = $this->getMinLength();
        if ($minLength && strlen($password) < $minLength) {
            $error = _t(
                RulesPasswordValidator::class . '.TOOSHORT',
                'Password is too short, it must be {minimum} or more characters long',
                ['minimum' => $minLength]
            );

            $validationResult->addError($error, ValidationResult::TYPE_ERROR, 'TOO_SHORT');
            // return without checking further
            return $validationResult;
        }

        /**
         * Parent password validation
         * \SilverStripe\Security\Validation\EntropyPasswordValidator: check strength, do validation in extensions via updateValidatePassword
         * \SilverStripe\Security\Validation\PasswordValidator: min historic count check, if configured
         */
        $validationResult->combineAnd(parent::validate($password, $member));

        return $validationResult;
    }

}
