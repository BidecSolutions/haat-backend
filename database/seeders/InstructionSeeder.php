<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Instruction;

class InstructionSeeder extends Seeder
{
    public function run(): void
    {
        $instructions = [
            [
                'title' => 'How to Post a Listing',
                'description' => 'Step by step guide to create and publish your listing.',
                'image' => 'instructions/post-listing.png',
                'module' => 'marketplace',
                'position' => 1,
                'is_active' => true,
                'created_by' => 75,
            ],
            [
                'title' => 'How to Place a Bid',
                'description' => 'Learn how to participate in auctions by placing manual or auto bids.',
                'image' => 'instructions/place-bid.png',
                'module' => 'motors',
                'position' => 2,
                'is_active' => true,
                'created_by' => 75,
            ],
            [
                'title' => 'How to Contact Seller',
                'description' => 'Instructions on messaging and negotiating with sellers.',
                'image' => 'instructions/contact-seller.png',
                'module' => 'marketplace',
                'position' => 3,
                'is_active' => true,
                'created_by' => 75,
            ],
            [
                'title' => 'Payment Guidelines',
                'description' => 'Secure ways to complete transactions safely.',
                'image' => 'instructions/payment-guidelines.png',
                'module' => 'general',
                'position' => 4,
                'is_active' => true,
                'created_by' => 75,
            ],
            [
                'title' => 'Account Verification',
                'description' => 'Steps to verify your identity for secure transactions.',
                'image' => 'instructions/account-verification.png',
                'module' => 'general',
                'position' => 5,
                'is_active' => true,
                'created_by' => 75,
            ],
            [
                'title' => 'Reporting a Problem',
                'description' => 'How to report issues or disputes with a seller or buyer.',
                'image' => 'instructions/report-problem.png',
                'module' => 'support',
                'position' => 6,
                'is_active' => true,
                'created_by' => 75,
            ],
        ];

        foreach ($instructions as $instruction) {
            Instruction::create($instruction);
        }
    }
}
