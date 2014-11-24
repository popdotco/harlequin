<?
//
// HARLEQUIN
//
// An automatic testing framework for PHP
//
// https://github.com/popdotco/harelquin/
//

require_once(dirname(__FILE__).'/lib/misc.php');
require_once(dirname(__FILE__).'/lib/recorder.php');
require_once(dirname(__FILE__).'/lib/store.php');
require_once(dirname(__FILE__).'/lib/testplan.php');
require_once(dirname(__FILE__).'/lib/validators.php');

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

