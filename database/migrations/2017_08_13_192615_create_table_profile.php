<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableProfile extends Migration
{

    protected $table = 'profile';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
//        Schema::create($this->table, function (Blueprint $table) {
//            $table->increments('id');
//            $table->string('email', 100);
//            $table->string('first_name', 50);
//            $table->string('last_name', 50);
//            $table->string('gender', 1);
//            $table->bigInteger('birth_date')->index();
//        });
//        DB::statement('ALTER TABLE ' . $this->table . " ADD CHECK (gender IN ('m', 'f'))");
//        DB::statement('ALTER TABLE ' . $this->table . " ADD CHECK (birth_date >= -1262311200 AND birth_date < 915224400)");

        DB::statement("public.profile
(
  id integer NOT NULL DEFAULT nextval('profile_id_seq'::regclass),
  email character varying(100) NOT NULL,
  first_name character varying(50) NOT NULL,
  last_name character varying(50) NOT NULL,
  gender character varying(1) NOT NULL,
  birth_date integer NOT NULL,
  CONSTRAINT profile_pkey PRIMARY KEY (id),
  CONSTRAINT profile_birth_date_check CHECK (birth_date >= '-1262311200'::integer AND birth_date < 915224400),
  CONSTRAINT profile_gender_check CHECK (gender::text = ANY (ARRAY['m'::character varying, 'f'::character varying]::text[]))
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
