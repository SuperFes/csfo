<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->date('date')->index();
            $table->string('category')->index();
            $table->string('status');
            $table->text('summary');
            $table->text('could_have');
            $table->text('context')->nullable();
            $table->text('schumer_response')->nullable();
            $table->json('sources');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};
