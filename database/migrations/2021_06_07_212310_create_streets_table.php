<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStreetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('streets', function (Blueprint $table) {

            $table->unsignedBigInteger('id');
            $table->string('namespace');
            $table->string('version');

            $table->primary(['id', 'namespace']);

            $table->foreignId('municipality_id')->constrained();
            $table->string('municipality_version');

            $table->dateTime('valid_from');
            $table->string('name_fr')->nullable();
            $table->string('name_nl')->nullable();
            $table->string('name_de')->nullable();
            $table->string('status');
            $table->dateTime('status_valid_from');
            $table->string('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('streets');
    }
}
