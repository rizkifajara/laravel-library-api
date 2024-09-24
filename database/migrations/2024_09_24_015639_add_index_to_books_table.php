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
        Schema::table('books', function (Blueprint $table) {
            $table->index('title');
            $table->index('author_id');
        });
    }

    public function down()
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['title']);
            $table->dropIndex(['author_id']);
        });
    }
};
