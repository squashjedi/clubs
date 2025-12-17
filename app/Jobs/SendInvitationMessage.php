<?php

namespace App\Jobs;

use App\Models\Club;
use App\Models\Player;
use App\Models\Invitation;
use App\Mail\InvitationEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendInvitationMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Invitation $invitation,
        public Club $club,
        public Player $player
    ) { }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->invitation->email)->queue(new InvitationEmail($this->invitation, $this->club, $this->player));
    }
}
