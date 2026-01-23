<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AddMemberId extends Seeder
{
    public function run()
    {
        $users = User::whereNull('memberId')->orWhere('memberId', '')->get();

        foreach ($users as $user) {
            $baseId = $this->generateMemberId();

            if (empty($baseId)) {
                $baseId = 'USER-0000'; // fallback if generation fails
            }
            $memberId = $baseId;
            $counter = 0;

            while (User::where('memberId', $memberId)->exists()) {
                $memberId = $this->generateMemberId();
                $counter++;

                if ($counter > 10) {
                    $memberId = $this->generateMemberId();
                    break;
                }
            }
            $user->memberId = $memberId;
            $user->save();

            echo "Updated User ID {$user->id} with memberId: {$memberId}\n";
        }
        echo "✅ MemberId updated successfully!\n";
    }

    private function generateUniqueCode()
    {
        // Characters: digits + uppercase letters
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        // Generate 8 random characters
        $randomString = '';
        for ($i = 0; $i < 8; $i++) {
            $randomString .= $characters[random_int(0, strlen($characters) - 1)];
        }

        // Add dash in the middle (after 4 chars)
        return substr($randomString, 0, 4).'-'.substr($randomString, 4, 4);
    }

    private function generateMemberId()
    {
        // Get Year + Month (last 2 digits of year + month)
        $prefix = now()->format('ym'); // e.g. 2509 for Sept 2025

        // Generate 3 random uppercase letters
        $letters = strtoupper(Str::random(3));

        // Get the latest memberId number part and increment
        $lastUser = User::whereNotNull('memberId')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastUser && preg_match('/-(\d{4})$/', $lastUser->memberId, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1001; // starting point
        }

        // Ensure it's 4 digits (padded with leading zeros)
        $number = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$letters}{$number}";
    }
}
