<?

class HarlequinStore {
	static $file = false;
	static $cache = false;

	static function append($field, $value) {
		// enlist nonarray values
		if (isset(self::$cache[$field]) 
				&& !is_array(self::$cache[$field]))
			self::$cache[$field] = array(self::$cache[$field]);
		self::$cache[$field][] = $value;
	}
	static function boot($file) {
		self::$file = $file;
		if (file_exists($file)) {
			self::$cache = require($file);
		} else {
			self::$cache = array();
		}
	}
	static function flush() {
		file_put_contents(self::$file, '<'.'? return '.var_export(self::$cache, true).';');
	}
	static function loadOrDefault($field, $value) {
		if (isset(self::$cache[$field]))
			return self::$cache[$field];
		else
			return $value;
	}
	static function store($field, $value) {
		self::$cache[$field] = $value;
	}
}

