<?php

namespace NSWDPC\Authentication\Services;

use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Security\Member;
use SilverStripe\Security\Validation\RulesPasswordValidator;

/**
 * Provide a basic password validator using NIST.gov guidelines:
 *
 * - Set and enforce a minimum 8 character minimum length
 * - Remove complexity checks
 * - Remove historical password checking
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
class NISTPasswordValidator extends RulesPasswordValidator
{
    /**
     * @var int
     * When setting a minimum password length, this is used as the min value
     */
    public const PASSWORD_MINIMUM_LENGTH = 12;

    /**
     * Composition rules, this must be null to override array
     * @inheritdoc
     */
    private static array $character_strength_tests = [];

    /**
     * Memorised secrets should be at least 8 characters
     * @inheritdoc
     */
    private static int $min_length = 12;

    /**
     * Composition rules
     * @inheritdoc
     */
    private static int $min_test_score = 0;

    /**
     * Historical password count
     * @inheritdoc
     */
    private static int $historic_count = 0;

    /**
     * @inheritdoc
     */
    protected ?int $minLength = 12;

    /**
     * @inheritdoc
     */
    protected ?int $minScore = 0;

    /**
     * @inheritdoc
     */
    protected ?array $testNames = [];

    /**
     * @inheritdoc
     */
    protected ?int $historicalPasswordCount = 0;

    /**
     * Override test complexity to none
     * @inheritdoc
     */
    #[\Override]
    public function getTests(): array
    {
        return [];
    }

    /**
     * Disallow setting of testNames
     * @inheritdoc
     */
    #[\Override]
    public function setTestNames(array $testNames): static
    {
        return $this;
    }

    /**
     * Override complexity tests to none
     * @inheritdoc
     */
    #[\Override]
    public function getTestNames(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     * Enforce minimum length defined by constant value, if configuration sets
     * the length under that value
     */
    #[\Override]
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
    #[\Override]
    public function setMinLength(int $minLength): static
    {
        if ($minLength < self::PASSWORD_MINIMUM_LENGTH) {
            $minLength = self::PASSWORD_MINIMUM_LENGTH;
        }

        return parent::setMinLength($minLength);
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function setMinTestScore($minScore): static
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function setHistoricCount(int $count): static
    {
        return $this;
    }

    #[\Override]
    public function validate(string $password, Member $member): ValidationResult
    {
        $valid = ValidationResult::create();
        $minLength = $this->getMinLength();
        if ($minLength && strlen($password) < $minLength) {
            $error = _t(
                RulesPasswordValidator::class . '.TOOSHORT',
                'Password is too short, it must be {minimum} or more characters long',
                ['minimum' => $minLength]
            );

            $valid->addError($error, 'bad', 'TOO_SHORT');
        }

        $this->extend('updateValidatePassword', $password, $member, $valid, $this);
        return $valid;
    }

}
