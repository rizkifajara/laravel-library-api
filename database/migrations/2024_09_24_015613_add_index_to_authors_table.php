<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->index('name');
        });
    }

    public function down()
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });
    }
};
