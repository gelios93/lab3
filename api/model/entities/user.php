<?php
/**
 * User entity
 *
 * @author Serhii Shkrabak
 * @global object $CORE->model
 * @package Model\Entities
 */
namespace Model\Entities;

class User
{
	use \Library\Shared;
	use \Library\Entity;

	public static function search(?Int $chat, Int $limit = 0):self|array|null {
		$result = [];
		$db = self::getDB();
		foreach ($db -> select([
				'Users' => []
			])->where([
				'Users' => ['chat' => $chat]
			])->many($limit) as $user) {
				$class = __CLASS__;
				$result[] = new $class($user['id'], $chat, $user['guid'], $user['message'], $user['service'], $user['input']);
		}
		return $limit == 1 ? (isset($result[0]) ? $result[0] : null) : $result;
	}

	public function save():self {
		$db = $this->db;
		if (!$this->id)
			$this->id = $db -> insert([
				'Users' => [ 'chat' => $this->chat ]
			])->run(true)->storage['inserted'];

		if ($this->_changed)
			$db -> update('Users', $this->_changed )
				-> where(['Users'=> ['id' => $this->id]])
				-> run();
		return $this;
	}

	public function __construct(public Int $id = 0, public ?Int $chat = null, public ?String $guid = null, public ?Int $message = null, public ?Int $service = null, public String|Array|Null $input = '') {
		$this->db = $this->getDB();
		$this->input = $this->input ? json_decode($this->input, true) : [];
	}
}