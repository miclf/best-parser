<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMunicipalitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('municipalities', function (Blueprint $table) {

            $table->unsignedBigInteger('id')->primary();
            $table->string('name_fr')->nullable();
            $table->string('name_nl')->nullable();
            $table->string('name_de')->nullable();
            $table->unsignedMediumInteger('nis_code')->index();
            $table->string('namespace');
            $table->string('version');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('municipalities');
    }
}
