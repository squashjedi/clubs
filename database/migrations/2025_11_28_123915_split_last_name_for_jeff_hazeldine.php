<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure columns exist
        if (
            ! Schema::hasTable('users') ||
            ! Schema::hasColumn('users', 'last_name') ||
            ! Schema::hasColumn('users', 'first_name')
        ) {
            return;
        }

        $user = DB::table('users')->where('id', 33341)->first();

        if (! $user || empty($user->last_name)) {
            return;
        }

        // Normalise whitespace
        $name = trim(preg_replace('/\s+/', ' ', $user->last_name));

        // Split on FIRST space only
        $parts = explode(' ', $name, 2);

        $first = $parts[0] ?? null;
        $last  = $parts[1] ?? null;

        // Format using helper
        $first = $first ? format_name($first) : null;
        $last  = $last  ? format_name($last)  : null;

        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'first_name' => $first,
                'last_name'  => $last,
                'updated_at' => Carbon::now(),
            ]);
    }

    public function down(): void
    {
        // Rollback? Optional.
        // Combine both back into last_name.
        $user = DB::table('users')->where('id', 33341)->first();

        if (! $user) {
            return;
        }

        $combined = trim(
            ($user->first_name ?? '') . ' ' . ($user->last_name ?? '')
        );

        DB::table('users')
            ->where('id', 33341)
            ->update([
                'last_name'  => $combined,
                'updated_at' => now(),
            ]);
    }
};
