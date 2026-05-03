<?php

namespace App\Repositories;

use App\Models\HealthProfile;

class HealthProfileRepository
{
    public function findByUserId(int $userId): ?HealthProfile
    {
        return HealthProfile::where('user_id', $userId)->first();
    }

    public function create(array $data): HealthProfile
    {
        return HealthProfile::create($data);
    }

    public function update(HealthProfile $profile, array $data): HealthProfile
    {
        $profile->update($data);
        return $profile->fresh();
    }
}
