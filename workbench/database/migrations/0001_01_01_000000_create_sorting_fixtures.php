<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authors', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
        });

        // Chiave primaria non standard: serve a provare il parametro ownerKey
        // di RelationOrder, che nell'originale dell'host era hardcoded a 'id'.
        Schema::create('tags', function (Blueprint $table): void {
            $table->string('code')->primary();
            $table->string('label');
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::create('books', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('author_id')->nullable();
            $table->string('tag_code')->nullable();
            $table->string('status')->nullable();
            // `order` e' parola riservata in MySQL e PostgreSQL: e' la colonna
            // su cui si prova il wrap del grammar in EnumOrder.
            $table->string('order')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('authors');
    }
};
