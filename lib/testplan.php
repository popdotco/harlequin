<?

class HarlequinTestEvaluator {
	static function evaluate($testConfig, $response) {
		$plan = $testConfig['testPlan'];
		$len = strlen($response[1]);
		foreach ($plan['expect'] as $expect) {
			if ($expect['minLength'] > $len) {
				return array('success' => false, 'error' => 'minLength', 'field' => 'content-length');
			}
			if ($expect['maxLength'] < $len) {
				return array('success' => false, 'error' => 'maxLength', 'field' => 'content-length');
			}
		}
		$content = @json_decode($response[1], true);
		if (!empty($expect['response'])) {
			$valid = self::evaluateResponse($expect['response'], $content);
			if (! $valid['success'])
				return $valid;
		}
		// other tests..
		return array('success' => true, 'data' => $content);
	}
	static function evaluateResponse($responseValidators, $content, $selectorRoot = '') {
		foreach ($responseValidators as $k=>$params) {
			// echo "evaluateResponse - $selectorRoot.$k: " . var_export($params, true) . "\n";
			$reqMatched = null;

			if (is_array($params) && !isset($params['validateThis'])) {
				// this appears to be an array of subparameters; recurse
				$reqMatched = self::evaluateResponse($params, $content, $selectorRoot . '.' . $k);
			} else {
				// regular response validation config (leaf node):
				if (!empty($params['validateThis'])) {
					$reqMatched = self::evaluateResponseFieldRequirement($content, $k, $params, $selectorRoot);
				}
			}

			// if this attempted field failed validation, bomb out entirely
			if ($reqMatched !== null && !$reqMatched['success'])
				return $reqMatched;
		}
	}
	static function evaluateResponseFieldRequirement($content, $k, $params, $selectorRoot = '') {
		$selector = "$selectorRoot.$k";
		$value = HarlequinMisc::select($content, $selector);
		if ($params['allowEmpty'] === false && empty($value)) {
			return array('success' => false, 'error' => 'selector mismatch', 'field' => $selector);
		} 
		if (isset($params['validator']) && is_callable($params['validator'])) {
			$v = $params['validator'];
			if (! ($v($value))) {
				HarlequinMisc::output('notice', "calling validator on $selector");
				return array('success' => false, 'error' => 'validator-failed', 'field' => $selector);
			}
		}
		return array('success' => true);
	}
}

class HarlequinTestPlan {
	static function run($testConfig) {
		$response = self::request($testConfig);
		$result = HarlequinTestEvaluator::evaluate($testConfig, $response);
		self::show($testConfig, $result, $response);
	}
	static function data($testConfig) { 
		$out = array();
		return HarlequinMisc::expand($testConfig['testPlan']['request']['data']);
	}
	static function headers($testPlanReq) {
		$out = array();
		foreach (HarlequinMisc::expand($testPlanReq['headers']) as $k=>$v) {
			$out[] = "$k: $v";
		}
		return $out;
		$keys = array_map(function($value) {
			return var_export($value, true);
		}, explode('.', $query));
	}
	static function request($testConfig) {
		$testPlanReq = $testConfig['testPlan']['request'];
		$url = $testConfig['urlPrefix'] . $testConfig['path'];
		$ch = curl_init();
		if (!empty($testPlanReq['data'])) {
			$post = false;

			if ($testPlanReq['type'] != 'GET') {
				if (!empty($testPlanReq['POST'])) {
					$post = self::data($testConfig);
					if ($testPlanReq['json'])
						$post = json_encode($post);
				}
			} else {
				if (!empty($testPlanReq['GET'])) {
					$url .= '?' . http_build_str(self::data($testConfig));
				}
				HarlequinMisc::output('notice', 'get: ' . $url);
			}

			if ($post) {
				curl_setopt($ch, CURLOPT_POST, 1);
				HarlequinMisc::output('notice', 'post: ' . $post);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			}
		}
		$headers = self::headers($testPlanReq);
		HarlequinMisc::output('notice', 'headers: ' . join('; ', $headers));
		curl_setopt_array($ch, array(
			CURLOPT_URL => $url,
			CURLOPT_HEADER => true,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 10
		));
		HarlequinMisc::output('notice', 'requesting: '.$url);
		return explode("\r\n\r\n", curl_exec($ch), 2);
	}
	static function show($testConfig, $result, $response) {
		if ($result['success']) {
			HarlequinMisc::output('success', "passed: $testConfig[name]");
			self::updateStore($testConfig, $result, $response);
		} else {
			HarlequinMisc::output('failure', "FAILED: $testConfig[name]");
			HarlequinMisc::output('info', "field: $result[field]; validator: $result[error]");
			HarlequinMisc::output('info', 'expected: '.$testConfig['fullRequestData']['response']);
			HarlequinMisc::output('info', 'received headers: '.$response[0]);
			HarlequinMisc::output('info', 'received body: '.$response[1]);
		}
	}
	static function updateStore($testConfig, $result, $response) {
		if (!empty($result['data'])) {
			HarlequinStore::append('test-history', $testConfig['name']);
			foreach ($result['data'] as $k=>$v) {
				HarlequinStore::store($k, $v);
			}
			HarlequinStore::flush();
		}
	}
}
