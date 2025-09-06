<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    public function run(): void
    {
        $ticketCategories = [
            'Billing',           // Payment issues, invoices, refunds
            'Technical',         // Software bugs, system errors, troubleshooting
            'Account',           // Login issues, account access, profile updates
            'Sales',             // Purchase inquiries, product info, upsells
            'General',           // Miscellaneous questions, feedback, non-specific requests
            'Feature request',   // Requests for new features or improvements
            'Bug report',        // Explicit software or system bugs
            'Security',          // Security concerns, phishing, account compromise
            'Shipping',          // Delivery, tracking, logistics issues
            'Cancellation',      // Subscription or order cancellations
            'Complaint',         // Customer complaints or dissatisfaction
        ];

        foreach ($ticketCategories as $category) {
            TicketCategory::query()->firstOrCreate(['name' => $category]);
        }
    }
}
