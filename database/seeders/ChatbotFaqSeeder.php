<?php

namespace Database\Seeders;

use App\Models\ChatbotFaq;
use Illuminate\Database\Seeder;

class ChatbotFaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'What is Haat?',
                'answer' => 'Haat is Bangladesh\'s online marketplace where you can buy and sell items, cars, properties, find jobs, and book services — all in one place.',
                'sort_order' => 1,
            ],
            [
                'question' => 'How do I create a listing?',
                'answer' => 'Click "Start a Listing" or "Login" to sign in. Then choose your category (Marketplace, Motors, Jobs, Services, or Property) and fill in the details. Add photos and set your price.',
                'sort_order' => 2,
            ],
            [
                'question' => 'How can I contact support?',
                'answer' => 'You can reach us at support@haat.com or use the Contact Us page. We typically respond within 24 hours.',
                'sort_order' => 3,
            ],
            [
                'question' => 'Is Haat free to use?',
                'answer' => 'Creating an account and browsing listings is free. Some listing types may have fees. Check our How It Works page for details.',
                'sort_order' => 4,
            ],
            [
                'question' => 'How do I place a bid?',
                'answer' => 'Sign in, find the item you want, and click "Place Bid". Enter your bid amount. You\'ll be notified if you\'re outbid or if you win.',
                'sort_order' => 5,
            ],
        ];

        foreach ($faqs as $faq) {
            ChatbotFaq::updateOrCreate(
                ['question' => $faq['question']],
                array_merge($faq, ['is_active' => true])
            );
        }

        $this->command->info('Chatbot FAQs seeded.');
    }
}
