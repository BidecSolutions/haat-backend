<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Str;

class UpdateUsernamesSeeder extends Seeder{
    public function run(){
        $users = User::whereNull('user_code')->orWhere('user_code', '')->get();

        foreach($users as $user){
            $baseCode = $this->generateUniqueCode();

            if(empty($baseCode)){
                $baseCode = 'USER-0000'; // fallback if generation fails
            }
            $user_code = $baseCode;
            $counter = 0;

            while(User::where('user_code', $user_code)->exists()){
                $user_code = $this->generateUniqueCode();
                $counter++;

                if($counter > 10){
                    $user_code = $this->generateUniqueCode();
                    break;
                }
            }
            $user->user_code = $user_code;
            $user->save();

            echo "Updated User ID {$user->id} with user_code: {$user_code}\n";
        }
        echo "✅ User codes updated successfully!\n";
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
        return substr($randomString, 0, 4) . '-' . substr($randomString, 4, 4);
    }
}