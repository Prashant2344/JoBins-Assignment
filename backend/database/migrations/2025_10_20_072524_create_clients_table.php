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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('email');
            $table->string('phone_number');
            $table->boolean('is_duplicate')->default(false);
            $table->string('duplicate_group_id')->nullable(); // Groups duplicate records together
            $table->json('import_metadata')->nullable(); // Store import batch info, errors, etc.
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['company_name', 'email', 'phone_number']);
            $table->index('duplicate_group_id');
            $table->index('is_duplicate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
