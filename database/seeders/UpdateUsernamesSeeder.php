<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Str;

class UpdateUsernamesSeeder extends Seeder
{
    public function run()
    {
        // Fetch all users who don't have a username
        $users = User::whereNull('username')->orWhere('username', '')->get();

        foreach ($users as $user) {
            // Start with name + first_name (fallback to only name if first_name is missing)
            $baseUsername = Str::slug($user->name . $user->first_name, '_'); 

            if (empty($baseUsername)) {
                $baseUsername = 'user'; // fallback if both are empty
            }

            $username = $baseUsername;
            $counter = 0;

            // Ensure uniqueness
            while (User::where('username', $username)->exists()) {
                $username = $baseUsername . '_' . rand(1000, 9999);
                $counter++;

                // Safety to avoid infinite loop
                if ($counter > 10) {
                    $username = $baseUsername . '_' . Str::random(6);
                    break;
                }
            }

            // Update the user
            $user->username = $username;
            $user->save();

            echo "Updated User ID {$user->id} with username: {$username}\n";
        }

        echo "✅ Usernames updated successfully!\n";
    }
}
