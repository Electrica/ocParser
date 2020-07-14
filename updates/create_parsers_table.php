<?php namespace Electrica\Parser\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateParsersTable extends Migration
{
    public function up()
    {
        Schema::create('electrica_parser_parsers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->text('name');
            $table->text('link');
            $table->text('pagination');
            $table->text('from');
            $table->text('to');
            $table->text('base_params');
            $table->json('params');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('electrica_parser_parsers');
    }
}
