<?php
namespace App\Traits;

use App\Models\Member;
use App\Models\Invitation;
use Illuminate\Support\Facades\DB;

trait Authentication {

	public function checkSessionHasInvitation()
	{
        if (session()->has('invitation')) {
            $invitation = Invitation::where('code', session('invitation.code'))->first();
            $member = Member::withTrashed()->find($invitation->member_id);

            DB::transaction(function () use ($member, $invitation) {
                $member->assign(auth()->user());
                $invitation->delete();
            });

            session()->forget('invitation');
            session()->flash('message', "You can now submit results for {$member->full_name} in {$member->club->name}.");
        }
	}

}