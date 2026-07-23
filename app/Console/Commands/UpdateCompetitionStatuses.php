<?php

namespace App\Console\Commands;

use App\Models\Competition;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('competitions:update-statuses')]
#[Description('Flip competition status upcoming->open->closed based on registration dates')]
class UpdateCompetitionStatuses extends Command
{
    public function handle(): void
    {
        $opened = Competition::where('status', 'upcoming')
            ->whereNotNull('registration_opens_at')
            ->where('registration_opens_at', '<=', now())
            ->update(['status' => 'open']);

        $closed = Competition::where('status', 'open')
            ->whereNotNull('registration_closes_at')
            ->where('registration_closes_at', '<=', now())
            ->update(['status' => 'closed']);

        $this->info("Opened registration for {$opened} competition(s), closed {$closed} competition(s).");
    }
}
