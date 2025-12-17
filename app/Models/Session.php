<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Session extends Model
{
    /** @use HasFactory<\Database\Factories\SessionFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $table = 'league_sessions';

    protected $appends = ['active_period'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'built_at' => 'datetime',
            'published_at' => 'datetime',
            'processed_at' => 'datetime',
            'division_template' => 'array',
            'structure' => 'array',
        ];
    }

    protected function activePeriod(): Attribute
    {
        return Attribute::make(
            get: function () {
                $starts_at = $this->starts_at->timezone($this->timezone);
                $ends_at = $this->ends_at->timezone($this->timezone);

                if ($starts_at->format('Y') === $ends_at->format('Y')) {
                    if ($starts_at->format('M Y') === $ends_at->format('M Y')) {
                        if ($starts_at->format('j M Y') === $ends_at->format('j M Y')) {
                            return $starts_at->format('j M Y');
                        }

                        return $starts_at->format('j')."-".$ends_at->format('j M Y');
                    }

                    return $starts_at->format('j M')." - ".$ends_at->format('j M Y');
                }

                return $starts_at->format('j M Y')." - ".$ends_at->format('j M Y');
            }
        );
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function tallyUnit(): BelongsTo
    {
        return $this->belongsTo(TallyUnit::class);
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(Tier::class, 'league_session_id');
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class, 'league_session_id');
    }

    public function contestants(): HasManyThrough
    {
        return $this->hasManyThrough(Contestant::class, Division::class, 'league_session_id')->withTrashed();
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'entrants', 'league_session_id', 'member_id');
    }

    public function entrants(): HasMany
    {
        return $this->hasMany(Entrant::class, 'league_session_id');
    }

    public function previous()
    {
        return $this->league->sessions()->where('id', '<', $this->id)->orderBy('id', 'desc')->first();
    }

    public function next()
    {
        return $this->league->sessions()->where('id', '>', $this->id)->orderBy('id', 'asc')->first();
    }

    public function isPublished()
    {
        return ! is_null($this->published_at);
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->whereNotNull('published_at');
    }

    #[Scope]
    protected function unpublished(Builder $query): void
    {
        $query->whereNull('published_at');
    }

    #[Scope]
    protected function startingSoon(Builder $query): void
    {
        $query->where('starts_at', '>', now());
    }

    #[Scope]
    protected function inProgress(Builder $query): void
    {
        $now = now();
        $query
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now);
    }

    #[Scope]
    protected function ended(Builder $query): void
    {
        $query->where('ends_at', '<', now());
    }

    #[Scope]
    protected function notProcessed(Builder $query): void
    {
        $query->whereNull('processed_at');
    }

    #[Scope]
    protected function processed(Builder $query): void
    {
        $query->whereNotNull('processed_at');
    }

    public function removeStructure()
    {
        DB::transaction(function () {
            foreach ($this->tiers as $tier) {
                foreach ($tier->divisions as $division) {
                    foreach ($division->contestants()->withTrashed()->get() as $contestant) {
                        $contestant->deleteFromDivision();
                    }
                    $division->forceDelete();
                }
                $tier->forceDelete();
            }
        });
    }

    public function isBuilt()
    {
        return !is_null($this->built_at);
    }

    public function isStructureDifferentFromSeedings(): bool
    {
        // 1. Expected order from seedings
        $seedingIds = $this->entrants()
            ->orderBy('index')
            ->pluck('member_id')
            ->toArray();

        // 2. Actual structure order: row-first across divisions in each tier
        $structuredIds = [];

        foreach ($this->structure as $tier) {
            $divisions = $tier['divisions'] ?? [];

            // Max contestants in any division in this tier
            $maxRows = collect($divisions)
                ->map(fn($d) => count($d['contestants'] ?? []))
                ->max();

            // Row-wise extraction
            for ($i = 0; $i < $maxRows; $i++) {
                foreach ($divisions as $division) {
                    if (isset($division['contestants'][$i])) {
                        $structuredIds[] = $division['contestants'][$i]['member_id'];
                    }
                }
            }
        }

        return $seedingIds !== $structuredIds;
    }

    public function playersInSession()
    {
        $inSession = Contestant::withTrashed()->whereHas('division.tier', function ($query) {
            $query->where('league_session_id', $this->id);
        })->pluck('player_id');

        return $inSession;
    }

    public function tableOrder(): string
    {
        if ($this->created_at->isAfter(Carbon::parse(config('app.table_order_change_date'))))  {
            return 'total_points DESC, total_won DESC, total_drawn DESC, total_diff DESC, total_for DESC, total_played DESC';
        }

        return 'total_points DESC, total_played DESC, total_won DESC, total_drawn DESC, total_diff DESC, total_for DESC';
    }

    public function isNewTableOrder()
    {
        return $this->created_at->isAfter(Carbon::parse(config('app.table_order_change_date'))) ? true : false;
    }

    public function calculateStandings(): array
    {
        $sessionId = $this->id;
        $order = $this->tableOrder();

        $resultsSubquery = DB::table('results')
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

        $subquery = DB::table('contestants')
            ->join('divisions', 'contestants.division_id', '=', 'divisions.id')
            ->join('tiers', 'divisions.tier_id', '=', 'tiers.id')
            ->join('league_sessions', 'tiers.league_session_id', '=', 'league_sessions.id')
            ->join('leagues', 'leagues.id', '=', 'league_sessions.league_id') // club scope
            ->join('players', 'contestants.player_id', '=', 'players.id')

            // club-scoped membership
            ->leftJoin('club_player', function ($j) {
                $j->on('club_player.player_id', '=', 'players.id')
                ->on('club_player.club_id', '=', 'leagues.club_id');
                // ->whereNull('club_player.deleted_at'); // if you soft-delete pivot
            })

            ->leftJoin('player_user', 'player_user.player_id', '=', 'players.id')
            ->leftJoin('users', 'users.id', '=', 'player_user.user_id')
            ->leftJoinSub($resultsSubquery, 'r', function ($join) {
                $join->on('contestants.id', '=', 'r.contestant_id')
                    ->on('contestants.division_id', '=', 'r.division_id');
            })
            ->where('league_sessions.id', $sessionId)
            ->selectRaw("
                tiers.id   AS tier_id,
                tiers.name AS tier_name,
                tiers.`index` AS tier_index,

                divisions.id      AS division_id,
                divisions.`index` AS division_index,
                divisions.promote_count,
                divisions.relegate_count,

                contestants.id          AS contestant_id,
                contestants.player_id,
                contestants.`index`     AS contestant_index,
                contestants.notified_at,
                (contestants.deleted_at IS NOT NULL) AS trashed,
                contestants.deleted_at  AS contestant_deleted_at,

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

                /* 🔄 NEW membership flags based on club_player pivot */
                MAX(CASE WHEN club_player.id IS NOT NULL THEN 1 ELSE 0 END) AS is_member,
                MAX(club_player.club_player_id) AS club_member_id,
                MAX(CASE WHEN player_user.user_id IS NOT NULL THEN 1 ELSE 0 END) AS has_user
            ")
            ->groupBy([
                'tiers.id','tiers.name','tiers.index',
                'divisions.id','divisions.index','divisions.promote_count','divisions.relegate_count',
                'contestants.id','contestants.player_id','contestants.index','contestants.notified_at','contestants.deleted_at',
                'players.first_name','players.last_name',
            ])
            ->orderByRaw('trashed ASC')
            ->orderByDesc('has_played')
            ->orderBy('contestant_index', 'ASC');

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

        return $rows->groupBy('tier_id')->map(function ($tierGroup) {
            $firstTier = $tierGroup->first();
            $tierName  = $firstTier->tier_name;
            $tierIndex = $firstTier->tier_index;

            $divisionsGrouped = $tierGroup->groupBy('division_id');
            $divisionCount    = $divisionsGrouped->count();

            $divisions = $divisionsGrouped->map(function ($divisionGroup) use ($tierIndex, $divisionCount) {
                $firstDivision = $divisionGroup->first();
                $index  = (int) $firstDivision->division_index;
                $letter = chr(97 + $index);

                return [
                    'id'              => $firstDivision->division_id,
                    'index'           => $index,
                    'division_letter' => $letter,
                    'name'            => 'Box '. ($tierIndex + 1). ($divisionCount > 1 ? '(' . $letter . ')' : ''),
                    'promote_count'   => (int) $firstDivision->promote_count,
                    'relegate_count'  => (int) $firstDivision->relegate_count,
                    'division_count'  => $divisionCount,
                    'standings'       => $divisionGroup->map(function ($row) {
                        $isMember = (bool) $row->is_member;

                        return [
                            'id'             => $row->contestant_id,
                            'player_id'      => $row->player_id,
                            'name'           => $row->first_name.' '.$row->last_name,
                            'notified_at'    => $row->notified_at,
                            'deleted_at'     => $row->contestant_deleted_at,

                            'played'         => (int) $row->total_played,
                            'rank'           => (int) $row->rank,
                            'won'            => (int) $row->total_won,
                            'drawn'          => (int) $row->total_drawn,
                            'lost'           => (int) $row->total_lost,
                            'for'            => (int) $row->total_for,
                            'against'        => (int) $row->total_against,
                            'diff'           => (int) $row->total_diff,
                            'points'         => (int) $row->total_points,
                            'trashed'        => (bool) $row->trashed,
                            'seed'           => (int) $row->contestant_index + 1,

                            // 🔄 membership + user flags from club_player / player_user
                            'is_member'      => $isMember,
                            'club_member_id' => $isMember ? (int) $row->club_member_id : null,
                            'has_user'       => (bool) $row->has_user,
                        ];
                    })->values(),
                ];
            });

            return [
                'id'             => $firstTier->tier_id,
                'name'           => $tierName,
                'index'          => $firstTier->tier_index,
                'division_count' => $divisions->count(),
                'divisions'      => $divisions->sortBy('index')->values(),
            ];
        })->sortBy('index')->toArray();
    }


}
