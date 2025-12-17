<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvitationsController extends Controller
{
    public function show(Request $request, Invitation $invitation)
    {
        $member = $invitation->member()->withTrashed()->first();

        if (! auth()->check()) {
            session(['invitation' => [
                'code' => $invitation->code,
                'message' => "Please login or register to accept the invitation to submit results for {$member->full_name} in {$member->club->name}.",
            ]]);

            return redirect()->route('login');
        }

        abort_if($member->isAssigned(), 404);

        DB::transaction(function () use ($member, $invitation) {
            $member->assign(auth()->user());
            $invitation->delete();
        });

        return redirect()->route('dashboard')
            ->with('message', "You can now submit results for {$member->full_name} in {$member->club->name}.");
    }
}
