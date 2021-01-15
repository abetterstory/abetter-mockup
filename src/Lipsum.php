<?php

namespace ABetter\Mockup;

use \Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Model AS BaseModel;

class Lipsum extends BaseModel {

	public static $faker;

	public static $default = [
		'format' => 'lipsum',
		'faker' => NULL,
		'type' => NULL,
		'size' => 350,
		'fraction' => 10,
		'dot' => TRUE,
		'min' => NULL,
		'max' => NULL,
		'tag' => NULL,
		'attr' => NULL,
		'class' => NULL,
		'repeat' => NULL,
	];

	public static function get() {

		$opt = self::options(func_get_args());

		if ($opt['min'] === NULL) $opt['min'] = $opt['size'] - ($opt['size'] / $opt['fraction']);
		if ($opt['max'] === NULL) $opt['max'] = $opt['size'] + ($opt['size'] / $opt['fraction']);

		self::$faker = Faker::create();

		$return = "";

		switch ($opt['type']) {
			case 'word' : $return .= self::format(ucfirst(self::$faker->word()),$opt); break;
			case 'name' : $return .= self::format(self::$faker->name(),$opt); break;
			case 'label' :
			case 'link' : $return .= self::fake(array_merge($opt,['min'=>20,'max'=>25,'dot'=>FALSE])); break;
			case 'menu' : $return .= self::fake(array_merge($opt,['min'=>15,'max'=>20,'dot'=>FALSE])); break;
			case 'headline' : $return .= self::fake(array_merge($opt,['min'=>70,'max'=>100])); break;
			case 'tiny' :
			case 'line' : $return .= self::fake(array_merge($opt,['min'=>20,'max'=>25])); break;
			case 'short' : $return .= self::fake(array_merge($opt,['min'=>30,'max'=>40])); break;
			case 'lead' :
			case 'medium' : $return .= self::fake(array_merge($opt,['min'=>250,'max'=>300])); break;
			case 'long' : $return .= self::fake(array_merge($opt,['min'=>500,'max'=>600])); break;
			case 'extra' : $return .= self::fake(array_merge($opt,['min'=>800,'max'=>900])); break;
			case 'normal' :
			default : $return .= self::fake(array_merge($opt,['min'=>300,'max'=>400]));
		}

		return (string) $return;

	}

	// ---
	
	public static function fake($opt=[]) {
		$length = rand($opt['min'],$opt['max']);
		if ($opt['format'] == 'real') {
			$line = self::$faker->realText($length);
		} else {
			$line = self::$faker->text($length);
		}
		return self::format($line,$opt);
	}

	public static function format($line,$opt=[]) {
		$line = self::cleanup($line,$opt);
		$attr = ($opt['attr']) ? " {$opt['format']}" : "";
		$class = ($opt['class']) ? " class=\"{$opt['format']}\"" : "";
		return ($opt['tag']) ? "<{$opt['tag']}{$class}{$attr}>$line</{$opt['tag']}>" : $line;
	}

	public static function cleanup($line,$opt=[]) {
		if (empty($opt['dot'])) $line = rtrim($line,'.!?');
		return $line;
	}

	// ---

	public static function options($args=[]) {
		$opt = self::$default;
		foreach ($args AS $arg) if (is_array($arg)) $opt = array_merge($opt,$arg);
		if (is_numeric($args[0]??NULL)) $opt['size'] = (int) $args[0];
		if (is_string($args[0]??NULL)) $opt['type'] = $args[0];
		if (is_string($args[1]??NULL)) $opt['tag'] = $args[1];
		return $opt;
	}

}
