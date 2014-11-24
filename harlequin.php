<?
/**

HARLEQUIN
=========

An automatic testing framework

*/

require_once(dirname(__FILE__).'/lib/misc.php');
require_once(dirname(__FILE__).'/lib/recorder.php');
require_once(dirname(__FILE__).'/lib/testplan.php');

class Harlequin {
	// Things you can configure:
	static $SKIP_TRACKING_COOKIES = array(
		'/^__/',
		'/^km_/',
		'/^_ga/',
		'/^kvcd/',
		'/^wcsid/',
		'/^_ok/',
		'/^hblid/',
		'/^olfsk/',
	);
	static $OUTPUT_COLORS = array(
			'success' => '0;37',
			'failure' => '1;31;40',
			'info'    => '',
			'notice'  => '1;30;40'
		);
	static $_phpCodePath = __FILE__;
	static function record($dir) {
    if (!$dir || !file_exists($dir) || !is_writable($dir)) 
      throw new Exception('Harlequin output folder not writable: '.$dir);
		HarlequinRecorder::boot($dir);
	}
	static function invokeCLI($testConfig) {
		if (php_sapi_name() == 'cli') 
			HarlequinRunner::run($testConfig);
	}
}

class HarlequinAnsi {
	static function color($status) {
		return "\033[".Harlequin::$OUTPUT_COLORS[$status].'m';
	}
}

class HarlequinRunner {
	static function run($testConfig) {
		HarlequinTestPlan::run($testConfig);
	}
	static function configureCurl($testConfig) {
	}
}

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

class HarlequinValidator {
	static function isInt($val) {
		echo "isInt:\n";
		var_dump($val);
		return gettype($val) === 'integer';
	}
	static function isIntId($val) {
		return gettype($val) === 'integer';
	}
	static function isString($val) {
		return gettype($val) === 'string';
	}
}
