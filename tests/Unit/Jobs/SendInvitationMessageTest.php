<?php

use App\Models\Club;
use App\Models\User;
use App\Models\Member;
use App\Models\Invitation;
use App\Mail\InvitationEmail;
use App\Jobs\SendInvitationMessage;
use Illuminate\Support\Facades\Mail;

test('it sends a message to the email address of the invitation', function () {
    Mail::fake();

    $club = Club::factory()->create();
    $member = Member::factory()->create();
    $invitation = Invitation::factory()->create(['member_id' => $member->id, 'email' => 'john@doe.com']);

    SendInvitationMessage::dispatch($invitation, $club, $member);

    Mail::assertQueued(InvitationEmail::class, function ($email) use ($invitation, $club, $member) {
        return $email->hasTo('john@doe.com')
            && $email->invitation->is($invitation)
            && $email->club->is($club)
            && $email->member->is($member);
    });
});