<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table): void {
            $table->dropUnique('submissions_submitted_by_email_unique');
            $table->index(['event_id', 'submitted_by_email'], 'submissions_event_email_index');
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table): void {
            $table->dropIndex('submissions_event_email_index');
            $table->unique('submitted_by_email');
        });
    }
};
