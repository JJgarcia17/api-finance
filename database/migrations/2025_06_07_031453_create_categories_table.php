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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->enum('type', ['income', 'expense']);
            $table->string('color', 7)->default('#6366F1'); // Color hexadecimal
            $table->string('icon', 50)->default('category'); // Nombre del ícono
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para optimizar consultas
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'is_active']);
            
            // Constraint único: un usuario no puede tener dos categorías con el mismo nombre y tipo
            $table->unique(['user_id', 'name', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};