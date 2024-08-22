<?php

namespace NSWDPC\Authentication\Tests;

use NSWDPC\Authentication\Exceptions\PasswordVerificationException;
use NSWDPC\Authentication\Rules\ContextualWordRule;
use NSWDPC\Authentication\Rules\DictionaryWordRule;
use NSWDPC\Authentication\Rules\RepetitiveCharacterRule;
use NSWDPC\Authentication\Rules\SequentialCharacterRule;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Member;
use SilverStripe\Security\PasswordValidator;

class PasswordStrengthTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function testContextualWords()
    {
        $strings = [
            'website',
            '',
            'contextword',
            'with spaces',
            'computer',
            'c0mputer',
            'dept'
        ];

        $member = Member::create([
            'Email' => 'bob.smith@example.com',
            'FirstName' => 'Bob',
            'Surname' => 'Smith',
        ]);

        // password set to check
        $passwords = [
            // password => expected result, false=fail
            'bob123website' => false,// fail on bob and website
            'bobsmithwebsite' => false,// fail on bob, smith and website
            'HN0poiol1099' => true,// OK
            'correcthorsebatterystaple' => true,// OK
            '90009abcd' => true, // OK, for this check
            'c@mputer' => true, // OK, for this check
            'contextword' => false,// fail on contextword
            '100Bobp98sdjkfhsdfs' => false,// fail on Bob
            '100bobp98sdjkfhsdfs' => false,// fail on bob
            'deptpassw0rd1' => false
        ];

        Config::modify()->set(ContextualWordRule::class, 'context_strings', $strings);
        Config::modify()->set(ContextualWordRule::class, 'min_length', 3);

        $rule = Injector::inst()->create(ContextualWordRule::class);
        $strings = $rule->getContextStrings($member);

        $this->assertNotEmpty($strings, "Strings are empty!");

        foreach($passwords as $password => $result) {
            try {
                $check = $rule->check($password, $member);
                // result of check should match
                $check_string = $check ? "OK" : "FAIL";
                $result_string = $result ? "OK" : "FAIL";
                $this->assertEquals($result, $check, "ContexturalWordRule password {$password} expected {$result_string} but got {$check_string}");
            } catch (PasswordVerificationException $e) {
                // password failed which we aren't expecting
                $this->assertFalse($result, "ContextualWordRule " . $e->getMessage() . "Password {$password} expected OK but got FAIL");
            }
        }

        return true;

    }

    public function testDictionaryWords()
    {
        // password set to check
        $passwords = [
            'password' => false,
            'notinthedictionary' => true,
            'umbrellla' => true,
            'umbrella' => false,
            'English' => false,// fail as pronoun
            'english' => true,// pass as the word is a proper noun
            'department' => false,
        ];

        $member = null;

        $rule = Injector::inst()->create(DictionaryWordRule::class);

        if($rule->canRun()) {

            foreach($passwords as $password => $result) {
                try {
                    $check = $rule->check($password, $member);
                    $check_string = $check ? "OK" : "FAIL";
                    $result_string = $result ? "OK" : "FAIL";
                    $this->assertEquals($result, $check, "DictionaryWordRule password {$password} expected {$result_string} but got {$check_string}");
                } catch (PasswordVerificationException $e) {
                    // password fail which we aren't expecting
                    $this->assertFalse($result, "DictionaryWordRule " . $e->getMessage() . "Password {$password} expected OK but got FAIL");
                }
            }

        }

        return true;
    }

    public function testSequentialCharacters()
    {

        // password set to check
        $passwords = [
            'abcd12345defgh' => false,
            'ab100password' => true,
            'ab123' => false,
            '3210pqstv' => true,
            'xyz12ab999' => false,
            'xy1289abgh' => true,
            'xyz123dcb' => false
        ];

        $member = null;

        Config::modify()->set(SequentialCharacterRule::class, 'length', 3);

        $rule = Injector::inst()->create(SequentialCharacterRule::class);

        foreach($passwords as $password => $result) {
            try {
                $check = $rule->check($password, $member);
                $check_string = $check ? "OK" : "FAIL";
                $result_string = $result ? "OK" : "FAIL";
                $this->assertEquals($result, $check, "SequentialCharacterRule password {$password} expected {$result_string} but got {$check_string}");
            } catch (PasswordVerificationException $e) {
                // password fail which we aren't expecting
                $this->assertFalse($result, "SequentialCharacterRule " . $e->getMessage() . "Password {$password} expected OK but got FAIL");
            }
        }

        return true;
    }

    public function testRepetitiveCharacters()
    {
        // password set to check
        $passwords = [
            'aaa123456' => false,// fail on aaa
            'aab123456' => true,
            'abc11111' => false,// fail on 11111
            'abc111' => false,// fail on 111
            'abc11' => true,
            'xyz9990abc' => false,// fail on 999
            'aaa111' => false, // fail on aaa and 111
            'aa11' => true,
        ];

        $member = null;

        Config::modify()->set(RepetitiveCharacterRule::class, 'length', 3);

        $rule = Injector::inst()->create(RepetitiveCharacterRule::class);

        foreach($passwords as $password => $result) {
            try {
                $check = $rule->check($password, $member);
                $check_string = $check ? "OK" : "FAIL";
                $result_string = $result ? "OK" : "FAIL";
                $this->assertEquals($result, $check, "RepetitiveCharacterRule password {$password} expected {$result_string} but got {$check_string}");
            } catch (PasswordVerificationException $e) {
                // password fail which we aren't expecting
                $this->assertFalse($result, "RepetitiveCharacterRule " . $e->getMessage() . "Password {$password} expected OK but got FAIL");
            }
        }

        return true;
    }

    public function testVerifier()
    {

        $member = Member::create([
            'Email' => 'bob.smith@example.com',
            'FirstName' => 'Bob',
            'Surname' => 'Smith',
        ]);

        $validator = Member::password_validator();

        $this->assertTrue($validator instanceof  PasswordValidator, "Member password validator is not an instance of PasswordValidator");

        // Bob wants to set his password to this... it should fail
        $repetitive_password = "abcd12345defgh";
        $result = $member->changePassword($repetitive_password, true);

        $this->assertFalse($result->isValid(), "{$repetitive_password} as a password is valid, it should not be");

        // Bob's friend tells him about password managers
        $managed_password = "f45f4fb2f09abb4e3bb6af2881f8598ee10210cd376f4e72665ba44988abfb4d";
        $result = $member->changePassword($managed_password, true);

        $this->assertTrue($result->isValid(), "{$managed_password} as a password is not valid, it should be");

    }

    /**
     * Given some random passwords based on alphabets, check their validity
     */
    public function testRandomPasswords()
    {

        // use english lower/upper and numbers
        $alphabets = [
            '0123456789',
            'abcdefghijklmnopqrstuvwxyz'
        ];
        $alphabets[2] = strtoupper($alphabets[1]);

        $letters = [];
        foreach($alphabets as $alphabet) {
            $letters = array_merge(str_split($alphabet), $letters);
        }

        $member = Member::create([
            'Email' => 'bob.smith@example.com',
            'FirstName' => 'Bob',
            'Surname' => 'Smith',
        ]);

        $password_count = 5;
        for($i = 0;$i < $password_count;$i++) {
            $keys = array_rand($letters, 8);
            $password = "";
            foreach($keys as $key) {
                $password .= $letters[ $key ];
            }
            $result = $member->changePassword($password, false);
            $this->assertTrue($result->isValid(), "{$password} as a password is not valid, it should be");
        }
    }
}
