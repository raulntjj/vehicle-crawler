<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Adiciona coluna `source` à tabela `vehicles`.
 *
 * Motivação:
 * O pipeline ETL é multi-portal. Um mesmo `external_id` pode existir em
 * portais distintos (Mobiauto, Webmotors, etc.), portanto a unicidade
 * deve ser composta por `(external_id, source)` e não só por `external_id`.
 *
 * Alterações:
 * 1. Adiciona coluna `source` (string, default 'unknown')
 * 2. Remove o índice único antigo em `external_id` (simples)
 * 3. Cria índice único composto em `(external_id, source)`
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Adiciona a coluna de origem logo após external_id
            $table->string('source')->default('unknown')->after('external_id');

            // Remove o índice único simples anterior
            $table->dropUnique(['external_id']);

            // Índice único composto: mesmo ID pode existir em portais diferentes
            $table->unique(['external_id', 'source'], 'vehicles_external_id_source_unique');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Reverte: remove índice composto, recria simples, dropa coluna
            $table->dropUnique('vehicles_external_id_source_unique');
            $table->unique('external_id');
            $table->dropColumn('source');
        });
    }
};
