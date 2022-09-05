<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTaxListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tax_lists', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('year');
            $table->float('tax');
            $table->integer('type')->comment(" 1 = Residensial, 2 = Commercial ");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tax_lists');
    }
}
