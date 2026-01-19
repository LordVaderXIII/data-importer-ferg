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
        Schema::create('basiq_users', function (Blueprint $table) {
            $table->id();
            $table->string('firefly_instance_url');
            $table->string('firefly_user_id')->nullable(); // For future multi-user support
            $table->string('basiq_user_id');
            $table->timestamps();

            $table->unique(['firefly_instance_url', 'firefly_user_id']);
        });

        Schema::create('basiq_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('basiq_user_id')->constrained('basiq_users')->onDelete('cascade');
            $table->string('connection_id');
            $table->string('institution_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('basiq_connections');
        Schema::dropIfExists('basiq_users');
    }
};
