<?php

namespace App\Services;

use App\Models\Region;
use App\Models\User;

class RegionalPocContacts
{
    public function __construct(private array $contacts)
    {
    }

    public function forUser(User $user): array
    {
        $employee = $user->employee;
        $ids = $this->regionIds($employee?->region_ids);
        if ($ids === []) {
            $ids = $this->regionIds($user->region_ids);
        }
        if ($ids === []) {
            $ids = $this->regionIds($employee?->region_id ?? $user->region_id);
        }
        if ($ids === []) {
            $ids = $this->regionIds($employee?->sub_region_id ?? $user->sub_region_id);
        }

        $contacts = [];
        $visited = [];
        while ($ids !== []) {
            $ids = array_values(array_diff($ids, $visited));
            if ($ids === []) {
                break;
            }
            $visited = array_merge($visited, $ids);
            $regions = Region::whereIn('id', $ids)->get(['id', 'name', 'p_region_id']);
            $ids = [];
            foreach ($regions as $region) {
                $matches = $this->forRegionNames([$region->name]);
                foreach ($matches as $contact) {
                    $contacts[$contact['email']] = $contact;
                }
                // Resolve a sub-region through its parent, only if it has no direct match.
                if ($matches === [] && $region->p_region_id) {
                    $ids[] = (int) $region->p_region_id;
                }
            }
        }

        return array_values($contacts);
    }

    public function forRegionNames(array $names): array
    {
        $regions = [];
        foreach ($names as $name) {
            foreach (explode(',', (string) $name) as $part) {
                $regions[] = $this->normalize($part);
            }
        }

        $matches = [];
        foreach ($this->contacts as $contact) {
            $covered = array_map(fn ($name) => $this->normalize($name), $contact['regions']);
            if (array_intersect($regions, $covered) !== []) {
                $matches[$contact['email']] = $contact;
            }
        }

        return array_values($matches);
    }

    private function normalize(string $name): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(trim($name)));
    }

    private function regionIds($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : explode(',', $value);
        }

        return array_values(array_unique(array_map('intval', array_filter(
            (array) $value,
            fn ($id) => is_scalar($id) && ctype_digit(trim((string) $id)) && (int) $id > 0
        ))));
    }
}
