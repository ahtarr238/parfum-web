<?php

namespace App\Services;

use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Menjaga aturan: selama user punya alamat, tepat satu alamat berstatus utama.
 */
class AddressBook
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function add(User $user, array $data): Address
    {
        return DB::transaction(function () use ($user, $data) {
            $hasAny = $user->addresses()->lockForUpdate()->exists();
            $makeDefault = ! $hasAny || ! empty($data['is_default']);

            $address = $user->addresses()->create([...$data, 'is_default' => false]);

            if ($makeDefault) {
                $this->setDefault($address);
            }

            return $address->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Address $address, array $data): Address
    {
        return DB::transaction(function () use ($address, $data) {
            $address->update(collect($data)->except('is_default')->all());

            if (! empty($data['is_default'])) {
                $this->setDefault($address);
            }

            return $address->refresh();
        });
    }

    public function makeDefault(Address $address): void
    {
        DB::transaction(fn () => $this->setDefault($address));
    }

    public function remove(Address $address): void
    {
        DB::transaction(function () use ($address) {
            $user = $address->user;
            $wasDefault = $address->is_default;

            $address->delete();

            if ($wasDefault) {
                $next = $user->addresses()->latest('created_at')->latest('id')->first();

                if ($next) {
                    $this->setDefault($next);
                }
            }
        });
    }

    private function setDefault(Address $address): void
    {
        Address::where('user_id', $address->user_id)
            ->whereKeyNot($address->getKey())
            ->update(['is_default' => false]);

        $address->forceFill(['is_default' => true])->save();
    }
}
