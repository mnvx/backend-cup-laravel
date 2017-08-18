<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableLocation extends Migration
{
    protected $table = 'location';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
//        Schema::create($this->table, function (Blueprint $table) {
//            $table->increments('id');
//            $table->text('place');
//            $table->string('country', 50)->index();
//            $table->string('city', 50);
//            $table->integer('distance')->index();
//        });
        DB::statement("CREATE TABLE public.location
(
  id integer NOT NULL DEFAULT nextval('location_id_seq'::regclass),
  place text NOT NULL,
  country character varying(50) NOT NULL,
  city character varying(50) NOT NULL,
  distance integer NOT NULL,
  CONSTRAINT location_pkey PRIMARY KEY (id)
)
TABLESPACE ram;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->table);
    }
}
