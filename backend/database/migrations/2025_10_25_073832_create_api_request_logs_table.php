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
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('request_id')->unique();
            $table->string('method', 10);
            $table->string('path');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->integer('status_code')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->integer('response_size')->nullable();
            $table->bigInteger('memory_usage')->nullable();
            $table->text('error_message')->nullable();
            $table->enum('status', ['started', 'completed', 'error'])->default('started');
            $table->timestamps();
            
            $table->index(['method', 'path']);
            $table->index(['ip_address']);
            $table->index(['status_code']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
    }
};
