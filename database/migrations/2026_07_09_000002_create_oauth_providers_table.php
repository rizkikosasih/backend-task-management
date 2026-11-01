<?php

declare(strict_types=1);

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
        Schema::create('oauth_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider'); // 'google', 'github'
            $table->string('provider_id'); // OAuth provider's user ID
            $table->string('email'); // Email from OAuth provider
            $table->json('profile_data')->nullable(); // Store name, avatar, etc.
            $table->timestamp('connected_at')->useCurrent();
            $table->timestamps();

            $table->unique(['provider', 'provider_id']);
            $table->index(['provider', 'provider_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_providers');
    }
};
