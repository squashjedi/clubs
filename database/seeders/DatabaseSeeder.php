<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Club;
use App\Models\Tier;
use App\Models\User;
use App\Models\Sport;
use App\Models\League;
use App\Models\Member;
use App\Models\Result;
use App\Models\Entrant;
use App\Models\Session;
use App\Models\ClubUser;
use App\Models\Division;
use App\Models\ClubSport;
use App\Models\TallyUnit;
use App\Models\Contestant;
use App\Models\SportTallyUnit;
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

        // $clubs = [
        //     'giantswood' => Club::factory()->create(['user_id' => $users['me'], 'name' => 'Giantswood Squash Club', 'slug' => 'giantswood-squash-club']),
        //     'prestbury' => Club::factory()->create(['name' => 'Prestbury Squash Club', 'slug' => 'prestbury-squash-club']),
        // ];

        $sports = [
            'squash' => Sport::factory()->create(['name' => 'Squash']),
            'badminton' => Sport::factory()->create(['name' => 'Badminton']),
            'tennis' => Sport::factory()->create(['name' => 'Tennis']),
            'snooker' => Sport::factory()->create(['name' => 'Snooker']),
            'darts' => Sport::factory()->create(['name' => 'Darts']),
            'chess' => Sport::factory()->create(['name' => 'Chess']),
            'table_tennis' => Sport::factory()->create(['name' => 'Table Tennis']),
            'racketball' => Sport::factory()->create(['name' => 'Racketball']),
        ];

        $tally_units = [
            'sets' => TallyUnit::factory()->create(['name' => 'Sets', 'key' => 'sets']),
            'games' => TallyUnit::factory()->create(['name' => 'Games', 'key' => 'games']),
            'points' => TallyUnit::factory()->create(['name' => 'Points', 'key' => 'points']),
            'frames' => TallyUnit::factory()->create(['name' => 'Frames', 'key' => 'frames']),
            'legs' => TallyUnit::factory()->create(['name' => 'Legs', 'key' => 'legs']),
            'racks' => TallyUnit::factory()->create(['name' => 'Racks', 'key' => 'racks']),
        ];

        SportTallyUnit::factory()->create(['sport_id' => $sports['squash']->id, 'tally_unit_id' => $tally_units['games']->id, 'max_best_of' => 5]);
        SportTallyUnit::factory()->create(['sport_id' => $sports['squash']->id, 'tally_unit_id' => $tally_units['points']->id, 'max_best_of' => 29]);
        SportTallyUnit::factory()->create(['sport_id' => $sports['badminton']->id, 'tally_unit_id' => $tally_units['games']->id, 'max_best_of' => 5]);
        SportTallyUnit::factory()->create(['sport_id' => $sports['badminton']->id, 'tally_unit_id' => $tally_units['points']->id, 'max_best_of' => 41]);
        SportTallyUnit::factory()->create(['sport_id' => $sports['tennis']->id, 'tally_unit_id' => $tally_units['sets']->id, 'max_best_of' => 5]);
        SportTallyUnit::factory()->create(['sport_id' => $sports['tennis']->id, 'tally_unit_id' => $tally_units['games']->id, 'max_best_of' => 11]);
        SportTallyUnit::factory()->create(['sport_id' => $sports['snooker']->id, 'tally_unit_id' => $tally_units['frames']->id, 'max_best_of' => 35]);
        SportTallyUnit::factory()->create(['sport_id' => $sports['darts']->id, 'tally_unit_id' => $tally_units['sets']->id, 'max_best_of' => 13]);
        SportTallyUnit::factory()->create(['sport_id' => $sports['darts']->id, 'tally_unit_id' => $tally_units['legs']->id, 'max_best_of' => 35]);
        SportTallyUnit::factory()->create(['sport_id' => $sports['chess']->id, 'tally_unit_id' => $tally_units['games']->id, 'max_best_of' => 35]);
        SportTallyUnit::factory()->create(['sport_id' => $sports['table_tennis']->id, 'tally_unit_id' => $tally_units['games']->id, 'max_best_of' => 7]);
        SportTallyUnit::factory()->create(['sport_id' => $sports['table_tennis']->id, 'tally_unit_id' => $tally_units['points']->id, 'max_best_of' => 21]);
        SportTallyUnit::factory()->create(['sport_id' => $sports['racketball']->id, 'tally_unit_id' => $tally_units['games']->id, 'max_best_of' => 5]);
        SportTallyUnit::factory()->create(['sport_id' => $sports['racketball']->id, 'tally_unit_id' => $tally_units['points']->id, 'max_best_of' => 29]);


        $club_member_count = 59;

        Club::factory()
            ->has(
                Member::factory()
                    ->count($club_member_count)
                    ->state(new Sequence(
                        fn ($sequence) => [
                            'club_member_id' => $sequence->index + 1,
                        ]
                    )),
            )
            ->has(
                Member::factory()
                    ->count(1)
                    ->state([
                        'club_member_id' => $club_member_count + 1,
                        'user_id' => $users['me']->id,
                        'first_name' => 'Paul',
                        'last_name' => 'Lord',
                    ])
            )
            ->has(
                League::factory()
                    ->count(1)
                    ->state([
                        'club_league_id' => 1,
                        'template' => [
                            [
                                'name' => 'Premier',
                                'divisions' => [
                                    [
                                        'contestant_count' => 5,
                                        'promote_count' => 0,
                                        'relegate_count' => 1,
                                    ]
                                ]
                            ],
                            [
                                'name' => 'Championship',
                                'divisions' => [
                                    [
                                        'contestant_count' => 5,
                                        'promote_count' => 1,
                                        'relegate_count' => 1,
                                    ],
                                    [
                                        'contestant_count' => 5,
                                        'promote_count' => 1,
                                        'relegate_count' => 1,
                                    ],
                                ]
                            ],
                            [
                                'name' => 'League 1',
                                'divisions' => [
                                    [
                                        'contestant_count' => 5,
                                        'promote_count' => 1,
                                        'relegate_count' => 0,
                                    ]
                                ]
                            ],
                        ],
                        'sport_id' => $sports['squash']->id,
                        'tally_unit_id' => 2,
                        'best_of' => 5,
                        'name' => 'Squash Box League'
                    ])
                    ->has(
                        Session::factory()
                            ->count(1)
                            ->state([
                                'starts_at' => Carbon::parse(Carbon::parse(now(), 'UTC')->format('Y-m-d'), 'Europe/London')->startOfDay()->utc(),
                                'ends_at' => Carbon::parse(Carbon::parse(now()->addDays(32), 'UTC')->format('Y-m-d'), 'Europe/London')->endOfDay()->utc(),
                                'structure' => [
                                    [
                                        'name' => 'Premier',
                                        'divisions' => [
                                            [
                                                'contestant_count' => 5,
                                                'prmote_count' => 0,
                                                'relegate_count' => 1,
                                            ]
                                        ]
                                    ],
                                    [
                                        'name' => 'Division 1',
                                        'divisions' => [
                                            [
                                                'contestant_count' => 5,
                                                'prmote_count' => 1,
                                                'relegate_count' => 1,
                                            ],
                                            [
                                                'contestant_count' => 5,
                                                'prmote_count' => 1,
                                                'relegate_count' => 1,
                                            ],
                                        ]
                                    ],
                                    [
                                        'name' => 'Division 2',
                                        'divisions' => [
                                            [
                                                'contestant_count' => 5,
                                                'prmote_count' => 1,
                                                'relegate_count' => 0,
                                            ]
                                        ]
                                    ],
                                ],
                                'pts_win' => 1,
                                'pts_draw' => 0,
                                'pts_per_set' => 1,
                                'pts_play' => 1,
                            ])
                            // ->has(
                            //     $tier = Tier::factory()
                            //         ->count(1)
                            //         ->state([
                            //             'index' => 0,
                            //             'name' => 'Division 1',
                            //         ])
                            //         ->has(
                            //             Division::factory()
                            //                 ->count(1)
                            //                 ->state(fn ($attributes, Tier $tier) => [
                            //                     'league_session_id' => $tier->league_session_id,
                            //                     'tier_id' => $tier->id,
                            //                     'index' => 0,
                            //                     'contestant_count' => 5,
                            //                     'promote_count' => 0,
                            //                     'relegate_count' => 2,
                            //                 ])
                            //                 ->has(
                            //                     Contestant::factory()
                            //                         ->count(5)
                            //                         ->state(new Sequence(
                            //                             fn ($sequence) => [
                            //                                 'member_id' => $sequence->index + 1,
                            //                                 'index' => $sequence->index,
                            //                             ]
                            //                         ))
                            //                 )
                            //         )
                            // )
                            // ->has(
                            //     $tier = Tier::factory()
                            //         ->count(1)
                            //         ->state([
                            //             'index' => 1,
                            //             'name' => 'Division 2',
                            //         ])
                            //         ->has(
                            //             Division::factory()
                            //                 ->count(1)
                            //                 ->state(fn ($attributes, Tier $tier) => [
                            //                     'league_session_id' => $tier->league_session_id,
                            //                     'tier_id' => $tier->id,
                            //                     'index' => 0,
                            //                     'contestant_count' => 5,
                            //                     'promote_count' => 1,
                            //                     'relegate_count' => 0,
                            //                 ])
                            //                 ->has(
                            //                     Contestant::factory()
                            //                         ->count(5)
                            //                         ->state(new Sequence(
                            //                             fn ($sequence) => [
                            //                                 'member_id' => $sequence->index + 6,
                            //                                 'index' => $sequence->index,
                            //                             ]
                            //                         ))
                            //                 )
                            //         )
                            //         ->has(
                            //             Division::factory()
                            //                 ->count(1)
                            //                 ->state(fn ($attributes, Tier $tier) => [
                            //                     'league_session_id' => $tier->league_session_id,
                            //                     'tier_id' => $tier->id,
                            //                     'index' => 1,
                            //                     'contestant_count' => 5,
                            //                     'promote_count' => 1,
                            //                     'relegate_count' => 0,
                            //                 ])
                            //                 ->has(
                            //                     Contestant::factory()
                            //                         ->count(5)
                            //                         ->state(new Sequence(
                            //                             fn ($sequence) => [
                            //                                 'member_id' => $sequence->index + 11,
                            //                                 'index' => $sequence->index,
                            //                             ]
                            //                         ))
                            //                 )
                            //         )
                            // )
                            ->has(
                                Entrant::factory()
                                    ->count(15)
                                    ->state(new Sequence(
                                        fn ($sequence) => [
                                            'member_id' => $sequence->index + 1,
                                            'index' => $sequence->index,
                                        ]
                                    ))
                            )
                    )
                    // ->has(
                    //     Session::factory()
                    //         ->count(1)
                    //         ->state([
                    //             'starting_at' => now()->subMonths(1)->startOfDay(),
                    //             'ending_at' => now()->subMonths(0)->endOfDay(),
                    //             'tally_unit_id' => $tally_units[1]->id,
                    //             'best_of' => 5,
                    //         ])
                    // )
            )
            ->create([
                'user_id' => $users['me'],
                'name' => 'Giantswood Squash Club',
                'slug' => 'giantswood-squash-club',
            ]);

        // Result::factory()->create([
        //     'division_id' => 1,
        //     'match_at' => now(),
        //     'member1_id' => 1,
        //     'member2_id' => 2,
        //     'member1_score' => 3,
        //     'member2_score' => 0,
        //     'member1_attended' => 1,
        //     'member2_attended' => 1,
        //     'submitted_by' => 1,
        //     'submitted_by_admin' => 0,
        // ]);

        // Result::factory()->create([
        //     'division_id' => 1,
        //     'match_at' => now(),
        //     'member1_id' => 1,
        //     'member2_id' => 3,
        //     'member1_score' => 2,
        //     'member2_score' => 2,
        //     'member1_attended' => 1,
        //     'member2_attended' => 1,
        //     'submitted_by' => 1,
        //     'submitted_by_admin' => 0,
        // ]);

        // Result::factory()->create([
        //     'division_id' => 1,
        //     'match_at' => now(),
        //     'member1_id' => 3,
        //     'member2_id' => 2,
        //     'member1_score' => 3,
        //     'member2_score' => 2,
        //     'member1_attended' => 1,
        //     'member2_attended' => 1,
        //     'submitted_by' => 1,
        //     'submitted_by_admin' => 0,
        // ]);

        // $members = collect();
        // for ($i = 1; $i < 58; $i++) {
        //     $members->prepend(Member::factory()->create(['club_member_id' => $i, 'club_id' => $clubs['giantswood']]));
        // }
        // $members->prepend(Member::factory()->create(['club_member_id' => 58, 'club_id' => $clubs['giantswood'], 'user_id' => $users['me']->id]));

        // $members = collect();
        // for ($i = 1; $i < 58; $i++) {
        //     $members->prepend(Member::factory()->create(['club_member_id' => $i, 'club_id' => $clubs['prestbury']]));
        // }

        // $members = Member::factory(57)->create(['club_id' => $clubs['giantswood']]);
        // $members->prepend(Member::factory()->create(['club_id' => $clubs['giantswood'], 'user_id' => $users['me']->id]));

        // League::factory()
        //     ->hasSessions(1, [
        //         'starting_at' => now()->subMonths(2)->startOfDay(),
        //         'ending_at' => now()->subMonths(1)->endOfDay(),
        //         'tally_unit_id' => $tally_units[1]->id,
        //         'best_of' => 5,
        //     ])
        //     ->hasSessions(1, [
        //         'starting_at' => now()->subMonths(1)->startOfDay(),
        //         'ending_at' => now()->subMonths(0)->endOfDay(),
        //         'tally_unit_id' => $tally_units[1]->id,
        //         'best_of' => 5,
        //     ])
        //     ->create([
        //         'club_league_id' => 1,
        //         'club_id' => $clubs['giantswood']->id,
        //         'sport_id' => $sports['squash']->id,
        //         'name' => 'Squash Box League'
        //     ]);

        // League::factory()->create([
        //     'club_league_id' => 2,
        //     'club_id' => $clubs['giantswood']->id,
        //     'sport_id' => $sports['badminton']->id,
        //     'name' => 'Badminton Box League'
        // ]);

        // League::factory()->create([
        //     'club_league_id' => 1,
        //     'club_id' => $clubs['prestbury']->id,
        //     'sport_id' => $sports['squash']->id,
        //     'name' => 'Badminton Box League'
        // ]);
    }
}