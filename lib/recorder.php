<?
class HarlequinRecorder {
	static $dir = false;
	static $testCounter = null;
	static function boot($dir) {
		HarlequinRecorder::$dir = $dir;
		ob_start();
		register_shutdown_function('HarlequinRecorder::writeSession');
	}

	// build 'expect' expression for test file based on response from
	// request
	static function buildExpect($req) { 
		$contentStr = $req['response'];
		$expect = array();
		$expect['minLength'] = strlen($contentStr) / 2;
		$expect['maxLength'] = strlen($contentStr) * 2;
		$expect['responseCode'] = $req['responseCode'];
		if (@$content = json_decode($contentStr, true)) {
			$expect['response'] = self::expectExpression(false, $content);
		}
		return array($expect);
	}

	static function expectExpression($k, $v) {
		if (is_array($v)) {
			$out = array();
			foreach (array_slice($v, 0, 10) as $kk=>$vv) {
				$out[$kk] = self::expectExpression($kk, $vv);
			} 
			return $out;
		} else {
			return array(
				'validateThis' => true,
				'allowEmpty' => true,
				'store' => true,
				'validator' => self::getValidator($k, $v),
			);
		}
	}

	static function getValidator($field, $value) {
		if (preg_match('/_?(id|key)$/', $field)) {
			if (preg_match('/^[0-9]+/', $value)) {
				$v = 'HarlequinValidator::isIntId';
			} else {
				$v = 'HarlequinValidator::isId';
			}
		} else if (@strtotime($value) !== false) {
			$v = 'HarlequinValidator::isDate';
		} else {
			$v = 'HarlequinValidator::is' . ucwords(gettype($value));
		}
		return HarlequinMisc::encodePHPExpression('function($value) { return '.$v. '($value); }');
	}

	static function loadExpression($k, $v) {
		return "HarlequinStore::loadOrDefault('$k', ".var_export($v, true).")";
	}

	static function outputFile() {
		if (self::$testCounter === null) {
			self::$testCounter = count(glob(self::$dir.'/t-*.php'));
		}
		self::$testCounter++;
		$endPt = preg_replace('/[^a-z=]+/i', '_', substr($_SERVER['REQUEST_URI'], 1));
		return HarlequinRecorder::$dir . '/t-' . (string)self::$testCounter . '-' . date('YmdHis') . '-' . $endPt . '.php';
	}

	static function requestMap($array) {
		$tmp = array();
		if (empty($array)) return;
		foreach (array_slice($array, 0, 25) as $k=>$v) {
			$skip = false;
			foreach (Harlequin::$SKIP_TRACKING_COOKIES as $re) {
				if (preg_match($re, $k))
					$skip = true;
			}
			if (!$skip)
				$tmp[$k] = HarlequinMisc::encodePHPExpression('function() { return '.self::loadExpression($k, $v).'; }');
		}
		return $tmp;
	}

	static function sessionRepresentation() {
		$m = $_SERVER['REQUEST_METHOD'];
		$req = array();
		$req['runThisTest'] = true; 
		$req['name'] = "$m to $_SERVER[REQUEST_URI]";
		$req['type'] = $m;
		$req['urlPrefix'] = HarlequinMisc::transport() . '://' . $_SERVER['HTTP_HOST'];
		$req['path'] = $_SERVER['REQUEST_URI'];
		$frd = array(
			'time' => date('Y-m-d H:i:s'),
			'responseCode' => http_response_code(),
			'cookies' => $_COOKIE,
			'headers' => array(
				// fastcgi: php>5.4.0, cli: php>5.5.7
				'request' => apache_request_headers(),
				'response'	=> apache_response_headers(), 
			),
			'request' => $m == 'POST' ? HarlequinMisc::post() : $_GET,
			'response' => ob_get_contents()
		);
		$req['testPlan'] = array(
			'request' => array(
				'cookies' => self::requestMap($_COOKIE),
				'headers' => self::requestMap(apache_request_headers()),
				'type' => HarlequinMisc::abstractRequestType(), 
				'json' => HarlequinMisc::jsonEncodeBody(),
				'data' => self::requestMap(HarlequinMisc::requestData())
			),
			'expect' => self::buildExpect($frd),
			'prepareCallback' => null,
			'cleanupCallback' => null
		);
		$req['fullRequestData'] = $frd;
		return $req;
	}

	static function testStub($testData) {
		$thisFile = Harlequin::$_phpCodePath;
		$testDataStr = HarlequinMisc::decodePHPExpression(var_export($testData, true));
		$code = '<' . <<<EOF
?php
// Harlequin Auto Test
// Edit freely

require_once('$thisFile');

// HarlequinStore acts as a cookie jar of sorts, storing data from previous
// tests and allowing us to preserve values in between tests. This is just PHP
// so just shove any crazy data generators you need in there.
HarlequinStore::boot(dirname(__FILE__).'/session-store.php');

// This test:

\$testConfig = $testDataStr;

// Load configuration:

// Use local-{ENV}.php for local test overrides, such as
// different test scenarios. In that file, simply set 
// options on \$testConfig
if (!empty(\$_ENV['ENV']) &&
	  file_exists(dirname(__FILE__).'/local-'.\$_ENV['ENV'].'.php'))
	require_once(dirname(__FILE__).'/local-'.\$_ENV['ENV'].'.php');

// Edit config.php to set other options on \$testConfig:
if (file_exists(dirname(__FILE__).'/config.php'))
	require_once(dirname(__FILE__).'/config.php');

// If invoked directly, run this test
Harlequin::invokeCLI(\$testConfig);

return \$testConfig;
EOF;
		return $code;
	}

	static function writeSession() {
    if (HarlequinRecorder::$dir) {
      ob_end_flush();
      error_log(self::$dir);
			file_put_contents(self::outputFile(), self::testStub(self::sessionRepresentation()));
     }
	}
}

