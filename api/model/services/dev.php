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
	use \Library\Telegram;

	private Int $TGEmergencyChat = 280751679;

	public function __construct(private String $TGKey, private \Library\MySQL $db) {}
}