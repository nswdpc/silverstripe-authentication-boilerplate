<?php

namespace NSWDPC\Authentication\Tests;

use NSWDPC\Authentication\Rules\ContextualWordRule;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Member;
use SilverStripe\SiteConfig\SiteConfig;

class ContextualRuleTest extends SapphireTest
{
    protected $usesDatabase = true;


    public function testContextualWords()
    {
        $strings = [
            'Our website',
            'About us',
            'Computers',
            '',// empty string
            'something;with*punctuation'
        ];

        $config = SiteConfig::current_site_config();
        $config->Title = 'The test website';
        $config->Tagline = 'This is a website with a tagline';
        $config->write();

        $member = Member::create([
            'Email' => 'bob.smith@example.com',
            'FirstName' => 'Barry',
            'Surname' => 'St.John-Smythe',
        ]);
        $member->write();

        // configured strings
        Config::modify()->set(ContextualWordRule::class, 'context_strings', $strings);
        Config::modify()->set(ContextualWordRule::class, 'min_length', 4);

        $rule = Injector::inst()->create(ContextualWordRule::class);
        $strings = $rule->getContextStrings($member);

        sort($strings);

        $this->assertNotEmpty($strings, "Strings are empty!");

        $expected = [
            'About',
            'Barry',
            'Computers',
            'John',
            'Smythe',
            'This',
            'example',
            'punctuation',
            'smith',
            'something',
            'tagline',
            'test',
            'website',
            'with'
        ];

        $result = array_diff($strings, $expected);

        $this->assertEmpty($result);

    }

}
