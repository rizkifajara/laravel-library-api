<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
       {
           // Reset the sequence for the authors table
           DB::statement('ALTER SEQUENCE authors_id_seq RESTART WITH 1');

           // Reset the sequence for the books table
           DB::statement('ALTER SEQUENCE books_id_seq RESTART WITH 1');
       }

    /**
    * Reverse the migrations.
    *
    * @return void
    */
    public function down()
    {
        // Optionally, you can set the sequences to a higher value if needed
        DB::statement('ALTER SEQUENCE authors_id_seq RESTART WITH 1000');
        DB::statement('ALTER SEQUENCE books_id_seq RESTART WITH 1000');
    }
};
