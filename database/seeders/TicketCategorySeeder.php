<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    public function run(): void
    {
        $ticketCategories = [
            'billing',           // Payment issues, invoices, refunds
            'technical',         // Software bugs, system errors, troubleshooting
            'account',           // Login issues, account access, profile updates
            'sales',             // Purchase inquiries, product info, upsells
            'general',           // Miscellaneous questions, feedback, non-specific requests
            'feature_request',   // Requests for new features or improvements
            'bug_report',        // Explicit software or system bugs
            'security',          // Security concerns, phishing, account compromise
            'shipping',          // Delivery, tracking, logistics issues
            'cancellation',      // Subscription or order cancellations
            'complaint',         // Customer complaints or dissatisfaction
        ];

        foreach ($ticketCategories as $category) {
            TicketCategory::query()->firstOrCreate(['name' => $category]);
        }
    }
}
