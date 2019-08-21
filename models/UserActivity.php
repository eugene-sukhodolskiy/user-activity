<?php

namespace Modules\UserActivity\Models;

class UserActivity extends \Extensions\Model{

	public $table = "UserActivity";

	public function default_rows(){
		return [];
	}

	public function set_activity($user_id, $action){
		$data = [
			'browser' => getBrowser($_SERVER['HTTP_USER_AGENT']),
			'os' => getOS($_SERVER['HTTP_USER_AGENT']),
			'ip' => $_SERVER['HTTP_CLIENT_IP'] ? $_SERVER['HTTP_CLIENT_IP'] : ($_SERVER['HTTP_X_FORWARDED_FOR'] ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']),
			'user_agent_src' => $_SERVER['HTTP_USER_AGENT'],
			'user_id' => $user_id,
			'action' => $action
		];

		return $this -> set($data);
	}

	/**
	 * [Получить активность выбранного пользователя за выбраный период]
	 *
	 * @method get_activity
	 *
	 * @param  [int] $user_id [Идентификатор пользователя]
	 * @param  [int] $time_offset [Сдвиг временного периода в днях]
	 * @param  [int] $time_period [Длинная выбранного временного периода в днях]
	 *
	 * @return [array] [Если есть данные по заданным условиям - будет возвращено массив из активностей]
	 */
	public function get_activity($user_id, $time_offset, $time_period){
		$time_offset_per_second = time() - ($time_offset * 60 * 60 * 24);
		$time_offset_str = date('Y-m-d H:i:s', $time_offset_per_second);
		$time_period_str = date('Y-m-d H:i:s', $time_offset_per_second + ($time_period * 60 * 60 * 24));
		$where = [
			'user_id', '=', $user_id,
			'AND',
			'date_of_create', '>', $time_offset_str,
			'AND',
			'date_of_create', '<', $time_period_str,
		];

		$order = ['id', 'DESC'];

		return $this -> get(compact('where', 'order'));
	}

	public function get_activity_status($user_id){
		$user_online_condition_per_second = module('UserActivity') -> user_online_condition * 60;
		$user_online_condition_per_second_str = date('Y-m-d H:i:s', time() - $user_online_condition_per_second);
		$where = [
			'user_id', '=', $user_id,
			'AND',
			'date_of_create', '>', $user_online_condition_per_second_str
		];

		if(!$this -> length($where)){
			return false;
		}

		return true;
	}

	public function get_last_activity($user_id){
		return $this -> one() -> user_id($user_id);
	}

	public function total_online(){
		$user_online_condition_per_second = module('UserActivity') -> user_online_condition * 60;
		$user_online_condition_per_second_str = date('Y-m-d H:i:s', time() - $user_online_condition_per_second);
		$sql_query_str = "SELECT COUNT(DISTINCT `user_id`) FROM `{$this -> table}` WHERE `date_of_create` > '{$user_online_condition_per_second_str}'";

		$result = $this -> q($sql_query_str);

		return $result[0]['COUNT(DISTINCT `user_id`)'];
	}

	public function online_list(){
		$user_online_condition_per_second = module('UserActivity') -> user_online_condition * 60;
		$user_online_condition_per_second_str = date('Y-m-d H:i:s', time() - $user_online_condition_per_second);
		$sql_query_str = "SELECT DISTINCT `user_id` FROM `{$this -> table}` WHERE `date_of_create` > '{$user_online_condition_per_second_str}'";
		$result = $this -> q($sql_query_str);
		foreach ($result as $i => $item) {
			$result[$i] = $this -> wrap_up($item);
		}
		
		return $result;
	}

}
