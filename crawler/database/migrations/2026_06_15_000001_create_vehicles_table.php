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
            $table->string('external_id');

            // Marca e modelo separados para indexação e consultas eficientes
            $table->string('brand');                    // Ex: "Honda"
            $table->string('model');                    // Ex: "Civic"

            // Dados do anúncio (já transformados/limpos)
            $table->string('title');                    // Ex: "Honda Civic EX 2.0 i-VTEC CVT"
            $table->decimal('price', 12, 2);            // Ex: 134900.00
            $table->integer('km');                      // Ex: 53008
            $table->integer('year_fabrication');         // Ex: 2021
            $table->integer('year_model');               // Ex: 2021
            $table->string('url');                       // URL original do anúncio

            // Fonte de dados (ex: "mobiauto")
            $table->string('source');

            // Campos adicionais
            $table->json('images')->nullable();
            $table->integer('doors')->nullable();
            $table->string('bodystyle')->nullable();
            $table->string('fuel')->nullable();
            $table->string('transmission')->nullable();

            $table->timestamps();

            // Unicidade garantida pela combinação de ID Externo e Fonte
            $table->unique(['external_id', 'source'], 'vehicles_external_id_source_unique');

            // Índices de busca
            $table->index('brand');
            $table->index('model');
            $table->index('source');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
