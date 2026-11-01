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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            // relation ke projects
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            // basic task info
            $table->string('title');
            $table->text('description')->nullable();

            // status workflow
            $table->string('status')->default('todo');

            // assign user
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->date('due_date')->nullable();

            $table->timestamps(6);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
