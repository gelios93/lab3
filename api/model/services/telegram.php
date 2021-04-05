<?php
/**
 * Telegram communication serice
 *
 * @author Serhii Shkrabak
 * @package Library\Telegram
 */
namespace Model\Services;
class Telegram
{
	use \Library\Shared;

	private ?Int $chat;

	public function send(String $text, Int $chat = 0, Array $keyboard = [], Int $reload = 0) {
		if (!$chat)
			$chat = $this->chat;
		$method = 'sendMessage';
		$reply = '';
		if (!empty($keyboard)) {
			$reply = '&reply_markup=';
			$reply .= json_encode( [
				'inline_keyboard' => $keyboard,
				'one_time_keyboard' => true,
				'resize_keyboard' => false
			] );
		}
		if ( $reload ) {
			$method = 'editMessageText';
		}
		$text = urlencode($text);
		file_get_contents("https://api.telegram.org/bot{$this->key}/$method?parse_mode=markdown&chat_id=$chat&text=$text" . ($reload ? "&message_id=$reload" : '') . $reply );
	}

	public function alert(String $body = '') {
		$this->send("Батут зломався: $body", $this->emergency);
	}

	public function setChat(Int $id):self {
		$this->chat = $id;
		return $this;
	}

	private function getReply(String $code):string {
		$db = $this->db;
		$reply = $db -> select(['Messages' => []])
					-> where(['Messages'=> ['code' => $code]])
					-> many();
		if (empty($reply))
			$reply = $this->getReply('unknown');
		else {
			$reply = $reply[mt_rand (0,count($reply)-1)];
		}
		return $reply['text'];
	}

	private function getContext(\Model\Entities\User $user, String $text, ?String $entrypoint = null):string {
		$message = \Model\Entities\Message::search(id: $user->message, limit: 1);
		$fields = $message->getChildren();
		$input = $user->input;
		$response = '';
		$full = false;

		foreach ( $fields as $field ) {
			if (!isset($input[$field->code])) {
				if (!$full) {
					$input[$field->code] = $text;
					$full = true;
				}
				else {
					$response = ( $field->title ? '*' . $field->title . "*\n\n" : '') . $field->text;
					break;
				}
			}
		}

		$update = ['input' => $input];
		if (count($input) == count($fields)) {
			$update['message'] = null;
			if($message->service) {
				$service = \Model\Entities\Service::search(id: $message->service, limit: 1);
				$service = new \Model\Entities\Service($input['s-title'], $input['s-description'], user: $user->id,
					token: $this->generateToken(32), signature: $this->generateToken(32));
				$service->save();
				$response = "✅ *ЗГЕНЕРОВАНО КЛЮЧІ*\n\nВикористовуйте для інтеграції наступні ключі:\n\n🔐 *Токен:* " . $service->token
						. "\nТокен призначено для верифікації запитів до Вашого сервісу\n\n🖇 *Підпис:* " . $service->signature
						. "\nПідпис призначено для виконання запитів з Вашого сервісу до Єдиних інформаційних систем\n";
			}
		}
		$user->set($update);
		return $response;
	}

	public function process(Array $entrypoint, String $terminal = '', Bool $edited = false) {
		if (!isset($this->chat))
			$this->setChat($entrypoint['chat']['id']);

		$db = $this->db;
		$response = '';

		$user = \Model\Entities\User::search(chat: $this->chat, limit: 1);

		if (!$user) {
			$user = new \Model\Entities\User(chat: $this->chat);
			$user->save();
		}

		$text = $entrypoint['text'];
		$keyboard = [];
		$reload = 0;
		$type = 0;

		if ($terminal) {
			$command = json_decode($terminal, true);
			$entry = $command['entry'];
			$type = $command['type'];
			if ($command['reload']) {
				$reload = $entrypoint['message_id'];
			}

		}

		switch ($type) {
			case 2: // Вводиться форма
				$message = \Model\Entities\Message::search(id: $command['id'], limit: 1);
				if ($entrypoint) {
					$user->set([
						'message' => $message->id,
						'input' => []
					]);
					$field = $message->getChildren(1);
					$response = ( $field->title ? '*' . $field->title . "*\n\n" : '') . $field->text;
				}
				break;
			default:
				if ($user->message)
					$response = $this->getContext($user, $text);
				else {
					$message = \Model\Entities\Message::search(entrypoint: isset($entry) ? $entry : $text, limit: 1);
					if ($message) {
						$response = ($message->title ? '*' . $message->title . "*\n\n" : '') . $message->text;
						$keyboard = $message->getKeyboard();
					} else {
						$response = $this->getReply('unknown');
					}
				}
		}
		if ($response)
			$this->send($response, keyboard: $keyboard, reload: $reload);

		return [];
	}

	public function __construct(private String $key, private Int $emergency) {
		$this->db = $this->getDB();
	}

}