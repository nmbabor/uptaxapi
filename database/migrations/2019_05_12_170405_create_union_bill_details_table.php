<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUnionBillDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('union_bill_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('union_id');
            $table->foreign('union_id')->references('id')->on('unions');
            $table->string('chairman_name');
            $table->string('chairman_mobile');
            $table->string('bank_name');
            $table->string('branch_name');
            $table->date('bill_start_date');
            $table->date('bill_end_date');
            $table->string('signature');
            $table->text('details');
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
        Schema::dropIfExists('union_bill_details');
    }
}
