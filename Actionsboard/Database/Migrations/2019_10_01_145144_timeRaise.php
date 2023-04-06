<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TimeRaise extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('worlds', function (Blueprint $table) {
        $table->integer('first_time_raise')->default(3);
        $table->integer('second_time_raise')->default(3);
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('worlds', function (Blueprint $table) {
          $table->dropColumn('first_time_raise');
          $table->dropColumn('second_time_raise');
      });
    }
}
