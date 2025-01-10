<?php

namespace NSWDPC\Authentication\Models;

use NSWDPC\Authentication\Rules\AbstractPasswordRule;
use NSWDPC\Authentication\Rules\PasswordRuleCheck;
use NSWDPC\Pwnage\Pwnage;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\PasswordValidator;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\ArrayList;

/**
 * Password model
 */
class Password
{
    use Configurable;
    use Injectable;

    public function rules()
    {

        $validator = Injector::inst()->get(PasswordValidator::class);

        // Min length
        $data = [];
        $minLength = $validator->getMinLength();
        if ($minLength > 0) {
            $data['MinLength'] =  sprintf(_t(self::class . '.MIN_LENGTH', 'The password must have a minimum length of %d characters'), $minLength);
        }

        // Min tests, if any
        $minTestScore = $validator->getMinTestScore();
        if ($minTestScore > 0) {
            $data['MinTestScore'] =  sprintf(_t(self::class . '.MIN_TEST_SCORE', 'Your password must pass %d of the following test(s)'), $minTestScore);

            // Available character strength tests
            $data['CharacterStrengthTests'] = ArrayList::create();
            $testNames = $validator->getTestNames();
            if (!empty($testNames)  && is_array($testNames)) {

                foreach ($testNames as $name) {
                    match ($name) {
                        "lowercase" => $data['CharacterStrengthTests']->push([
                            'Description' => _t(self::class . '.LOWERCASE_REQUIRED', 'Lowercase characters are required')
                        ]),
                        "uppercase" => $data['CharacterStrengthTests']->push([
                            'Description' => _t(self::class . '.UPPERCASECASE_REQUIRED', 'Uppercase characters are required')
                        ]),
                        "digits" => $data['CharacterStrengthTests']->push([
                            'Description' => _t(self::class . '.DIGITS_REQUIRED', 'Number characters are required')
                        ]),
                        "punctuation" => $data['CharacterStrengthTests']->push([
                            'Description' => _t(self::class . '.PUNCTUATION_REQUIRED', 'Punctuation characters are required')
                        ]),
                        default => $data['CharacterStrengthTests']->push([
                            'Description' => sprintf(_t(self::class . '.CHARACTER_RANGE_REQUIRED', 'Characters in the following range are required: %s'), $name)
                        ]),
                    };
                }
            }
        }

        // Pwned password check
        $pwnage = Injector::inst()->get(Pwnage::class);
        $checkPwnedPasswords = $pwnage->config()->get('check_pwned_passwords');
        $data['PwnageCheck'] = $checkPwnedPasswords
                                    ? _t(self::class . '.PWNAGE_CHECK', 'Your password must not have appeared in a known data breach (we will let you know if it has)')
                                    : '';
        $data['PwnageAttribution'] = false;
        $pwnageAttribution = $pwnage->config()->get('hibp_attribution');
        if ($pwnageAttribution) {
            $data['PwnageAttribution'] = $pwnageAttribution;
        }

        // Password rule checks
        $rule_checks = Config::inst()->get(PasswordRuleCheck::class, 'checks');
        if (!empty($rule_checks) && is_array($rule_checks)) {
            $data['RuleChecks'] = ArrayList::create();
            foreach ($rule_checks as $rule_check_class) {
                $inst = Injector::inst()->create($rule_check_class);
                if (!$inst instanceof AbstractPasswordRule || !$inst->canRun()) {
                    continue;
                }

                $template_var = $inst->config()->get('template_var');
                // default
                $template_value = $inst->config()->get('template_value');
                $data['RuleChecks']->push([
                    'Description' => _t($rule_check_class . '.' . $template_var, $template_value)
                ]);
            }
        }

        $data['PasswordTitle'] = _t(self::class . '.PASSWORD_TITLE', 'The best passwords are a combination of words or characters that are memorable only to you. To help you choose the password we use the following rules.');

        return ArrayData::create($data);
    }

}
