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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained();

            $table->string('submitted_by_email')->nullable();
            $table->string('submitted_by_name')->nullable();

            $table->enum('participation_type', ['individual', 'team']);
            $table->string('team_name')->nullable();

            $table->enum('status', ['submitted', 'approved', 'rejected', 'cancelled'])->default('submitted');
            $table->text('review_comment')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->dateTime('reviewed_at')->nullable();

            $table->json('form_answers')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
