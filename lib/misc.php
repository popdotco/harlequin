<?
class HarlequinMisc {
	static function abstractRequestType() {
		return $_SERVER['REQUEST_METHOD'];
	}
	static function cleanupSelector($sel) {
		return preg_replace('/^\.+/', '', $sel);
	}
	static function decodePHPExpression($str) {
		return preg_replace_callback('/\'__PHP\((.+)\)__\'/i', 
			create_function('$match', 'return base64_decode($match[1]);'),
			$str);
	}
	static function encodePHPExpression($str) {
		return '__PHP(' . base64_encode($str) . ')__';
	}
	// iterate through $array, replacing values of callables with their result
	static function expand($array) {
		$out = array();
		foreach ($array as $k=>$v) {
			$out[$k] = is_callable($v) ? $v() : $v;
		}
		return $out;
	}
	static function jsonEncodeBody() {
		if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json')!==false) {
			return true;
		} else {
			return false;
		}
	}
	static function isSSL() {
		$s = $_SERVER;
		return (isset($s['HTTP_X_FORWARDED_PROTO']) && $s['HTTP_X_FORWARDED_PROTO'] === 'https') 
						|| isset($s['HTTPS']) 
						|| isset($s['FRONT-END-HTTPS']);
	}
	static function output($class, $msg) {
		echo HarlequinAnsi::color($class).$msg."\n";
	}
	static function post() {
		$p = self::jsonEncodeBody() || empty($_POST) ? json_decode(file_get_contents('php://input'), true) : $_POST;
		return $p;
	}
	static function requestData() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			return self::post();
		} else {
			return $_GET;
		}
	}
	static function select($data, $query) {
		$query = self::cleanupSelector($query);
		$keys = explode('.', $query);
		$value = $data;
		foreach ($keys as $key) {
			if (! isset($value[$key]))
				return null;
			$value = $value[$key];
		}
		return $value;
	}
	static function transport() {
		return self::isSSL() ? 'https' : 'http';
	}
}
