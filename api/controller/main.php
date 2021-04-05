<?php
/**
 * User Controller
 *
 * @author Serhii Shkrabak
 * @global object $CORE
 * @package Controller\Main
 */

namespace Controller;
class Main
{
	use \Library\Shared;


	private $model;

	public function exec():?array {
		$result = null;
		$url = $this->getVar('REQUEST_URI', 'e');
		$path = explode('/', $url);

		if (isset($path[2]) && !strpos($path[1], '.')) { // Disallow directory changing
			$file = ROOT . 'model/config/methods/' . $path[1] . '.php';
			if (file_exists($file)) {
				include $file;
				if (isset($methods[$path[2]])) {
					$details = $methods[$path[2]];
					$request = [];
					$preError = null;
					$error = null;
					foreach ($details['params'] as $param) {
						$var = $this->getVar($param['name'], $param['source']);

						if($var==''){
							if($param['required']==true){
								$error =  new \Exception($param['name'], 1, $preError);
								$prevError = $error;
							}
							elseif($param['default']!=='')
								$var = $param['default'];
						}
						if($param['pattern'] !== ''){
							$var = $this->isCorrect($patterns[$param['pattern']], $var);
							if($var == null){
								$error =  new \Exception($param['name'], 2, $preError);
								$preError = $error;
							}
			
						}
						if ($var)
							$request[$param['name']] = $var;
					}
					if($error !== null) throw $error;

					if (method_exists($this->model, $path[1] . $path[2])) {
						$method = [$this->model, $path[1] . $path[2]];
						$result = $method($request);
					}

				}

			}
		}

		return $result;
	}

	private function isCorrect($name, $var):?String {
		if(!preg_match( $name['pattern'] , $var))
			return null;
		elseif(isset($name['replace']))
			$var = preg_replace($name['replace']['pattern'], $name['replace']['value'] , $var);
		return $var;
	}

	public function __construct() {
		// CORS configuration
		$origin = $this -> getVar('HTTP_ORIGIN', 'e');
		$front = $this -> getVar('FRONT', 'e');
		foreach ( [$front] as $allowed )
			if ( $origin == "https://$allowed") {
				header( "Access-Control-Allow-Origin: $origin" );
				header( 'Access-Control-Allow-Credentials: true' );
			}
		$this->model = new \Model\Main;
	}
}