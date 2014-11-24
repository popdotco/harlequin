<?
// XXX so many more validators to write 
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
