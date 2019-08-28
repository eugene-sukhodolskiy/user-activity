<?php

namespace Modules\UserActivity\Models;

class UserActivity extends \Extensions\Model{

	public $table = "UserActivity";

	public function default_rows(){
		return [];
	}

	/**
	 * Добавление новой активности пользователя, в основном не требует вызова. Работает по событию
	 *
	 * @method set_activity
	 *
	 * @param  [int] $user_id Идентификатор пользователя
	 * @param  [boolean] $action Флаг успешности добавления, в случае успешного добавления, будет возвращён id записи
	 */
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
			'date_of_create', '>', $time_offset_str,
			'AND',
			'date_of_create', '<', $time_period_str,
		];

		if($user_id !== false){
			$where = array_merge($where, ['AND', 'user_id', '=', $user_id]);
		}else{
			$where = array_merge($where, ['AND', 'user_id', '<>', '0']);
		}

		$order = ['id', 'DESC'];

		return $this -> get(compact('where', 'order'));
	}

	/**
	 * Стату пользователя, Онлайн или Оффлайн
	 *
	 * @method get_activity_status
	 *
	 * @param  [int] $user_id Идентификатор пользователя
	 *
	 * @return [boolean] Флаг активности
	 */
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

	/**
	 * Получить последнюю запись об активности пользователя
	 *
	 * @method get_last_activity
	 *
	 * @param  [int] $user_id Идентификатор пользователя
	 *
	 * @return [object] Объект записи
	 */
	public function get_last_activity($user_id){
		return $this -> one() -> user_id($user_id);
	}

	/**
	 * Общее количество пользователей онлайн
	 *
	 * @method total_online
	 *
	 * @return [int] Количество пользователей онлайн
	 */
	public function total_online(){
		$user_online_condition_per_second = module('UserActivity') -> user_online_condition * 60;
		$user_online_condition_per_second_str = date('Y-m-d H:i:s', time() - $user_online_condition_per_second);
		$sql_query_str = "SELECT COUNT(DISTINCT `user_id`) FROM `{$this -> table}` WHERE `date_of_create` > '{$user_online_condition_per_second_str}' AND `user_id` <> '0'";

		$result = $this -> q($sql_query_str);

		return $result[0]['COUNT(DISTINCT `user_id`)'];
	}

	/**
	 * Метод для получения списка пользователей онлайн
	 *
	 * @method online_list
	 *
	 * @return [array] возвращает список пользователей, каждый пользователь представлен в виде объекта
	 */
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

	/**
	 * Анализ активности отдельно взятого пользователя, за выбранный период
	 *
	 * @method get_user_absolute_activity
	 *
	 * @param  [int] $user_id [description]
	 * @param  [int] $time_offset сдвиг в прошлое на указанное количество минут
	 * @param  [int] $time_period количество времени за которое нужен анализ, в минутах
	 *
	 * @return [array] Ассоциативный массив с результатами анализа, многоуровневый. Все значения в секундах для точности
	 */
	public function get_user_absolute_activity($user_id, $time_offset, $time_period){
		$activity = $this -> get_activity($user_id, $time_offset, $time_period);
		$activity_periods = $this -> split_into_periods($activity);
		$activity_periods_times = $this -> period_time($activity_periods);
		$data = [
			'activity_list' => $activity,
			'periods' => [
				'items' => $activity_periods,
				'times' => $activity_periods_times
			],
			'total' => array_sum($activity_periods_times)
		];

		if($user_id === false){
			$data['unique_users'] = ['unique_users_list' => [], 'total_unique_users' => []];
			$data['unique_users']['unique_users_list'] = $this -> detect_unique_users($activity);
			$data['unique_users']['total_unique_users'] = count($data['unique_users']['unique_users_list']);
		}

		return $data;
	}

	/**
	 * Служебный метод для нахождения записей с уникальными id пользователя
	 *
	 * @method detect_unique_users
	 *
	 * @param  [array] $general_activity массив с активностями
	 *
	 * @return [array] масив с уникальными пользователями
	 */
	private function detect_unique_users($general_activity){
		$unique_users = [];
		foreach($general_activity as $i => $item){
			if(array_search($item -> user_id, $unique_users) !== false)
				continue;
			$unique_users[] = $item -> user_id;
		}

		return $unique_users;
	}

	/**
	 * Служебный метод для разделения активностей на периоды
	 *
	 * @method split_into_periods
	 *
	 * @param  [array] $activity_list Массив с активностями
	 *
	 * @return [array] Результирующий массив с активностями разделёнными на периоды
	 */
	private function split_into_periods($activity_list){
		$user_online_condition_per_second = module('UserActivity') -> user_online_condition * 60;
		$periods = [];
		$pinx = 0;
		$activity_list_count = count($activity_list);
		for($i=1; $i<$activity_list_count; $i++){
			$periods[$pinx][] = $activity_list[$i-1];
			if(strtotime($activity_list[$i-1] -> date_of_create) - strtotime($activity_list[$i] -> date_of_create) > $user_online_condition_per_second){
				$pinx++;
			}
		}

		return $periods;
	}

	/**
	 * Служебный метод. Формирование списка периодов на основе списка периодов активностей
	 *
	 * @method period_time
	 *
	 * @param  [array] $periods
	 *
	 * @return [array] 
	 */
	private function period_time($periods){
		$results = [];
		foreach($periods as $period){
			$results[] = strtotime($period[0] -> date_of_create) - strtotime($period[count($period) - 1] -> date_of_create);
		}

		return $results;
	}

	/**
	 * Определение глобальной активности, с привязкой ко времени и в абсолютных значениях
	 *
	 * @method get_general_absolute_activity
	 *
	 * @param  [int] $time_offset Временной отступ в днях
	 * @param  [int] $time_period Временной период в днях
	 *
	 * @return [array] Список активностей
	 */
	public function get_general_absolute_activity($time_offset, $time_period){
		return $this -> get_user_absolute_activity(false, $time_offset, $time_period);
	}

}
