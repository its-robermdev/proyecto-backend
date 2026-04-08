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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('type', [
                'hackathon',
                'bootcamp',
                'workshop',
                'conference',
                'job_fair',
                'other',
            ]);
            $table->enum('modality', ['online', 'in-person', 'hybrid']);
            $table->text('description');
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->dateTime('registration_deadline');
            $table->integer('capacity');
            $table->boolean('requires_approval')->default(false);
            $table->boolean('allows_teams')->default(false);
            $table->enum('status', ['draft', 'published', 'closed', 'cancelled', 'archived'])->default('draft');
            $table->json('form_schema')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
