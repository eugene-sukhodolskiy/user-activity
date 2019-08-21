<?php

namespace Modules\UserActivity;
use Kernel\Module;
use Kernel\Maker\Maker;

class UserActivity{
	public $p2m;
	public $module_name;

	/**
	 * [Условие при котором пользователь считается онлайн (в минутах)]
	 *
	 * @var integer
	 */
	public $user_online_condition = 10;

	public function __construct(){
		$this -> module_name = "UserActivity";
		$this -> p2m = Module::pathToModule($this -> module_name);

		include_once($this -> p2m . 'utils.php');
		include_once($this -> p2m . 'UserActivity.routes.map.php');
		user_activity_routes_map();
		$this -> processing();
	}

	public function install(){
		if(SLT_DEBUG == 'off'){
			return false;
		}
		if(Maker::migration_up('UserActivity', $this -> p2m . 'migrations/', false)){
			return true;
		}

		return false;
	}

	private function processing(){
		on_event('call_action', function($action){
			$user_id = is_signined() ? current_signin_id() : '0';
			$action_string = implode('@', [$action['controller'], $action['action']]) . ':' . $action['method'];
			\Modules\UserActivity\Models\UserActivity::ins() -> set_activity($user_id, $action_string);
		});
	}

	public function get_activity($user_id, $time_offset, $time_period){
		return \Modules\UserActivity\Models\UserActivity::ins() -> get_activity($user_id, $time_offset, $time_period);
	}

	public function get_activity_status($user_id){
		return \Modules\UserActivity\Models\UserActivity::ins() -> get_activity_status($user_id);
	}

	public function get_last_activity($user_id){
		return \Modules\UserActivity\Models\UserActivity::ins() -> get_last_activity($user_id);
	}	

	public function total_online(){
		return \Modules\UserActivity\Models\UserActivity::ins() -> total_online();
	}

	public function online_list(){
		return \Modules\UserActivity\Models\UserActivity::ins() -> online_list();
	}

}