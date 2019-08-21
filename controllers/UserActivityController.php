<?php

namespace Modules\UserActivity\Controllers;

class UserActivityController extends \Extensions\Controller{
	public function install(){
		if(module('UserActivity') -> install()){
			return "<h3>Installation was success</h3>";
		}

		return "<h3>Installation was fail</h3>";
	}
}