<?php

namespace NSWDPC\Authentication\Services;

use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Member;
use SilverStripe\Security\PasswordValidator;

/**
 * Provide a basic password validator using NIST.gov guidelines:
 *
 * - Set and enforce an 8 character minimum length
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
class NISTPasswordValidator extends PasswordValidator
{
    /**
     * @var int
     * When setting a minimum password length, this is used as the min value
     */
    public const PASSWORD_MINIMUM_LENGTH = 8;

    /**
     * Composition rules, this must be null to override array
     * @inheritdoc
     * @config
     */
    private static $character_strength_tests = null;

    /**
     * Memorised secrets should be at least 8 characters
     * @inheritdoc
     * @config
     */
    private static $min_length = 8;

    /**
     * Composition rules
     * @inheritdoc
     * @config
     */
    private static $min_test_score = 0;

    /**
     * Historical password count
     * @inheritdoc
     * @config
     */
    private static $historic_count = 0;

    /**
     * @inheritdoc
     */
    protected $minLength = 8;

    /**
     * @inheritdoc
     */
    protected $minScore = 0;

    /**
     * @inheritdoc
     */
    protected $testNames = [];

    /**
     * @inheritdoc
     */
    protected $historicalPasswordCount = 0;

    /**
     * Override test complexity to none
     * @inheritdoc
     */
    public function getTests()
    {
        return [];
    }

    /**
     * Disallow setting of testNames
     * @inheritdoc
     */
    public function setTestNames($testNames)
    {
        return $this;
    }

    /**
     * Override complexity tests to none
     * @inheritdoc
     */
    public function getTestNames()
    {
        return [];
    }

    /**
     * @inheritdoc
     * Enforce minimum length defined by constant value, if configuration sets
     * the length under that value
     */
    public function getMinLength()
    {
        if ($this->minLength !== null) {
            $minLength = $this->minLength;
        } else {
            $minLength = $this->config()->get('min_length');
        }
        if($minLength < self::PASSWORD_MINIMUM_LENGTH) {
            $minLength = self::PASSWORD_MINIMUM_LENGTH;
        }
        return $minLength;
    }

    /**
     * @inheritdoc
     */
    public function setMinLength($minLength)
    {
        if($minLength < self::PASSWORD_MINIMUM_LENGTH) {
            $minLength = self::PASSWORD_MINIMUM_LENGTH;
        }
        return parent::setMinLength($minLength);
    }

    /**
     * @inheritdoc
     */
    public function setMinTestScore($minScore)
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setHistoricCount($count)
    {
        return $this;
    }

    /**
     * @param string $password
     * @param Member $member
     * @return ValidationResult
     */
    public function validate($password, $member)
    {
        $valid = ValidationResult::create();
        $minLength = $this->getMinLength();
        if ($minLength && strlen($password) < $minLength) {
            $error = _t(
                'SilverStripe\Security\PasswordValidator.TOOSHORT',
                'Password is too short, it must be {minimum} or more characters long',
                ['minimum' => $minLength]
            );

            $valid->addError($error, 'bad', 'TOO_SHORT');
        }
        $this->extend('updateValidatePassword', $password, $member, $valid, $this);
        return $valid;
    }

}
