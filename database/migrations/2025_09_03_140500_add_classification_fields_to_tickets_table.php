<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->text('classification_explanation')->nullable()->after('note');
            $table->decimal('classification_confidence', 3, 2)->nullable()->after('classification_explanation');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['classification_explanation', 'classification_confidence']);
        });
    }
};
