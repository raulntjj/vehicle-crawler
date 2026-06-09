<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Tabela de veículos rastreados.
 *
 * Armazena os dados limpos e transformados de cada anúncio
 * de veículo encontrado pelo crawler.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();

            // Identificador único do anúncio na fonte (ex: ID do OLX, Webmotors, etc.)
            $table->string('external_id')->unique();

            // Dados do anúncio (já transformados/limpos)
            $table->string('title');                    // Ex: "Honda City Hatch EXL 1.5 Flex Aut."
            $table->decimal('price', 12, 2);            // Ex: 95900.00
            $table->integer('km');                      // Ex: 24500
            $table->integer('year_fabrication');         // Ex: 2022
            $table->integer('year_model');               // Ex: 2023
            $table->string('url');                       // URL original do anúncio

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
