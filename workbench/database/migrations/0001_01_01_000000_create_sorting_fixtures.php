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

        // Non standard primary key: proves the ownerKey parameter of
        // RelationOrder, which was hardcoded to 'id' in the host's original.
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
            // `order` is a reserved word in MySQL and PostgreSQL: it is the
            // column used to prove the grammar wrap in EnumOrder.
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
