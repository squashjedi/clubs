<?php

namespace App\Jobs;

use App\Models\Session;
use Illuminate\Support\Facades\Mail;
use App\Mail\LeagueSessionPublishedEmail;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendLeagueSessionPublishedMessage implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Session $session)
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->session->divisions()->each(function ($division) {
            $division->contestants()->notNotified()->each(function ($contestant) {
                $user = $contestant->member?->user;
                if ($user?->email) {
                    Mail::to($user->email)->queue(new LeagueSessionPublishedEmail($this->session->league->club, $this->session->league, $this->session, $user));
                    $contestant->update([
                        'notified_at' => now(),
                    ]);
                }
            });
        });
    }
}
