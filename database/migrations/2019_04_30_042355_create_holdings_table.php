<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHoldingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('holdings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->tinyInteger('type')->comment("1 = Residential, 2 = Commercial");
            $table->string('holding_no');
            $table->string('owner_name');
            $table->string('organization_name')->nullable();
            $table->string('father_or_husband');
            $table->string('mother');
            $table->string('mobile');
            $table->string('profession');
            $table->string('education')->nullable();
            $table->string('religion');
            $table->string('gender');
            $table->date('birthday');
            $table->string('nid');
            $table->string('got_social_benefits')->nullable();
            $table->string('get_social_benefits')->nullable();
            $table->string('eligible_for_social_benefits')->nullable();
            $table->string('tube_well')->nullable();
            $table->string('toilet')->nullable();

            $table->integer('house_unripe')->default(0);
            $table->integer('house_bhite_paka')->default(0);
            $table->integer('house_semi_ripe')->default(0);
            $table->integer('house_ripe')->default(0);

            $table->integer('annual_assessment');
            $table->integer('annual_tax');
            $table->integer('tax_due')->nullable();
            $table->string('others_bill_details')->nullable();
            $table->integer('others_bill')->nullable();
            $table->unsignedBigInteger('union_id');
            $table->foreign('union_id')->references('id')->on('unions');
            $table->integer('word');
            $table->unsignedBigInteger('village_id');
            $table->foreign('village_id')->references('id')->on('villages');
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
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
        Schema::dropIfExists('holdings');
    }
}
