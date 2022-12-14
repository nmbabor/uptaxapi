<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTaxCollectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tax_collections', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('year');
            $table->foreign('year')->references('id')->on('years');
            $table->float('tax');
            $table->date('payment_date');
            $table->unsignedBigInteger('holding_id');
            $table->foreign('holding_id')->references('id')->on('holdings');
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users');
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
        Schema::dropIfExists('tax_collections');
    }
}
