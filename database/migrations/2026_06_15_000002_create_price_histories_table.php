<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Tabela de histórico de preços.
 *
 * Registra cada alteração de preço detectada para um veículo,
 * mantendo um rastro temporal completo de variações.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_histories', function (Blueprint $table) {
            $table->id();

            // Relacionamento com o veículo
            $table->foreignId('vehicle_id')
                  ->constrained('vehicles')
                  ->cascadeOnDelete();

            // Preço registrado naquele momento
            $table->decimal('price', 12, 2);

            // Apenas created_at — não faz sentido "atualizar" um registro de histórico
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_histories');
    }
};
