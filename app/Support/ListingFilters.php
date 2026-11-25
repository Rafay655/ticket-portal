<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ListingFilters
{
    public static function search(Request $request): string
    {
        $value = $request->query('search', '');
        return is_string($value) ? mb_substr(trim($value), 0, 200) : '';
    }

    public static function perPage(Request $request): int|string
    {
        $value = $request->query('perpage', '10');
        return in_array($value, ['5', '10', '20', '50', 'all', 5, 10, 20, 50], true) ? ($value === 'all' ? 'all' : (int) $value) : 10;
    }

    public static function apply(Builder $query, Request $request, array $columns, array $relations = []): Builder
    {
        $search = self::search($request);
        if ($search === '') return $query;
        return $query->where(function (Builder $group) use ($columns, $relations, $search) {
            foreach ($columns as $column) {
                $group->orWhere($column, 'like', '%'.$search.'%');
            }
            foreach ($relations as $relation => $fields) {
                $group->orWhereHas($relation, function (Builder $related) use ($fields, $search) {
                    $related->where(function (Builder $terms) use ($fields, $search) {
                        foreach ($fields as $field) $terms->orWhere($field, 'like', '%'.$search.'%');
                    });
                });
            }
        });
    }

    public static function paginate(Builder $query, Request $request, array $columns, array $relations = [])
    {
        self::apply($query, $request, $columns, $relations);
        $size = self::perPage($request);
        if (!$query->getQuery()->orders) $query->orderBy($query->getModel()->qualifyColumn('id'));
        return $query->paginate($size === 'all' ? max(1, (clone $query)->count()) : $size)->appends($request->except('page'));
    }

    public static function tickets(Builder $query, Request $request): Builder
    {
        return self::apply($query, $request, ['id', 'description', 'status', 'created_at'], [
            'tickettype' => ['name'], 'ticketCategory' => ['name', 'code'],
            'ticketsubtype' => ['name', 'priority'], 'requestedby' => ['name'], 'assignto' => ['name'],
        ]);
    }
}
