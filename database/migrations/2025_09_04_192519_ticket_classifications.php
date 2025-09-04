<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('tickets_classifications', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('ticket_categories')->cascadeOnDelete();
            $table->text('explanation')->nullable();
            $table->decimal('confidence', 3, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_categories');
        Schema::dropIfExists('tickets_classifications');
    }
};
