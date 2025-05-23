<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\User;
use App\Models\Sport;
use App\Models\League;
use App\Models\Member;
use App\Models\Session;
use App\Models\ClubUser;
use App\Models\ClubSport;
use App\Models\TallyUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\Sequence;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        $users = [
            'me' => User::factory()->create(['name' => 'squashjedi', 'email' => 'headoffice@sabresports.com', 'password' => bcrypt('jansher69')]),
            'fiona' => User::factory()->create(['name' => 'Fiona', 'email' => 'fi@mac.com', 'password' => bcrypt('jansher69')]),
        ];

        $clubs = [
            'giantswood' => Club::factory()->create(['user_id' => $users['me'], 'name' => 'Giantswood Squash Club', 'slug' => 'giantswood-squash-club']),
        ];

        $sports = [
            'squash' => Sport::factory()->create(['name' => 'Squash']),
            'badminton' => Sport::factory()->create(['name' => 'Badminton']),
            'tennis' => Sport::factory()->create(['name' => 'Tennis']),
            'snooker' => Sport::factory()->create(['name' => 'Snooker']),
            'darts' => Sport::factory()->create(['name' => 'Darts']),
            'chess' => Sport::factory()->create(['name' => 'Chess']),
            'table-tennis' => Sport::factory()->create(['name' => 'Table Tennis']),
            'racketball' => Sport::factory()->create(['name' => 'Racketball']),
            'pool' => Sport::factory()->create(['name' => 'Pool']),
        ];

        $tallyUnits = [
            '1' => TallyUnit::factory()->create(['name' => 'Game', 'max_best_of' => 5, 'sport_id' => $sports['squash']->id]),
            '2' => TallyUnit::factory()->create(['name' => 'Point', 'max_best_of' => 29, 'sport_id' => $sports['squash']->id]),
            '3' => TallyUnit::factory()->create(['name' => 'Game', 'max_best_of' => 5, 'sport_id' => $sports['badminton']->id]),
            '4' => TallyUnit::factory()->create(['name' => 'Point', 'max_best_of' => 41, 'sport_id' => $sports['badminton']->id]),
            '5' => TallyUnit::factory()->create(['name' => 'Set', 'max_best_of' => 5, 'sport_id' => $sports['tennis']->id]),
            '6' => TallyUnit::factory()->create(['name' => 'Game', 'max_best_of' => 11, 'sport_id' => $sports['tennis']->id]),
            '7' => TallyUnit::factory()->create(['name' => 'Frame', 'max_best_of' => 35, 'sport_id' => $sports['snooker']->id]),
            '8' => TallyUnit::factory()->create(['name' => 'Set', 'max_best_of' => 13, 'sport_id' => $sports['darts']->id]),
            '9' => TallyUnit::factory()->create(['name' => 'Leg', 'max_best_of' => 35, 'sport_id' => $sports['darts']->id]),
            '10' => TallyUnit::factory()->create(['name' => 'Game', 'max_best_of' => 35, 'sport_id' => $sports['chess']->id]),
            '11' => TallyUnit::factory()->create(['name' => 'Game', 'max_best_of' => 7, 'sport_id' => $sports['table-tennis']->id]),
            '12' => TallyUnit::factory()->create(['name' => 'Point', 'max_best_of' => 21, 'sport_id' => $sports['table-tennis']->id]),
            '13' => TallyUnit::factory()->create(['name' => 'Game', 'max_best_of' => 5, 'sport_id' => $sports['racketball']->id]),
            '14' => TallyUnit::factory()->create(['name' => 'Point', 'max_best_of' => 29, 'sport_id' => $sports['racketball']->id]),
            '15' => TallyUnit::factory()->create(['name' => 'Racks', 'max_best_of' => 35, 'sport_id' => $sports['pool']->id]),
        ];

        $members = collect();
        for ($i = 1; $i < 58; $i++) {
            $members->prepend(Member::factory()->create(['club_member_id' => $i, 'club_id' => $clubs['giantswood']]));
        }
        $members->prepend(Member::factory()->create(['club_member_id' => 58, 'club_id' => $clubs['giantswood'], 'user_id' => $users['me']->id]));

        // $members = Member::factory(57)->create(['club_id' => $clubs['giantswood']]);
        // $members->prepend(Member::factory()->create(['club_id' => $clubs['giantswood'], 'user_id' => $users['me']->id]));

        ClubSport::factory()->create(['club_id' => $clubs['giantswood']->id, 'sport_id' => $sports['squash']->id]);

        $leagues = [
            League::factory()->create(['club_id' => $clubs['giantswood']->id, 'sport_id' => $sports['squash']->id, 'name' => 'Box League']),
        ];
    }
}