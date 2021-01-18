<?php

namespace ABetter\Mockup;

//use Illuminate\Database\Eloquent\Model AS BaseModel;

class Pixsum {

	public static $pexels;
	public static $unsplash;

	public static $types = [
		'photo',
		'logo',
		'illustration',
		'video',
		'placeholder',
	];

	public static $sources = [
		'pexels',
		'unsplash',
	];

	public static $tags = [
		'img',
		'figure',
	];

	public static $default = [
		'storage' => 'cache/pixsum',
		'source' => 'pexels',
		'type' => 'photo',
		'orientation' => 'landscape',
		'id' => NULL,
		'keywords' => NULL,
		'width' => NULL,
		'height' => NULL,
		'index' => NULL,
		'cache' => TRUE,
	];

	// ---

	public static function get() {
		$opt = self::options(func_get_args());
		if ($location = self::cached($opt)) return self::public($location);
		switch ($opt['type']) {
			case 'photo' : {
				if ($opt['source'] == 'pexels') {
					$img = self::pexels($opt);
				}
				if ($opt['source'] == 'unsplash') {
					$img = self::unsplash($opt);
				}
				break;
			}
		}
		return self::cache($img,$opt);
	}

	// ---

	public static function cache($img,$opt=[]) {
		if (empty($opt['cache'])) return $img['src'];
		$location = $opt['storage'].'/'.self::cachekey($opt);
		$location .= '.'.$opt['source'].'-'.$img['id'];
		$location .= '.color-'.ltrim($img['color'],'#');
		$location .= '.'.$img['type'];
		$ctx = stream_context_create(['http'=>['timeout'=>300]]);
		if (!empty($img['src']) && ($content = @file_get_contents($img['src'],FALSE,$ctx))) {
			@file_put_contents($location,$content);
		} else {
			$log = $opt['storage'].'/'.self::cachekey($opt).'.error';
			$img['opt'] = $opt;
			$img['http_error'] = error_get_last();
			@file_put_contents($log,json_encode($img,JSON_PRETTY_PRINT));
			return FALSE;
		}
		return self::public($location);
	}

	public static function cached($opt=[]) {
		if (empty($opt['cache'])) return FALSE;
		return ($location = self::cachefind($opt)) ? $location : FALSE;
	}

	public static function cachefind($opt,$found=FALSE) {
		$find = self::cachekey($opt);
		$dir = $opt['storage'];
		if (!is_dir($dir)) return FALSE;
		foreach (scandir($dir) AS $f) {
			if ($found || in_array($f,['.','..'])) continue;
			if (strtok($f,'.') == strtok(basename($find),'.')) {
				$found = $dir.'/'.$f;
			}
		}
		return $found;
	}

	public static function cachekey($opt=[]) {
		return md5(serialize($opt));
	}

	public static function public($location) {
		return ($p = explode('/storage/',$location)) ? '/'.end($p) : "";
	}

	// ---

	public static function pexels($opt=[],$img=[]) {
		if (empty(self::$pexels)) {
			$token = env('PEXELS_KEY');
			self::$pexels = new \GuzzleHttp\Client([
				'base_uri' => 'https://api.pexels.com/v1/',
				'headers' => ['Authorization' => $token],
				'http_errors' => FALSE,
			]);
		}
		if (!empty($opt['id'])) {
			$img = self::pexelsPhoto($opt['id']);
		} else if (!empty($opt['keywords'])) {
			$img = self::pexelsSearch($opt['keywords'],$opt['orientation']);
		} else {
			$img = self::pexelsRandom($opt['orientation']);
		}
		return $img;
	}

	public static function pexelsPhoto($id='',$img=[]) {
		try { if ($req = self::$pexels->get('photos/'.$id)) {
			$res = json_decode($req->getBody());
			$img['id'] = $res->id ?? "";
			$img['color'] = $res->avg_color ?? "";
			$img['src'] = $res->photos[0]->src->large2x ?? "";
			$img['type'] = self::filetype($img['src']);
			if (empty($img['src'])) $img['error'] = ['res'=>$res,'img'=>$img];
		}} catch(Exception $e) {
			$img['error'] = $e->getMessage();
		}
		return $img;
	}

	public static function pexelsRandom($orientation='',$random=10000,$query=[],$img=[]) {
		$query['orientation'] = $orientation;
		$query['per_page'] = 1;
		$query['page'] = rand(1,$random);
		$query['nocache'] = time();
		try { if ($req = self::$pexels->get('curated?'.http_build_query($query))) {
			$res = json_decode($req->getBody());
			$img['id'] = $res->photos[0]->id ?? "";
			$img['color'] = $res->photos[0]->avg_color ?? "";
			$img['src'] = $res->photos[0]->src->large2x ?? "";
			$img['type'] = self::filetype($img['src']);
			if (empty($img['src'])) $img['error'] = ['res'=>$res,'img'=>$img];
		}} catch(Exception $e) {
			$img['error'] = $e->getMessage();
		}
		return $img;
	}

