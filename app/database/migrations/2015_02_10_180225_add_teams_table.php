<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTeamsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('teams', function($table)
		{	
		    $table->increments('id');

		    $table->string('event_code', 10)->nullable();
		    $table->integer('team_code'); //Add manually

		    //Unique used just to confirm consistency.
		    $table->unique(array('event_code','team_code'));

		    //Team owner registration id, add owner to team members separately.
		    $table->integer('owner_id')->unsigned();
		    $table->foreign('owner_id')->references('id')->on('registrations');

		    $table->integer('confirmation')->default(0);

		    $table->timestamp('created_at');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('teams');
	}

}