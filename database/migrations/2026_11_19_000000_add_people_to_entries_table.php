<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->renameColumn('schumer_response', 'statement');
        });

        Schema::table('entries', function (Blueprint $table) {
            $table->json('people')->nullable()->after('statement');
        });
    }

    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dropColumn('people');
        });

        Schema::table('entries', function (Blueprint $table) {
            $table->renameColumn('statement', 'schumer_response');
        });
    }
};
