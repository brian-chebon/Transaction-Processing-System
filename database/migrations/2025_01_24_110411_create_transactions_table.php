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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('restrict');
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['credit', 'debit']);
            $table->string('description')->nullable();
            $table->string('reference')->unique();
            $table->enum('status', ['pending', 'completed', 'failed', 'reversed', 'archived'])
                ->default('pending');
            $table->decimal('balance_after', 15, 2);
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('account_id');
            $table->index('type');
            $table->index('status');
            $table->index('created_at');
            $table->index(['account_id', 'created_at']);
            $table->index(['account_id', 'type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
