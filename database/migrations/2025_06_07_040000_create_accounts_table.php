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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['bank', 'credit_card', 'cash', 'savings', 'investment', 'loan', 'other']);
            $table->string('currency', 3)->default('USD');
            $table->decimal('initial_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->string('color', 7)->default('#3B82F6');
            $table->string('icon', 50)->default('credit-card');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('include_in_total')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'include_in_total']);
            
            // Unique constraint
            $table->unique(['user_id', 'name'], 'accounts_user_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};