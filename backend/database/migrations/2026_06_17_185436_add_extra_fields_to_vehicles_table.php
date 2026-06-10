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
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('image')->nullable();
            $table->integer('doors')->nullable();
            $table->string('bodystyle')->nullable();
            $table->string('fuel')->nullable();
            $table->string('transmission')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['image', 'doors', 'bodystyle', 'fuel', 'transmission']);
        });
    }
};
