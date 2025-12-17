<?php
namespace App\Models\Traits;

use Illuminate\Support\Facades\DB;

trait Sortable
{
    public static function bootSortable()
    {
        static::addGlobalScope(function ($query) {
            return $query->orderBy('index');
        });

        static::deleting(function ($model) {
            $model->displace();
        });
    }

    public function move($index)
    {
        DB::transaction(function () use ($index) {
            $current = $this->index;
            $after = $index;

            if ($current === $after) return;

            $this->update(['index' => -1]);

            $block = $this->sortableQuery($this)->whereBetween('index', [
                min($current, $after),
                max($current, $after),
            ]);

            $needToShiftBlockUpCosDraggingTargetDown = $current < $after;

            $needToShiftBlockUpCosDraggingTargetDown
                ? $block->decrement('index')
                : $block->increment('index');

            $this->update(['index' => $after]);
        });
    }

    public function displace()
    {
        $this->move(9999999);
    }
}