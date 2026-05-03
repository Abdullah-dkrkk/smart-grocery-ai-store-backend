<?php

namespace App\Services;

use App\Models\HealthProfile;
use App\Repositories\HealthProfileRepository;

class HealthProfileService
{
    public function __construct(
        private HealthProfileRepository $repository
    ) {}

    public function updateOrCreate(int $userId, array $data): HealthProfile
    {
        $profile = $this->repository->findByUserId($userId);

        if ($profile) {
            return $this->repository->update($profile, $data);
        }

        $data['user_id'] = $userId;
        return $this->repository->create($data);
    }
}
