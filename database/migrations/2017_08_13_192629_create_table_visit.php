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
        Schema::create($this->table, function (Blueprint $table) {
            $table->increments('id');
            $table->integer('location')->index();
            $table->integer('user')->index();
            $table->unsignedInteger('visited_at');
            $table->tinyInteger('mark');
        });
        DB::statement('ALTER TABLE ' . $this->table . " ADD CHECK (visited_at >= 946674000 AND visited_at < 1420146000)");
        DB::statement('ALTER TABLE ' . $this->table . " ADD CHECK (mark BETWEEN 0 AND 5)");
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
