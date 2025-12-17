<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Add first_name and last_name columns if they don’t exist
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
        });

        // 2) Perform the name-splitting + formatting migration
        if (
            Schema::hasColumn('users', 'name') &&
            Schema::hasColumn('users', 'first_name') &&
            Schema::hasColumn('users', 'last_name')
        ) {
            DB::table('users')
                ->orderBy('id')
                ->chunkById(200, function ($users) {
                    foreach ($users as $user) {

                        if (empty($user->name)) {
                            continue;
                        }

                        // normalise whitespace
                        $name = trim(preg_replace('/\s+/', ' ', $user->name));

                        // split on FIRST space only
                        $parts = explode(' ', $name, 2);

                        $first = $parts[0] ?? null;
                        $last  = $parts[1] ?? null;

                        // use your helper
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
                });
        }
    }

    public function down(): void
    {
        // Optional: Combine back into `name`
        if (
            Schema::hasColumn('users', 'name') &&
            Schema::hasColumn('users', 'first_name') &&
            Schema::hasColumn('users', 'last_name')
        ) {
            DB::table('users')
                ->orderBy('id')
                ->chunkById(200, function ($users) {
                    foreach ($users as $user) {
                        $combined = trim(
                            ($user->first_name ?? '') . ' ' . ($user->last_name ?? '')
                        );

                        DB::table('users')
                            ->where('id', $user->id)
                            ->update([
                                'name'       => $combined,
                                'updated_at' => now(),
                            ]);
                    }
                });
        }

        // Optional: remove first_name + last_name
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'first_name')) {
                $table->dropColumn('first_name');
            }
            if (Schema::hasColumn('users', 'last_name')) {
                $table->dropColumn('last_name');
            }
        });
    }
};
