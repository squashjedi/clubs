<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\Traits\Sortable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Division extends Model
{
    use HasFactory, Sortable;

    protected $guarded = [];

    public function contestants(): HasMany
    {
        return $this->hasMany(Contestant::class)->withTrashed();
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'league_session_id');
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(Tier::class);
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }

    public function name()
    {
        $divisionName = 'Box '.($this->tier->index + 1);

        if ($this->tier->divisions->count() > 1) {
            $divisionName = $divisionName.'('.chr(97 + $this->index).')';
        }

        return $divisionName;
    }

    public function calculateStandings(): array
    {
        $order = $this->session->tableOrder();

        // per-fixture rows (home + away)
        $resultsSubquery = DB::table('results')
            ->where('results.division_id', $this->id)
            ->select([
                'results.id',
                'results.division_id',
                DB::raw('hc.id AS contestant_id'),
                DB::raw('results.home_score AS total_for'),
                DB::raw('results.away_score AS total_against'),
                DB::raw('CASE WHEN results.home_score > results.away_score THEN 1 ELSE 0 END AS won'),
                DB::raw('CASE WHEN results.home_score = results.away_score THEN 1 ELSE 0 END AS drawn'),
                DB::raw('CASE WHEN results.home_score < results.away_score THEN 1 ELSE 0 END AS lost'),
                DB::raw('results.home_attended AS attended'),
            ])
            ->join('contestants as hc', function ($join) {
                $join->on('hc.player_id', '=', 'results.home_player_id')
                    ->whereColumn('hc.division_id', 'results.division_id')
                    ->whereNull('hc.deleted_at');
            })
            ->join('contestants as ac', function ($join) {
                $join->on('ac.player_id', '=', 'results.away_player_id')
                    ->whereColumn('ac.division_id', 'results.division_id')
                    ->whereNull('ac.deleted_at');
            })
            ->unionAll(
                DB::table('results')
                    ->where('results.division_id', $this->id)
                    ->select([
                        'results.id',
                        'results.division_id',
                        DB::raw('ac.id AS contestant_id'),
                        DB::raw('results.away_score AS total_for'),
                        DB::raw('results.home_score AS total_against'),
                        DB::raw('CASE WHEN results.away_score > results.home_score THEN 1 ELSE 0 END AS won'),
                        DB::raw('CASE WHEN results.away_score = results.home_score THEN 1 ELSE 0 END AS drawn'),
                        DB::raw('CASE WHEN results.away_score < results.home_score THEN 1 ELSE 0 END AS lost'),
                        DB::raw('results.away_attended AS attended'),
                    ])
                    ->join('contestants as hc', function ($join) {
                        $join->on('hc.player_id', '=', 'results.home_player_id')
                            ->whereColumn('hc.division_id', 'results.division_id')
                            ->whereNull('hc.deleted_at');
                    })
                    ->join('contestants as ac', function ($join) {
                        $join->on('ac.player_id', '=', 'results.away_player_id')
                            ->whereColumn('ac.division_id', 'results.division_id')
                            ->whereNull('ac.deleted_at');
                    })
            );

        // aggregate per contestant
        $subquery = DB::table('contestants')
            ->join('divisions', 'contestants.division_id', '=', 'divisions.id')
            ->join('tiers', 'divisions.tier_id', '=', 'tiers.id')
            ->join('league_sessions', 'tiers.league_session_id', '=', 'league_sessions.id')
            ->join('leagues', 'leagues.id', '=', 'league_sessions.league_id') // for club scope
            ->join('players', 'contestants.player_id', '=', 'players.id')

            // club-scoped membership
            ->leftJoin('club_player', function ($j) {
                $j->on('club_player.player_id', '=', 'players.id')
                ->on('club_player.club_id', '=', 'leagues.club_id');
                // ->whereNull('club_player.deleted_at'); // if you soft-delete pivot
            })

            // player ↔ user
            ->leftJoin('player_user', 'player_user.player_id', '=', 'players.id')

            ->leftJoinSub($resultsSubquery, 'r', function ($join) {
                $join->on('contestants.id', '=', 'r.contestant_id')
                    ->on('contestants.division_id', '=', 'r.division_id');
            })
            ->where('divisions.id', $this->id)
            ->selectRaw("
                tiers.id   AS tier_id,
                tiers.name AS tier_name,
                tiers.`index` AS tier_index,

                divisions.id      AS division_id,
                divisions.`index` AS division_index,
                divisions.promote_count,
                divisions.relegate_count,

                (SELECT COUNT(*) FROM divisions d2 WHERE d2.tier_id = tiers.id) AS tier_division_count,

                contestants.id      AS contestant_id,
                contestants.player_id,
                contestants.`index` AS contestant_index,
                (contestants.deleted_at IS NOT NULL) AS trashed,
                contestants.deleted_at AS contestant_deleted_at,

                players.first_name,
                players.last_name,

                IFNULL(SUM(r.attended), 0) AS total_played,
                IFNULL(SUM(r.won), 0)      AS total_won,
                IFNULL(SUM(r.drawn), 0)    AS total_drawn,
                IFNULL(SUM(r.lost), 0)     AS total_lost,

                IFNULL(SUM(CAST(r.total_for     AS SIGNED)), 0) AS total_for,
                IFNULL(SUM(CAST(r.total_against AS SIGNED)), 0) AS total_against,
                (
                    IFNULL(SUM(CAST(r.total_for     AS SIGNED)), 0)
                    -
                    IFNULL(SUM(CAST(r.total_against AS SIGNED)), 0)
                ) AS total_diff,

                (IFNULL(SUM(r.attended), 0) > 0) AS has_played,

                IFNULL((
                    SUM(r.won)   * league_sessions.pts_win +
                    SUM(r.drawn) * league_sessions.pts_draw +
                    SUM(r.total_for) * league_sessions.pts_per_set +
                    SUM(r.attended)  * league_sessions.pts_play
                ), 0) AS total_points,

                /* membership/account flags */
                MAX(CASE WHEN club_player.id IS NOT NULL THEN 1 ELSE 0 END) AS is_member,
                MAX(club_player.club_player_id) AS club_member_id,
                MAX(CASE WHEN player_user.user_id IS NOT NULL THEN 1 ELSE 0 END) AS has_user
            ")
            ->groupBy([
                'tiers.id','tiers.name','tiers.index',
                'divisions.id','divisions.index','divisions.promote_count','divisions.relegate_count',
                'contestants.id','contestants.player_id','contestants.index','contestants.deleted_at',
                'players.first_name','players.last_name',
            ])
            ->orderByRaw('trashed ASC')
            ->orderByDesc('has_played')
            ->orderBy('contestant_index', 'ASC');

        // rank within division
        $rows = DB::table(DB::raw("({$subquery->toSql()}) AS base"))
            ->mergeBindings($subquery)
            ->selectRaw('
                *,
                RANK() OVER (
                    PARTITION BY division_id
                    ORDER BY
                        trashed ASC,
                        has_played DESC,
                        '.$order.'
                ) AS `rank`
            ')
            ->orderBy('rank')
            ->orderBy('contestant_index')
            ->get();

        // empty division fallback
        if ($rows->isEmpty()) {
            $meta = DB::table('divisions')
                ->join('tiers', 'divisions.tier_id', '=', 'tiers.id')
                ->select([
                    'divisions.id as division_id',
                    'divisions.index as division_index',
                    'divisions.promote_count',
                    'divisions.relegate_count',
                    'tiers.id as tier_id',
                    'tiers.name as tier_name',
                    'tiers.index as tier_index',
                    DB::raw('(SELECT COUNT(*) FROM divisions d2 WHERE d2.tier_id = tiers.id) as tier_division_count'),
                ])
                ->where('divisions.id', $this->id)
                ->first();

            if (!$meta) return [];

            $letter = chr(97 + (int) $meta->division_index);
            $name = $meta->tier_name.((int) $meta->tier_division_count > 1 ? '('.$letter.')' : '');

            return [
                'id' => (int) $meta->division_id,
                'index' => (int) $meta->division_index,
                'name' => $name,
                'tier_id' => (int) $meta->tier_id,
                'tier_name' => $meta->tier_name,
                'tier_index' => (int) $meta->tier_index,
                'promote_count' => (int) $meta->promote_count,
                'relegate_count' => (int) $meta->relegate_count,
                'division_count' => (int) $meta->tier_division_count,
                'standings' => [],
            ];
        }

        $first = $rows->first();
        $letter = chr(97 + (int) $first->division_index);
        $name = $first->tier_name.((int) $first->tier_division_count > 1 ? '('.$letter.')' : '');

        return [
            'id' => (int) $first->division_id,
            'index' => (int) $first->division_index,
            'name' => $name,
            'tier_id' => (int) $first->tier_id,
            'tier_name' => $first->tier_name,
            'tier_index' => (int) $first->tier_index,
            'promote_count' => (int) $first->promote_count,
            'relegate_count' => (int) $first->relegate_count,
            'division_count' => (int) $first->tier_division_count,
            'standings' => $rows->map(function ($row) {
                $isMember = (bool) $row->is_member;

                return [
                    'id'         => (int) $row->contestant_id,
                    'player_id'  => (int) $row->player_id,
                    'name'       => $row->first_name.' '.$row->last_name,
                    'deleted_at' => $row->contestant_deleted_at,

                    'played' => (int) $row->total_played,
                    'rank'   => (int) $row->rank,
                    'won'    => (int) $row->total_won,
                    'drawn'  => (int) $row->total_drawn,
                    'lost'   => (int) $row->total_lost,
                    'for'    => (int) $row->total_for,
                    'against'=> (int) $row->total_against,
                    'diff'   => (int) $row->total_diff,
                    'points' => (int) $row->total_points,
                    'trashed'=> (bool) $row->trashed,
                    'seed'   => (int) $row->contestant_index + 1,

                    // membership/account extras
                    'is_member'      => $isMember,
                    'club_member_id' => $isMember ? (int) $row->club_member_id : null,
                    'member_id'      => $isMember ? (int) $row->club_member_id : null, // alias of club_member_id
                    'has_user'       => (bool) $row->has_user,
                ];
            })->values(),
        ];
    }

    public function sortableQuery($division)
    {
        return $division->tier->divisions();
    }
}
