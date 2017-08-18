<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableVisit extends Migration
{
    protected $table = 'visit';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
//        Schema::create($this->table, function (Blueprint $table) {
//            $table->increments('id');
//            $table->integer('location');
//            $table->integer('user');
//            $table->bigInteger('visited_at')->index();
//            $table->tinyInteger('mark');
//            $table->index(['user', 'location', 'visited_at']);
//            $table->index(['location', 'user']);
//        });
//        DB::statement('ALTER TABLE ' . $this->table . " ADD CHECK (visited_at >= 946674000 AND visited_at < 1420146000)");
//        DB::statement('ALTER TABLE ' . $this->table . " ADD CHECK (mark BETWEEN 0 AND 5)");

        DB::statement("CREATE TABLE public.visit
(
  id integer NOT NULL DEFAULT nextval('visit_id_seq'::regclass),
  location integer NOT NULL,
  \"user\" integer NOT NULL,
  visited_at integer NOT NULL,
  mark smallint NOT NULL,
  CONSTRAINT visit_pkey PRIMARY KEY (id),
  CONSTRAINT visit_mark_check CHECK (mark >= 0 AND mark <= 5),
  CONSTRAINT visit_visited_at_check CHECK (visited_at >= 946674000 AND visited_at < 1420146000)
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
