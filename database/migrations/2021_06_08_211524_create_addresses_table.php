<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('addresses', function (Blueprint $table) {

            $table->unsignedBigInteger('id');
            $table->string('namespace');
            $table->string('version');

            $table->primary(['id', 'namespace']);

            $table->decimal('longitude', 11, 8);
            $table->decimal('latitude', 10, 8);
            $table->point('location');
            $table->spatialIndex('location');

            $table->unsignedDecimal('epsg_31370_x', 9, 3);
            $table->unsignedDecimal('epsg_31370_y', 9, 3);

            $table->string('position_geometry_method');
            $table->string('position_specification');

            $table->string('house_number')->nullable();
            $table->string('sort_field')->nullable();
            $table->string('box_number')->nullable();

            $table->unsignedSmallInteger('postcode');

            $table->string('status');
            $table->dateTime('status_valid_from');

            $table->boolean('officially_assigned');

            // No explicit foreign key here because the relationship has
            // to be based on both the identifier AND the namespace.
            // This is because many IDs are used by more than a
            // single region.
            $table->unsignedBigInteger('street_id');
            $table->string('street_namespace');
            $table->string('street_version');

            $table->foreignId('municipality_id')->constrained('municipalities');
            $table->string('municipality_namespace');
            $table->string('municipality_version');

            $table->unsignedSmallInteger('postcode_id');
            $table->foreign('postcode_id')->references('id')->on('postcodes');
            $table->string('postcode_namespace');
            $table->string('postcode_version');

            $table->dateTime('valid_from');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('addresses');
    }
}
