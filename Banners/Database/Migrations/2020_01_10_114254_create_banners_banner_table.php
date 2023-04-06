<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBannersBannerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('banners_banner', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->boolean('published');
            $table->string('title');
            $table->string('placement');
            $table->string('page');
            $table->string('type');
            $table->string('language_id');
            $table->boolean('visible_owner');
            $table->boolean('visible_user');

            $table->string('link')->nullable();
            $table->longtext('html')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

        });

        Schema::table('banners_banner', function (Blueprint $table) {

            $table->foreign('language_id')->references('id')->on('languages');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('banners_banner');
    }
}
