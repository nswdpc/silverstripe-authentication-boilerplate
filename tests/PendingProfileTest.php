<?php

namespace NSWDPC\Authentication\Tests;

use NSWDPC\Authentication\Models\PendingProfile;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Member;

class PendingProfileTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function setUp(): void
    {
        parent::setUp();
        Config::modify()->set(PendingProfile::class, 'require_admin_approval', true);
        Config::modify()->set(PendingProfile::class, 'require_self_verification', true);
    }

    public function testMakePending(): void
    {
        $member = Member::create([
            'FirstName' => 'Pending',
            'Surname' => 'Profile',
            'Email' => 'pending@example.com'
        ]);
        $memberId = $member->write();

        $this->assertNotEmpty($memberId);

        // Make member pending
        $member->makePending();

        $pendingProfile = $member->PendingProfile();

        $this->assertInstanceOf(PendingProfile::class, $pendingProfile);
        $this->assertTrue($pendingProfile->exists(), "Pending profile exists");

        $this->assertEquals(1, $pendingProfile->RequireAdminApproval);
        $this->assertEquals(1, $pendingProfile->RequireSelfVerification);

        $this->assertTrue($member->getIsPending());

    }

    public function testMakeRemovePending(): void
    {
        $member = Member::create([
            'FirstName' => 'Pending',
            'Surname' => 'Profile',
            'Email' => 'pending@example.com'
        ]);
        $memberId = $member->write();

        $this->assertNotEmpty($memberId);

        // Make member pending
        $member->makePending();

        $pendingProfile = $member->PendingProfile();

        $this->assertInstanceOf(PendingProfile::class, $pendingProfile);
        $this->assertTrue($pendingProfile->exists(), "Pending profile exists");

        $this->assertEquals(1, $pendingProfile->RequireAdminApproval);
        $this->assertEquals(1, $pendingProfile->RequireSelfVerification);

        $this->assertTrue($member->getIsPending());

        $member->removePending();

        $checkProfile = PendingProfile::get()->byId($pendingProfile->ID);
        $this->assertEmpty($checkProfile);

        $this->assertFalse($member->getIsPending());

    }

    public function testCreateForMember(): void
    {
        $member = Member::create([
            'FirstName' => 'Pending',
            'Surname' => 'Profile',
            'Email' => 'pending@example.com'
        ]);
        $memberId = $member->write();
        $this->assertNotEmpty($memberId);
        $pendingProfile = PendingProfile::createForMember($member);
        $this->assertEquals(1, $pendingProfile->RequireAdminApproval);
        $this->assertEquals(1, $pendingProfile->RequireSelfVerification);

        $this->assertTrue($member->getIsPending());
    }

    public function testDirectCreate(): void
    {
        $member = Member::create([
            'FirstName' => 'Pending',
            'Surname' => 'Profile',
            'Email' => 'pending@example.com'
        ]);
        $memberId = $member->write();
        $this->assertNotEmpty($memberId);
        $pendingProfile = PendingProfile::create([
            'RequireAdminApproval' => 1,
            'RequireSelfVerification' => 1,
            'MemberID' => $memberId
        ]);
        $pendingProfile->write();
        $this->assertTrue($member->getIsPending());
    }

    public function testDirectCreateDelete(): void
    {
        $member = Member::create([
            'FirstName' => 'Pending',
            'Surname' => 'Profile',
            'Email' => 'pending@example.com'
        ]);
        $memberId = $member->write();
        $this->assertNotEmpty($memberId);
        $pendingProfile = PendingProfile::create([
            'RequireAdminApproval' => 1,
            'RequireSelfVerification' => 1,
            'MemberID' => $memberId
        ]);
        $pendingProfile->write();
        $this->assertTrue($member->getIsPending());
        $pendingProfile->delete();
        $this->assertFalse($member->getIsPending());
    }


}
