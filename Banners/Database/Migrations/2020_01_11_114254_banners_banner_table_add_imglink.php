<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BannersBannerTableAddImglink extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('banners_banner', function (Blueprint $table) {
            $table->longtext('imglink')->nullable()->after('html');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('banners_banner', function (Blueprint $table) {
            $table->dropColumn('imglink');
        });
    }
}
