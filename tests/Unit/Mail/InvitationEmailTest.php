<?php

use App\Models\Club;
use App\Models\Member;
use App\Models\Invitation;
use App\Mail\InvitationEmail;

test('email contains the correct subject and content', function () {
    $club = Club::factory()->create(['name' => 'Giantswood Squash Club']);
    $member = Member::factory()->create(['first_name' => 'Roger', 'last_name' => 'Federer']);
    $invitation = Invitation::factory()->create();

    $mailable = new InvitationEmail($invitation, $club, $member);

    $mailable->assertHasSubject('Invitation to submit results');
    $mailable->assertSeeInText('This invitation has been sent by the Giantswood Squash Club administrator at reckify.com.');
    $mailable->assertSeeInText('Accept the invitation to have the privilege of submitting results for Roger Federer.');
});