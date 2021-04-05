<?php
/**
 * Service entity
 *
 * @author Serhii Shkrabak
 * @global object $CORE->model
 * @package Model\Entities\Service
 */
namespace Model\Entities;

class Service
{
	use \Library\Shared;
	use \Library\Entity;

	public static function search(String $title = '', String $description = '', Int $id = 0, ?String $module = '',
			Int $status = 0, String $webhook = '', ?String $updated = '',
			?String $office = '', ?String $signature = '', ?Int $user = 0, Int $limit = 0):self|array|null {
		$result = [];
		$db = self::getDB();
		$messages = $db -> select(['Services' => []]);

		foreach (['id', 'signature'] as $var)
			if ($$var)
				$filters[$var] = $$var;
		if(!empty($filters))
			$messages->where(['Services'=> $filters]);

		foreach ($messages->many($limit) as $message) {
			$class = __CLASS__;
			$result[] = new $class($message['title'], $message['description'], $message['id'], $message['module'],
				$message['status'], $message['webhook'], $message['updated'], $message['office'], $message['user']);
		}
		return $limit == 1 ? (isset($result[0]) ? $result[0] : null) : $result;
	}

	public function save():self {
		$db = $this->db;
		if (!$this->id) {
			$insert = [
				'title' => $this->title,
				'description' => $this->description,
				'user' => $this->user,
			];
			if ($this->token) {
				$insert['token'] = $this->token;
				$insert['signature'] = $this->signature;
			}
			$this->id = $db -> insert([
				'Services' => $insert
			])->run(true)->storage['inserted'];
		}

		if ($this->_changed)
			$db -> update('Services', $this->_changed )
				-> where(['Services'=> ['id' => $this->id]])
				-> run();
		return $this;
	}


	public function __construct(public String $title, public String $description = '', public Int $id = 0, public ?String $module = '',
								public Int $status = 0, public ?String $webhook = '', public ?String $updated = '',
								public ?String $office = '', public ?Int $user = 0, public ?String $token = '' , public ?String $signature = '', ) {
		$this->db = $this->getDB();
	}
}