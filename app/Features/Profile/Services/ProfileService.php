<?php

namespace App\Features\Profile\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    /** @param array<string, mixed> $data */
    public function updateProfile(User $user, array $data): User
    {
        $user->update(Arr::only($data, ['name', 'username', 'email']));

        return $user;
    }

    public function updatePassword(User $user, string $password): void
    {
        $user->update(['password' => $password]);
    }

    public function updatePhoto(User $user, UploadedFile $file): User
    {
        $path = $file->store('profile-photos', 'public');
        $this->deletePhotoFile($user);
        $user->update(['profile_photo_path' => $path]);

        return $user;
    }

    public function removePhoto(User $user): User
    {
        $this->deletePhotoFile($user);
        $user->update(['profile_photo_path' => null]);

        return $user;
    }

    private function deletePhotoFile(User $user): void
    {
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }
    }
}
