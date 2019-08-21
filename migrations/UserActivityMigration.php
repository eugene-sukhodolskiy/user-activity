<?php

/* /migrations/ */
use Kernel\DBW;

class UserActivityMigration extends \Extensions\Migration{

	public function up(){
		// Create tables in db
		DBW::create('UserActivity',function($t){
			$t -> int('user_id')
			-> varchar('action', 200)
			-> varchar('ip', 100)
			-> varchar('browser', 200)
			-> varchar('os', 100)
			-> varchar('user_agent_src', 255)
			-> timestamp('date_of_create');
		});

		return true;
	}

	public function down(){
		// Drop tables from db
		DBW::drop('UserActivity');

		return true;
	}

}