	public static function pexelsSearch($keywords="",$orientation='',$query=[],$img=[]) {
		$query['orientation'] = $orientation;
		$query['query'] = $keywords;
		$query['per_page'] = 1;
		$query['page'] = 1;
		$query['nocache'] = time();
		try { if ($req = self::$pexels->get('search?'.http_build_query($query))) {
			$res = json_decode($req->getBody());
			$img['id'] = $res->photos[0]->id ?? "";
			$img['color'] = $res->photos[0]->avg_color ?? "";
			$img['src'] = $res->photos[0]->src->large2x ?? "";
			$img['type'] = self::filetype($img['src']);
			if (empty($img['src'])) $img['error'] = ['res'=>$res,'img'=>$img];
		}} catch(Exception $e) {
			$img['error'] = $e->getMessage();
		}
		return $img;
	}

	// ---

	public static function unsplash($opt=[]) {
		if (empty(self::$unsplash)) {
			$token = env('UNSPLASH_KEY');
			$secret = env('UNSPLASH_SECRET');
			self::$unsplash = new \GuzzleHttp\Client([
				'base_uri' => 'https://api.unsplash.com/',
				'headers' => ['Authorization' => "Client-ID $token"],
				'http_errors' => FALSE,
			]);
		}
		if (!empty($opt['id'])) {
			$img = self::unsplashPhoto($opt['id']);
		} else if (!empty($opt['keywords'])) {
			$img = self::unsplashSearch($opt['keywords'],$opt['orientation']);
		} else {
			$img = self::unsplashRandom($opt['orientation']);
		}
		return $img;
	}

	public static function unsplashPhoto($id='',$img=[]) {
		try { if ($req = self::$unsplash->get('photos/'.$id)) {
			$res = json_decode($req->getBody());
			$img['id'] = $res->id ?? "";
			$img['color'] = $res->color ?? "";
			$img['src'] = $res->urls->full ?? "";
			$img['type'] = self::filetype($img['src']);
			if (empty($img['src'])) $img['error'] = ['res'=>$res,'img'=>$img];
		}} catch(Exception $e) {
			$img['error'] = $e->getMessage();
		}
		return $img;
	}

	public static function unsplashRandom($orientation='',$query=[],$img=[]) {
		$query['orientation'] = $orientation;
		$query['nocache'] = time();
		try { if ($req = self::$unsplash->get('photos/random?'.http_build_query($query))) {
			$res = json_decode($req->getBody());
			$img['id'] = $res->id ?? "";
			$img['color'] = $res->color ?? "";
			$img['src'] = $res->urls->full ?? "";
			$img['type'] = self::filetype($img['src']);
			if (empty($img['src'])) $img['error'] = ['res'=>$res,'img'=>$img];
		}} catch(Exception $e) {
			$img['error'] = $e->getMessage();
		}
		return $img;
	}

	public static function unsplashSearch($keywords='',$orientation='',$query=[],$img=[]) {
		$query['query'] = $keywords;
		$query['orientation'] = $orientation;
		$query['per_page'] = 1;
		$query['page'] = 1;
		$query['nocache'] = time();
		try { if ($req = self::$unsplash->get('search/photos?'.http_build_query($query))) {
			$res = json_decode($req->getBody());
			$img['id'] = $res->results[0]->id ?? "";
			$img['color'] = $res->results[0]->color ?? "";
			$img['src'] = $res->results[0]->urls->full ?? "";
			$img['type'] = self::filetype($img['src']);
			if (empty($img['src'])) $img['error'] = ['res'=>$res,'img'=>$img];
		}} catch(Exception $e) {
			$img['error'] = $e->getMessage();
		}
		return $img;
	}


	// ---

	public static function filetype($url,$reverse=FALSE) {
		return self::format(($headers = @get_headers($url,1)) ? $headers['Content-Type']??"" : "image/jpeg",TRUE);
	}

	public static function format($ext,$reverse=FALSE) {
		$formats = [
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'png' => 'image/png',
			'gif' => 'image/gif',
			'm4v' => 'video/mp4',
			'mp4' => 'video/mp4',
		];
		$formats = ($reverse) ? array_flip($formats) : $formats;
		return $formats[$ext] ?? reset($formats);
	}

	// ---

	public static function options($args=[]) {
		$opt = self::$default;
		foreach ($args AS $arg) if (is_array($arg)) $opt = array_merge($opt,$arg);
		if (preg_match('/\:/',($args[0]??""))) {
			$args = explode(':',$args[0]);
		}
		if (is_string($args[0]??NULL)) {
			if (in_array($args[0],self::$sources)) {
				$opt['source'] = $args[0];
				$opt['type'] = 'photo';
			} else if (in_array($args[0],self::$types)) {
				$opt['type'] = $args[0];
			} else {
				$opt['type'] = reset(self::$types);
				$opt['keywords'] = $args[0];
			}
		}
		if (is_string($args[1]??NULL)) {
			if (in_array($args[1],self::$tags)) {
				$opt['type'] = $args[1];
			} else {
				$opt['keywords'] = $args[1];
			}
		}
		$opt['storage'] = storage_path($opt['storage']?:'pixsum');
		if (!is_dir($opt['storage'])) \File::makeDirectory($opt['storage'],0777,TRUE);
		return $opt;
	}

}
