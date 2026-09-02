<?php

use App\Enums\StatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Single table by design — no status-history, no separate companies table.
     */
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();

            $table->string('company');
            $table->string('role');
            $table->string('posting_url')->nullable();
            $table->string('source')->nullable();

            $table->string('status')->default(StatusEnum::Queued->value);
            $table->string('tier')->nullable();

            $table->date('applied_at')->nullable();

            $table->string('next_action')->nullable();
            $table->date('next_action_due')->nullable();

            $table->text('notes')->nullable();

            $table->string('resume_path')->nullable();
            $table->string('cover_letter_path')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('tier');
            $table->index('next_action_due');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
