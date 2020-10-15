<?php

namespace NSWDPC\Passwords;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Security\PasswordValidator;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\ArrayList;
use NSWDPC\Pwnage\Pwnage;
use SilverStripe\Core\Injector\Injector;

/**
 * Password model
 */
class Password {

    use Configurable;
    use Injectable;

    public function rules() {
        $pv = Config::inst()->get(PasswordValidator::class);

        $data = [];
        if(!empty($pv['min_length'])) {
            $data['MinLength'] =  sprintf( _t( self::class . '.MIN_LENGTH', 'The password must have a minimum length of %d characters'), $pv['min_length']);
        }

        if(!empty($pv['max_length'])) {
            $data['MaxLength'] =  sprintf( _t( self::class . '.MAX_LENGTH', 'The password must have a maximum length of %d characters'), $pv['max_length']);
        }

        if(!empty($pv['min_test_score'])) {
            $data['MinTestScore'] =  sprintf( _t( self::class . '.MIN_TEST_SCORE', 'Your password must pass %d  of the following test(s)'), $pv['min_test_score']);
        }

        $data['CharacterStrengthTests'] = ArrayList::create();
        if(!empty($pv['character_strength_tests'])  && is_array($pv['character_strength_tests'])) {

            foreach($pv['character_strength_tests'] as $k=>$v) {
                switch($k) {
                    case "lowercase":
                        $data['CharacterStrengthTests']->push([
                            'Description' => _t( self::class . '.LOWERCASE_REQUIRED', 'Lowercase characters are required')
                        ]);
                        break;
                    case "uppercase":
                        $data['CharacterStrengthTests']->push([
                            'Description' => _t( self::class . '.UPPERCASECASE_REQUIRED', 'Uppercase characters are required')
                        ]);
                        break;
                    case "digits":
                        $data['CharacterStrengthTests']->push([
                            'Description' => _t( self::class . '.DIGITS_REQUIRED', 'Number characters are required')
                        ]);
                        break;
                    case "punctuation":
                        $data['CharacterStrengthTests']->push([
                            'Description' => _t( self::class . '.PUNCTUATION_REQUIRED','Punctuation characters are required')
                        ]);
                        break;
                    // for defaults
                    default:
                        $data['CharacterStrengthTests']->push([
                            'Description' => sprintf( _t( self::class . '.CHARACTER_RANGE_REQUIRED', 'Characters in the following range are required: %s'), $k)
                        ]);
                        break;
                }
            }
        }

        // pwned password check
        $pwnage = Config::inst()->get(Pwnage::class);
        $data['PwnageCheck'] = (isset($pwnage['check_pwned_passwords'])
                                && $pwnage['check_pwned_passwords']
                                    ? _t(self::class . '.PWNAGE_CHECK', 'Your password must not have appeared in a known data breach (we will let you know if it has)')
                                    : ''
                                );
        $data['PwnageAttribution'] = false;
        if(!empty($pwnage['hibp_attribution'])) {
            $data['PwnageAttribution'] = $pwnage['hibp_attribution'];
        }

        $rule_checks = Config::inst()->get(PasswordRuleCheck::class, 'checks');
        if(!empty($rule_checks) && is_array($rule_checks)) {
            $data['RuleChecks'] = ArrayList::create();
            foreach($rule_checks as $rule_check_class) {
                $inst = Injector::inst()->create( $rule_check_class );
                if(!$inst instanceof AbstractPasswordRule || !$inst->canRun()) {
                    continue;
                }
                $template_var = $inst->config()->get('template_var');
                // default
                $template_value = $inst->config()->get('template_value');
                $data['RuleChecks']->push([
                    'Description' => _t( $rule_check_class . '.' . $template_var, $template_value)
                ]);
            }
        }

        $data['PasswordTitle'] = _t(self::class . '.PASSWORD_TITLE', 'The best passwords are a combination of words or characters that are memorable only to you. To help you choose the password we use the following rules.');

        return ArrayData::create($data);
    }

}
