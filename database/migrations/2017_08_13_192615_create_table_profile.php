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
        Schema::create($this->table, function (Blueprint $table) {
            $table->increments('id');
            $table->string('email', 100);
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('gender', 1);
            $table->unsignedInteger('birth_date');
        });
        DB::statement('ALTER TABLE ' . $this->table . " ADD CHECK (gender IN ('m', 'f'))");
        DB::statement('ALTER TABLE ' . $this->table . " ADD CHECK (birth_date >= -1262311200 AND birth_date < 915224400)");
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
