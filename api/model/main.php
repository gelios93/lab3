<?php
/**
 * User Controller
 *
 * @author Serhii Shkrabak
 * @global object $CORE->model
 * @package Model\Main
 */
namespace Model;
class Main
{
	use \Library\Shared;

	public function formsubmitAmbassador(array $data):?array {
		// Тут модель повинна бути допрацьована, щоб використовувати бази даних, тощо
		$key = $this->getVar('TOCKEN', 'e'); // Ключ API телеграм
		if($key == '')
			throw new \Exception('key', 6);
		$result = null;
		$chat = 785442631;
		$text = "Нова заявка в *Цифрові Амбасадори*:\n" . $data['firstname'] . ' '. $data['secondname']. ', '. $data['position'] . "\n*Зв'язок*: " . $data['phone'] . "\n*Email*: " . $data['email'];
		$text = urlencode($text);
		$answer = file_get_contents("https://api.telegram.org/bot$key/sendMessage?parse_mode=markdown&chat_id=$chat&text=$text");
		$answer = json_decode($answer, true);
		$result = ['message' => $answer['result']];
		return $result;
	}

	public function cumwebhook(array $data):?array {
		if ($data['token'] == $this->getVar('TOCKEN_MAIN', 'e')) {
			//что-то
		} else
			throw new \Exception('TG token incorrect', 3);
		return [];
	}

	public function __construct() {

	}
}