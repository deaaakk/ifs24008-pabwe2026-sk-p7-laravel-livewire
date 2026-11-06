<?php

namespace App\Features\Stock\Policies;

use App\Features\Stock\Models\Stock;
use App\Models\User;

class StockPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Admin boleh melihat (read-only), member hanya miliknya. */
    public function view(User $user, Stock $stock): bool
    {
        return $user->isAdmin() || $this->owns($user, $stock);
    }

    public function create(User $user): bool
    {
        return $user->isMember();
    }

    /** Hanya pemilik (member) yang boleh mengubah. */
    public function update(User $user, Stock $stock): bool
    {
        return $user->isMember() && $this->owns($user, $stock);
    }

    public function delete(User $user, Stock $stock): bool
    {
        return $this->update($user, $stock);
    }

    private function owns(User $user, Stock $stock): bool
    {
        return (int) $stock->user_id === (int) $user->id;
    }
}
