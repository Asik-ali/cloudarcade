<?php

defined('ABSPATH') or die('abcd commons');

function get_all_categories(){
	// Excluding hidden categories
	$data = Category::getList();
	$results = $data['results'];
	foreach ($results as $key => $category) {
		if($category->priority < 0){
			unset($results[$key]);
		}
	}
	return $results;
}
function get_user($username){
	$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$sql = 'SELECT * FROM users WHERE username = :username';
	$st = $conn->prepare( $sql );
	$st->bindValue( ":username", $username, PDO::PARAM_STR );
	$st->execute();
	$row = $st->fetch();
	$conn = null;
	if ( $row ) return $row;
	return false;
}
function is_login(){
	if(isset( $_SESSION['username'] )){
		return true;
	} else {
		return false;
	}
}
function show_logout(){
	// Not used
	if(is_login()){
		echo '<a href="'.DOMAIN.'admin.php?action=logout"> Log out </a>';
	}
}
function get_permalink($type, $slug = '', $arrs = []){
	/*
	Usage:
	- get_permalink('game', 'super-mario');
	- get_permalink('category', 'action', ['page' => 1]);
	- get_permalink('user', 'admin', ['action' => 'edit', 'page' => 2]);
	*/
	$custom_type = get_custom_path($type);
	$params = '';
	$lang_id = '';
	$end_slash = '';
	if(count($arrs)){
		foreach ($arrs as $key => $value) {
			if( PRETTY_URL ){
				$params .= '/'.$value;
			} else {
				$params .= '&'.$key.'='.$value;
			}
		}
		if($slug == ''){
			$params = substr($params, 1);
		}
	}
	if(PRETTY_URL && $slug){
		// Add slash in the end of url
		if (strpos($params, '.') !== false) { //true
			//
		} else { //false
			if(get_setting_value('trailing_slash')){
				if(substr($slug.$params, -1) != '/'){
					$end_slash = '/';
				}
			}
		}
		if(get_setting_value('lang_code_in_url')){
			global $lang_code;
			if(isset($lang_code)){
				$lang_id = $lang_code.'/';
			}
		}
	}
	if($type == 'game' && $slug === ''){
		// Fix bug for get link without slug, include lang code
		if($lang_id === '' && get_setting_value('lang_code_in_url')){
			global $lang_code;
			if(isset($lang_code)){
				$lang_id = $lang_code.'/';
			}
		}
	}
	if($type == 'game'){
		if( PRETTY_URL ){
			return DOMAIN . $lang_id.$custom_type.'/' . $slug . $end_slash;
		} else {
			return DOMAIN . 'index.php?viewpage='.$custom_type.'&slug=' . $slug . $params;
		}
	} else if($type == 'archive'){
		if( PRETTY_URL ){
			return DOMAIN . $lang_id.$custom_type.'/' . $slug . $end_slash;
		} else {
			return DOMAIN . 'index.php?viewpage='.$custom_type.'&slug=' . $slug . $params;
		}
	} else if($type == 'search'){
		if( PRETTY_URL ){
			return DOMAIN . $lang_id.$custom_type.'/' . $slug . $params . $end_slash;
		} else {
			return DOMAIN . 'index.php?viewpage='.$custom_type.'&key=' . $slug . $params;
		}
	} else if($type == 'category'){
		$slug = strtolower($slug);
		if(get_setting_value('allow_slug_translation') && function_exists('get_slug_translation')){
			$slug = get_slug_translation(strtolower($slug));
		}
		if( PRETTY_URL ){
			return DOMAIN . $lang_id.$custom_type.'/' . $slug . $params . $end_slash;
		} else {
			return DOMAIN . 'index.php?viewpage='.$custom_type.'&slug=' . $slug . $params;
		}
	} else if($type == 'tag'){
		$slug = strtolower($slug);
		if(get_setting_value('allow_slug_translation') && function_exists('get_slug_translation')){
			$slug = get_slug_translation(strtolower($slug));
		}
		if( PRETTY_URL ){
			return DOMAIN . $lang_id.$custom_type.'/' . $slug . $params . $end_slash;
		} else {
			return DOMAIN . 'index.php?viewpage='.$custom_type.'&slug=' . $slug . $params;
		}
	} else if($type == 'page'){
		if( PRETTY_URL ){
			return DOMAIN . $lang_id.$custom_type.'/' . $slug . $params . $end_slash;
		} else {
			return DOMAIN . 'index.php?viewpage='.$custom_type.'&slug=' . $slug . $params;
		}
	} else {
		if( PRETTY_URL ){
			if(!$slug){
				$slug = '';
			}
			return DOMAIN . $lang_id . $custom_type .'/' . $slug . $params . $end_slash;
		} else {
			if(!$slug){
				$slug = '';
			} else {
				$slug = '&slug='.$slug;
			}
			return DOMAIN . 'index.php?viewpage=' . $custom_type . $slug . $params;
		}
	}
}
function get_small_thumb($game){
	$thumb = (isset($game->thumb_small) && $game->thumb_small != '' ? esc_url($game->thumb_small) : esc_url($game->thumb_2));
	if(substr($thumb, 0, 1) == '/'){
		$thumb = DOMAIN . substr($thumb, 1);
	}
	return $thumb;
}
function get_game_url($game){
	$url = esc_url($game->url);
	if(substr($url, 0, 7) == '/games/'){
		if(get_setting_value('splash')){
			$url = get_permalink('splash', $game->slug);
			return $url;
		} else {
			$url = DOMAIN . substr($url, 1);
		}
	} elseif($game->game_type != 'html5'){
		$game_types = get_game_types();
		if(isset($game_types[$game->game_type])){
			return $game_types[$game->game_type]['template'] . '?game='.$game->slug;
		} else {
			return '/404';
		}
	} elseif($game->source == 'gamedistribution'){
		//GameDistributon new url
		$url .= '?gd_sdk_referrer_url='.get_permalink('game', $game->slug);
	} elseif($game->source == 'gamepix'){
		//GamePix implement SID
		$gamepix_sid = get_pref('gamepix-sid');
		if(!is_null($gamepix_sid) && $gamepix_sid != ''){
			$parsed_url = parse_url($url);
			parse_str($parsed_url['query'], $query_params);
			$query_params['sid'] = $gamepix_sid;
			$new_query_string = http_build_query($query_params);
			$url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'] . '?' . $new_query_string;
		}
	} elseif($game->source == 'remote'){
		if(get_setting_value('splash') && get_setting_value('allow_splash_on_remote_games')){
			$url = get_permalink('splash', $game->slug);
		}
		if(strpos($url, "https://html5.gamedistribution.com") === 0){
			// Add extra parameter for GameDistribution
			$url .= '?gd_sdk_referrer_url='.get_permalink('game', $game->slug);
		}
	}
	return $url;
}
function commas_to_array($str){
	return preg_split("/\,/", $str);
}
function html_purify($html_content){
	require_once ABSPATH.'vendor/HTMLPurifier/HTMLPurifier.auto.php';
	$config = HTMLPurifier_Config::createDefault();
	$purifier = new HTMLPurifier($config);
	$clean_html = $purifier->purify($html_content);
	return $clean_html;
}
function esc_string($str){
	if($str == '') return $str;
	return strip_tags($str);
}
function esc_int($int){
	return (int)preg_replace('/[^0-9]/', '', $int);
}
function esc_url($str){
	return $str;
	// Pass it for now, previously using filter_var($str, FILTER_SANITIZE_URL) that are now deprecated.
}
function esc_slug($str){
	if($str == '') return $str;
	if(UNICODE_SLUG){
		return esc_unicode_slug($str);
	} else {
		// Allow unicode letters without UNICODE SLUG
		return strtolower(preg_replace('/[^\p{L}0-9_-]/u', '-', $str));
	}
}
function esc_unicode_slug($str){
	// Not actually used anymore, esc_slug() already allowing unicode letters
	return preg_replace('/[^\p{L}0-9_-]/u', '-', $str);
}
function imgResize($path, $rs_width=160, $rs_height=160, $slug = '') {
	// deprecated since v.1.7.1
	// use admin-functions.php generate_small_thumbnail() instead of call this function directly
	// this function is used to generate small thumbnail
	$x = getimagesize($path);
	$width  = $x['0'];
	$height = $x['1'];
	switch ($x['mime']) {
	  case "image/gif":
		 $img = imagecreatefromgif($path);
		 break;
	  case "image/jpg":
	  case "image/jpeg":
		 $img = imagecreatefromjpeg($path);
		 break;
	  case "image/png":
		 $img = imagecreatefrompng($path);
		 break;
	}
	$img_base = imagecreatetruecolor($rs_width, $rs_height);
	if($x['mime'] == "image/png"){
		imageAlphaBlending($img_base, false);
		imageSaveAlpha($img_base, true);
	}
	imagecopyresampled($img_base, $img, 0, 0, 0, 0, $rs_width, $rs_height, $width, $height);
	$path_info = pathinfo($path);
	$output = $path_info['dirname'].'/'.$slug.'_small.'.$path_info['extension'];
	switch ($path_info['extension']) {
	  case "gif":
		 imagegif($img_base, $output);  
		 break;
	case "jpg":
	case "jpeg":
		 imagejpeg($img_base, $output, 100); // No compression
		 break;
	  case "png":
		 imagepng($img_base, $output, 6); // No compression
		 break;
	}
}
function imgCopy($path, $new_file, $rs_width=160, $rs_height=160) {
	$x = getimagesize($path);
	$width  = $x['0'];
	$height = $x['1'];
	switch ($x['mime']) {
	  case "image/gif":
		 $img = imagecreatefromgif($path);
		 break;
	  case "image/jpg":
	  case "image/jpeg":
		 $img = imagecreatefromjpeg($path);
		 break;
	  case "image/png":
		 $img = imagecreatefrompng($path);
		 break;
	}
	$img_base = imagecreatetruecolor($rs_width, $rs_height);
	if($x['mime'] == "image/png"){
		imageAlphaBlending($img_base, false);
		imageSaveAlpha($img_base, true);
	}
	imagecopyresampled($img_base, $img, 0, 0, 0, 0, $rs_width, $rs_height, $width, $height);
	$path_info = pathinfo($path);
	$output = $new_file;
	switch ($path_info['extension']) {
	  case "gif":
		 imagegif($img_base, $output);  
		 break;
	case "jpg":
	case "jpeg":
		 imagejpeg($img_base, $output);
		 break;
	  case "png":
		 imagepng($img_base, $output);
		 break;
	}
}
function image_to_webp($file_path, $quality = 100, $new_file = null, $destroy_original_file = false){
	$img = null;
	$_img = getimagesize($file_path);
	$img_format;
	if(!$_img) return;
	switch ($_img['mime']) {
		case "image/jpg":
		case "image/jpeg":
			$img = imagecreatefromjpeg($file_path);
			$img_format = 'jpg';
			break;
		case "image/png":
			$img = imagecreatefrompng($file_path);
			$img_format = 'png';
			break;
		case "image/gif":
			$img = imagecreatefromgif($file_path);
			$img_format = 'gif';
			break;
	}
	if(!$img_format){
		return false;
	}
	$file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
	if(!$new_file){
		$new_file = str_replace('.'.$file_extension, '.webp', $file_path);
	}
	if($img_format == 'png' || $img_format == 'gif'){
		imagepalettetotruecolor($img);
		imagealphablending($img, true);
		imagesavealpha($img, true);
	}
	imagewebp($img, $new_file, -1); // No compression
	imagedestroy($img);
	if($destroy_original_file){
		unlink($file_path);
	}
}
function webp_to_image($file_path, $quality = 100, $new_format = 'jpg', $destroy_original_file = false){
	if($new_format != 'jpg' && $new_format != 'png'){
		echo 'File format must be jpg or png';
		return;
	}
	if(pathinfo($file_path, PATHINFO_EXTENSION) != 'webp'){
		echo 'File to convert must be .webp';
		return;
	}
	$img = imagecreatefromwebp($file_path);
	if($new_format == 'png'){
		imagepng($img, str_replace('.webp', '.'.$new_format, $file_path));
	} elseif($new_format == 'jpg'){
		imagejpeg($img, str_replace('.webp', '.'.$new_format, $file_path));
	}
	if(!$img){
		return;
	}
	imagedestroy($img);
	if($destroy_original_file){
		unlink($file_path);
	}
}

function webp_resize($file_path, $new_file = null, $newwidth = 160, $newheight = 160, $quality = 95){
	// Deprecated since v.1.7.1, replaced with generate_small_thumbnail() admin-functions.php
	// Used for small thumb
	$file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
	if($file_extension != 'webp'){
		return;
	}
	if(!$new_file){
		$new_file = $file_path;
	}
	$_img = getimagesize($file_path);
	$width  = $_img['0'];
	$height = $_img['1'];
	$img = imagecreatefromwebp($file_path);
	$new_img = imagecreatetruecolor($newwidth, $newheight);
	imagecopyresized($new_img, $img, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
	//output
	imagewebp($new_img, $new_file, -1); // No compression
}
function check_purchase_code(){
	return get_setting_value('purchase_code') == '' ? null : get_setting_value('purchase_code');
}
function get_admin_warning(){
	$results = [];
	if(!check_purchase_code() && !ADMIN_DEMO){
		array_push($results, 'Please provide your <b>Item Purchase code</b>. You can submit or update your Purchase code on site settings.');
	}
	if(URL_PROTOCOL == 'http://'){
		if(is_https()){
			array_push($results, 'You\'re using HTTPS but current config use HTTP, you can switch to HTTPS in Settings -> Advanced.');
		}
	}
	if(!check_writeable()){
		array_push($results, 'CloudArcade don\'t have permissions to modify files, uploaded files can\'t be saved and can\'t do backup or update. Change all folders and files CHMOD to 777 to fix this.');
	}
	if(!class_exists('ZipArchive')){
		array_push($results, '"ZipArchive" extension is missing or disabled. Can\'t do backup or update.');
	}
	if(!function_exists('curl_init')) {
		array_push($results, '"The cURL extension is missing or disabled. Please activate it in php.ini."');
	}
	if( (int)phpversion() < 7){
		array_push($results, 'You\'re using PHP v-'.phpversion().', CloudArcade is requires PHP v-7.xx');
	}
	return $results;
}
function is_https() {
	if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
		return true;
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
		return true;
	} else {
		return false;
	}
}
function check_writeable(){
	if (is_writable('../config.php') && is_writable('../site-settings.php') && is_writable('../admin/upload.php')) {
		return true;
	} else {
		return false;
	}
}
function get_cur_url(){
	if(SUB_FOLDER && SUB_FOLDER != ''){
		return DOMAIN . substr(str_replace(SUB_FOLDER, '', $_SERVER['REQUEST_URI']), 1);
	} else {
		return DOMAIN . substr($_SERVER['REQUEST_URI'], 1);
	}
}
function get_rating($type, $game){
	if($type == '5'){
		if($game->upvote+$game->downvote > 0){
			return round(($game->upvote/($game->upvote+$game->downvote))*5);
		} else {
			return 0;
		}
	} else if($type == '5-decimal'){
		if($game->upvote+$game->downvote > 0){
			return number_format(($game->upvote/($game->upvote+$game->downvote))*5, 1);
		} else {
			return 0;
		}
	}
}
function is_user_admin($username){
	$conn = open_connection();
	$sql = "SELECT * FROM users WHERE username = :username";
	$st = $conn->prepare($sql);
	$st->bindValue(":username", $username, PDO::PARAM_STR);
	$st->execute();
	$row = $st->fetch();
	if ($row) {
		if($row['role'] === 'admin'){
			return true;
		}
	}
	return false;
}

function scan_folder($path){
	$array = [];

	$dirs = scandir( ABSPATH . $path);
	$dirs = array_diff($dirs, array('.', '..'));

	foreach ($dirs as $dir) {
		if(is_dir( ABSPATH . $path . $dir)){
			if($dir != '.' || $dir != '..'){
				array_push($array, $dir);
			}
		}
	}

	return $array;
}

function scan_files($path){
	$directory = new \RecursiveDirectoryIterator(ABSPATH.$path);
	$iterator = new \RecursiveIteratorIterator($directory);
	$files = array();
	foreach ($iterator as $info) {
		if (is_file($info->getPathname())) {
			$files[] = str_replace(ABSPATH, '', $info->getPathname());
		}
	}
	return $files;
}

function delete_files($target) {
	if(is_dir($target)){
		$files = glob( $target . '*', GLOB_MARK );
		foreach( $files as $file ){
			delete_files( $file );      
		}
		if(is_dir($target)){
			rmdir( $target );
		}
	} elseif(is_file($target)) {
		unlink( $target );  
	}
}

function do_backup($root_path, $backup_type = 'part'){
	// Deprecated since v1.6.9, replaced with backup_cms()
	// Backup directory and file name
	if (extension_loaded('zip') && is_login() && USER_ADMIN && !ADMIN_DEMO) {
		$backup_dir = $root_path.'/admin/backups';
		if (!file_exists($backup_dir)) {
			mkdir($backup_dir, 0755, true);
		}
		$backup_file = $_SESSION['username'].'-cloudarcade-backup-'.$backup_type.'-'.VERSION.'-'.time().'-'.generate_random_strings().'.zip';
		// Exclusions (file and directory names to exclude from backup)
		$ignore_extensions = ['zip', 'rar', '7z'];
		$exclusions = array('cloudarcade', 'private', 'cache', 'temp', 'thumbs', 'vendor', 'games', 'files', 'backups');
		if($backup_type == 'full'){
			$exclusions = array('cloudarcade', 'private', 'cache', 'temp', 'backups');
		}
		add_to_zip( $root_path, ABSPATH . 'admin/backups/'.$backup_file, $exclusions, $ignore_extensions );
	}
}

function add_to_zip($source, $destination, $ignore_folder = [], $ignore_extensions = []) {
	// Deprecated since v1.6.9, replaced with zip_files_recursive()
	if (extension_loaded('zip') && is_login()) {
		if (file_exists($source)) {
			$zip = new ZipArchive();
			if ($zip->open($destination, ZIPARCHIVE::CREATE)) {
				$max_size = 20 * 1024 * 1024; // 20 MB
				if (is_dir($source)) {
					$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
					foreach ($files as $file) {
						$ignored = false;
						foreach ($ignore_folder as $ignore) {
							if (stripos($file, $ignore) !== false) {
								$ignored = true;
								break;
							}
						}
						if ($ignored) {
							continue;
						}
						$relativePath = str_replace('\\', '/', str_replace($source . DIRECTORY_SEPARATOR, '', $file));
						if (is_dir($file)) {
							if (count(glob("$file/*")) > 0) { //If folder not empty
								$zip->addEmptyDir($relativePath . '/');
							}
						} else if (is_file($file)) {
							// Ignore files larger than 20 MB
							if (filesize($file) > $max_size) {
								continue;
							}
							// Ignore archive files
							$ext = pathinfo($file, PATHINFO_EXTENSION);
							if (in_array($ext, $ignore_extensions)) {
								continue;
							}
							$zip->addFromString($relativePath, file_get_contents($file));
						}
					}
				} else if (is_file($source)) {
					// Ignore files larger than 20 MB
					if (filesize($source) > $max_size) {
						return false;
					}
					// Ignore archive files
					$ext = pathinfo($source, PATHINFO_EXTENSION);
					if (in_array($ext, $ignore_extensions)) {
						return false;
					}
					$zip->addFromString(basename($source), file_get_contents($source));
				}
			}
			return $zip->close();
		}
	}
	return false;
}

function generate_random_strings($length = 10) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

function getIpAddr() {
	if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
		$ipAddr = $_SERVER["HTTP_CF_CONNECTING_IP"];
	} elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		$ipAddr = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ipAddr = strtok($_SERVER['HTTP_X_FORWARDED_FOR'], ',');
	} else {
		$ipAddr = $_SERVER['REMOTE_ADDR'];
	}
	if(strlen($ipAddr) > 16){
		$ipAddr = '0.0.0.0';
	}
	return $ipAddr;
}

function get_user_avatar($username = null){
	global $login_user;
	$user;
	if(!$username){
		if($login_user){
			$username = $login_user->username;
			$user = $login_user;
		}
	} else {
		$cur_user = User::getByUsername($username);
		if($cur_user){
			$user = $cur_user;
		}
	}
	if($user){
		if(file_exists(ABSPATH.'images/avatar/'.$username.'.png')){
			return DOMAIN.'images/avatar/'.$username.'.png';
		} elseif($user->avatar){
			return DOMAIN.'images/avatar/default/'.$user->avatar.'.png';
		}
	}
	return DOMAIN.'images/default_profile.png';
}

$lang_data = [];

function load_language($type){
	global $lang_data;
	global $language_file_exist;
	$file = '';
	if($type === 'index'){
		$lang = get_setting_value('language');
		if(isset($_GET['lang'])){
			// Set dynamic language
			if(strlen($_GET['lang']) <= 3){
				setcookie('lang', $_GET['lang'], strtotime('+3 months'), '/');
				$lang = $_GET['lang'];
			}
		}
		if(isset($_COOKIE['lang']) && !isset($_GET['lang'])){
			// Load saved dynamic language
			$lang = $_COOKIE['lang'];
		}
		$file = ABSPATH.'locales/public/'.$lang.'.json';
		if(!file_exists($file)){
			$file = TEMPLATE_PATH.'/locales/'.$lang.'.json'; // Old path, backward compatibility
		}
		if(!file_exists($file)){
			if(isset($_COOKIE['lang']) && !isset($_GET['lang'])){
				// Language selected is not exist anymore, the remove cookie data
				// To avoid developer confusion
				setcookie('lang', '', time() - 3600, '/');
			}
		}
	} elseif($type === 'admin'){
		$file = ABSPATH.'locales/admin/'.get_setting_value('language').'.json';
		if(!file_exists($file)){
			$file = ABSPATH.'locales/'.get_setting_value('language').'.json';
		}
	}
	if(file_exists($file)){
		$lang_data = json_decode(file_get_contents($file), true);
	}
}

function translate($str, $val1 = null, $val2 = null){
	global $lang_data;
	$translated = $str;
	if(isset($lang_data[$str])){
		$translated = $lang_data[$str];
	}
	if(!is_null($val1)){
		$translated = str_replace('%a', $val1, $translated);
	}
	if(!is_null($val2)){
		$translated = str_replace('%b', $val2, $translated);
	}
	return $translated;
}

function _t($str, $val1 = null, $val2 = null){
	return translate($str, $val1, $val2);
}

function _e($str, $val1 = null, $val2 = null){
	echo translate($str, $val1, $val2);
}

function get_translation_key($translated_str) {
    global $lang_data;
    // Instead of get value by a key, this function return a key by value
    foreach ($lang_data as $key => $value) {
        if ($value === $translated_str) {
            return $key;
        }
    }
    return null; // Return null if no matching key is found
}

function get_base_taxonomy($page_name){
	// Get original base
	$custom_path_data = get_setting_value('custom_path');
	if(!empty($custom_path_data)){
		if(isset($custom_path_data[$page_name])){
			return $custom_path_data[$page_name];
		}
	}
	return $page_name;
}

function get_custom_path($base_name){
	// Changed in v1.6.2
	// Replacing convert_to_custom_path()
	$custom_path_data = get_setting_value('custom_path');
	if (!empty($custom_path_data)) {
		$custom_name = array_search($base_name, $custom_path_data);
		if($custom_name){
			return $custom_name;
		}
	}
	return $base_name;
}

function convert_to_custom_path($page_name){
	// Deprecated since v1.6.2
	global $options;
	if(isset($options['custom_path']) && $options['custom_path']){
		$custom_name = array_search($page_name, $options['custom_path']);
		if($custom_name){
			return $custom_name;
		}
	}
	return $page_name;
}

function str_encrypt($str, $key){
	$cipher = "AES-128-CTR";
	$ivlen = openssl_cipher_iv_length($cipher);
	$iv = '1234567891011121';
	return openssl_encrypt($str, $cipher, $key, $options=0, $iv);
}

function str_decrypt($str, $key){
	$cipher = "AES-128-CTR";
	$ivlen = openssl_cipher_iv_length($cipher);
	$iv = '1234567891011121';
	return openssl_decrypt($str, $cipher, $key, $options=0, $iv);
}

function show_alert($message, $type, $btn = true){
	if($type === 'error'){
		$type = 'danger';
	}
	echo '<div class="alert alert-'.$type.' alert-dismissible fade show" role="alert">'._t($message);
	if($btn){
		echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
	}
	echo '</div>';
}

function get_option($name){
	// Deprecated since v1.5.7, use get_pref() instead
	global $conn;
	$sql = "SELECT * FROM prefs WHERE name = :name";
	$st = $conn->prepare($sql);
	$st->bindValue(':name', $name, PDO::PARAM_STR);
	$st->execute();
	$row = $st->fetch();
	if($row){
		return $row['value'];
	} else {
		return null;
	}
}

function update_option($name, $value){
	// Deprecated since v1.5.7, use set_pref() instead
	global $conn;
	$sql = "SELECT id FROM prefs WHERE name = :name";
	$st = $conn->prepare($sql);
	$st->bindValue(':name', $name, PDO::PARAM_STR);
	$st->execute();
	$row = $st->fetch();
	if($row){
		$sql = "UPDATE prefs SET value = :value WHERE name = :name";
		$st = $conn->prepare($sql);
		$st->bindValue(':value', $value, PDO::PARAM_STR);
		$st->bindValue(':name', $name, PDO::PARAM_STR);
		$st->execute();
	} else {
		$sql = "INSERT INTO prefs (name, value) VALUES (:name, :value)";
		$st = $conn->prepare($sql);
		$st->bindValue(':value', $value, PDO::PARAM_STR);
		$st->bindValue(':name', $name, PDO::PARAM_STR);
		$st->execute();
	}
}

function get_pref($name){
	// Alternative for get_option()
	// Reason: better naming
	global $conn;
	$sql = "SELECT * FROM prefs WHERE name = :name";
	$st = $conn->prepare($sql);
	$st->bindValue(':name', $name, PDO::PARAM_STR);
	$st->execute();
	$row = $st->fetch();
	if($row){
		return $row['value'];
	} else {
		// Return null if key doesnt exist
		return null;
	}
}

function get_pref_bool($name){
	// Return boolean value
	// Only for "true" or "false" value
	$value = get_pref($name);
	if(is_null($value)){
		// The key is not exist
		return false;
	} else {
		if($value == 'true'){
			return true;
		} else {
			return false;
		}
	}
}

function set_pref($name, $value){
	// Alternative for update_option()
	// Reason: better naming
	global $conn;
	$sql = "SELECT id FROM prefs WHERE name = :name";
	$st = $conn->prepare($sql);
	$st->bindValue(':name', $name, PDO::PARAM_STR);
	$st->execute();
	$row = $st->fetch();
	if($row){
		$sql = "UPDATE prefs SET value = :value WHERE name = :name";
		$st = $conn->prepare($sql);
		$st->bindValue(':value', $value, PDO::PARAM_STR);
		$st->bindValue(':name', $name, PDO::PARAM_STR);
		$st->execute();
	} else {
		$sql = "INSERT INTO prefs (name, value) VALUES (:name, :value)";
		$st = $conn->prepare($sql);
		$st->bindValue(':value', $value, PDO::PARAM_STR);
		$st->bindValue(':name', $name, PDO::PARAM_STR);
		$st->execute();
	}
}

function remove_pref($name){
	global $conn;
	$sql = "DELETE FROM prefs WHERE name = :name LIMIT 1";
	$st = $conn->prepare($sql);
	$st->bindValue(':name', $name, PDO::PARAM_STR);
	$st->execute();
}

function set_plugin_pref($plugin_slug, $key, $value){
	// Similar to set_pref(), but specifically for plugins to avoid potential conflicts.
	global $conn;
	$name = $plugin_slug.'_'.$key;
	$sql = "SELECT id FROM prefs WHERE name = :name";
	$st = $conn->prepare($sql);
	$st->bindValue(':name', $name, PDO::PARAM_STR);
	$st->execute();
	$row = $st->fetch();
	if($row){
		$sql = "UPDATE prefs SET value = :value WHERE name = :name";
		$st = $conn->prepare($sql);
		$st->bindValue(':value', $value, PDO::PARAM_STR);
		$st->bindValue(':name', $name, PDO::PARAM_STR);
		$st->execute();
	} else {
		$sql = "INSERT INTO prefs (name, value) VALUES (:name, :value)";
		$st = $conn->prepare($sql);
		$st->bindValue(':value', $value, PDO::PARAM_STR);
		$st->bindValue(':name', $name, PDO::PARAM_STR);
		$st->execute();
	}
}

function get_plugin_pref($plugin_slug, $key, $default = null){
	global $conn;
	$name = $plugin_slug.'_'.$key;
	$sql = "SELECT * FROM prefs WHERE name = :name";
	$st = $conn->prepare($sql);
	$st->bindValue(':name', $name, PDO::PARAM_STR);
	$st->execute();
	$row = $st->fetch();
	if($row){
		return $row['value'];
	} else {
		// Return the default value if the key doesn't exist
		return $default;
	}
}

function get_plugin_pref_bool($plugin_slug, $key, $default = false){
	// Return boolean value
	// Only for "true" or "false" value
	$name = $plugin_slug.'_'.$key;
	$value = get_pref($name);
	if(is_null($value)){
		// The key is not exist
		return $default;
	} else {
		if($value == 'true'){
			return true;
		} else {
			return false;
		}
	}
}

function set_theme_pref($theme_slug, $key, $value){
	// Similar to set_pref(), but specifically for themes to avoid potential conflicts.
	global $conn;
	$name = $theme_slug.'_'.$key;
	$sql = "SELECT id FROM prefs WHERE name = :name";
	$st = $conn->prepare($sql);
	$st->bindValue(':name', $name, PDO::PARAM_STR);
	$st->execute();
	$row = $st->fetch();
	if($row){
		$sql = "UPDATE prefs SET value = :value WHERE name = :name";
		$st = $conn->prepare($sql);
		$st->bindValue(':value', $value, PDO::PARAM_STR);
		$st->bindValue(':name', $name, PDO::PARAM_STR);
		$st->execute();
	} else {
		$sql = "INSERT INTO prefs (name, value) VALUES (:name, :value)";
		$st = $conn->prepare($sql);
		$st->bindValue(':value', $value, PDO::PARAM_STR);
		$st->bindValue(':name', $name, PDO::PARAM_STR);
		$st->execute();
	}
}

function get_theme_pref($theme_slug, $key, $default = null){
	global $conn;
	$name = $theme_slug.'_'.$key;
	$sql = "SELECT * FROM prefs WHERE name = :name";
	$st = $conn->prepare($sql);
	$st->bindValue(':name', $name, PDO::PARAM_STR);
	$st->execute();
	$row = $st->fetch();
	if($row){
		return $row['value'];
	} else {
		// Return the default value if the key doesn't exist
		return $default;
	}
}

function get_theme_pref_bool($theme_slug, $key, $default = false){
	// Return boolean value
	// Only for "true" or "false" value
	$name = $theme_slug.'_'.$key;
	$value = get_pref($name);
	if(is_null($value)){
		// The key is not exist
		return $default;
	} else {
		if($value == 'true'){
			return true;
		} else {
			return $default;
		}
	}
}

function register_sidebar( $args = array() ){
	global $registered_sidebars;

	$i = count( $registered_sidebars ) + 1;

	$id_is_empty = empty( $args['id'] );

	$defaults = array(
		'name'           => 'Sidebar X',
		'id'             => "sidebar-$i",
		'description'    => '',
	);

	$sidebar = merge_args($args, $defaults);

	$registered_sidebars[ $sidebar['id'] ] = $sidebar;

	return $sidebar['id'];
}

function merge_args($args, $defaults = array()){
	foreach ($args as $key => $value) {
		$defaults[$key] = $value;
	}
	return $defaults;
}

function widget_aside($name, $args = array()){
	global $stored_widgets;
	global $registered_sidebars;
	if(isset($registered_sidebars[$name])){
		if(isset($stored_widgets[$name])){
			$list = $stored_widgets[$name];
			if(count($list)){
				foreach ($list as $item) {
					$key = $item['widget'];
					$widget;
					if(widget_exists($item['widget'])){
						$widget = get_widget( $item['widget'], $item );
					} else {
						continue;
					}
					$widget->widget( $item );
				}
			}
		}
	}
}

function nav_get_children($name, $parent_id = 0){
	global $conn;
	$items = [];
	$sql = "SELECT * FROM menus WHERE parent_id = :parent_id AND name = :name ORDER BY id ASC";
	$st = $conn->prepare($sql);
	$st->bindValue(":parent_id", $parent_id, PDO::PARAM_INT);
	$st->bindValue(":name", $name, PDO::PARAM_STR);
	$st->execute();
	$result = $st->fetchAll(PDO::FETCH_ASSOC);
	if (count($result)) {
		foreach ($result as $row) {
			$child = nav_get_children($name, $row['id']);
			if($child){
				$row['children'] = $child;
			}
			$items[] = $row;
		}
	} else {
		$items = [];
	}
	return $items;
}

function nav_menu_array($name = 'top_nav'){
	return nav_get_children($name, 0);
}

function get_template_path(){
	return DOMAIN . TEMPLATE_PATH;
}

function get_category_icon($slug, $array = []){
	foreach ($array as $key => $item) {
		foreach ($item as $child) {
			if($child == $slug){
				return $key;
			}
		}
	}
	return 'other';
}

function is_favorited_game($game_id){
	// Check if a game is favorited by current user
	global $login_user;
	global $conn;
	if($login_user){
		$conn = open_connection();
		$sql = "SELECT * FROM favorites WHERE user_id = :user_id AND game_id = :game_id LIMIT 1";
		$st = $conn->prepare($sql);
		$st->bindValue(":user_id", $login_user->id, PDO::PARAM_INT);
		$st->bindValue(":game_id", $game_id, PDO::PARAM_INT);
		$st->execute();
		$row = $st->fetch(PDO::FETCH_ASSOC);
		if($row){
			return true;
		} else {
			return false;
		}
	}
	return null;
}

function format_number_abbreviated($number) {
	if($number >= 1000){
		return substr($number, 0, -3).'k';
	}
	return $number;
}

function get_tags($sort = 'random', $limit = 20){
	global $conn;
	$_sort;
	if($sort == 'name'){
		$_sort = 'tags.name ASC';
	} else if($sort == 'usage'){
		$_sort = 'tags.usage_count DESC';
	} else {
		$_sort = 'RAND()';
	}
	$_limit = (int)$limit;
	$conn = open_connection();
	$sql = 'SELECT name FROM tags
	ORDER BY '.$_sort.'
	LIMIT '.$_limit;
	$st = $conn->prepare($sql);
	$st->execute();
	$tag_names = $st->fetchAll(PDO::FETCH_COLUMN);
	return $tag_names;
}
function get_tag_usage($name){
	global $conn;
	$conn = open_connection();
	$sql = 'SELECT usage_count FROM tags
	WHERE name = :name LIMIT 1';
	$st = $conn->prepare($sql);
	$st->bindValue(':name', $name, PDO::PARAM_STR);
	$st->execute();
	$count = $st->fetch(PDO::FETCH_ASSOC);
	return $count['usage_count'];
}
function get_tag_extra_field($tag, $key) {
	$json = null;
	if (is_object($tag) && isset($tag->extra_fields)) {
		$json = $tag->extra_fields;
	} elseif (is_array($tag) && isset($tag['extra_fields'])) {
		$json = $tag['extra_fields'];
	}
	if ($json !== null) {
		$fields = json_decode($json, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return null;
		}
		if (isset($fields[$key]) && $fields[$key] !== '') {
			return $fields[$key];
		}
	}
	return null;
}
function get_setting_value($name){
	if(isset(SETTINGS[$name])){
		return SETTINGS[$name]['value'];
	}
	throw new Exception("Key does not exist = ".$name);
}

function get_setting($name){
	if(isset(SETTINGS[$name])){
		return SETTINGS[$name];
	}
	throw new Exception("Key does not exist = ".$name);
}

function is_valid_json($json) {
	json_decode($json);
	return (json_last_error() === JSON_ERROR_NONE);
}

function get_csrf_token() {
	if (empty($_SESSION['csrf_token'])) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}
	return $_SESSION['csrf_token'];
}

function verify_csrf_token() {
	if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
		return false;
	}
	$isValid = hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
	// Regenerate the token
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	return $isValid;
}

function get_content_translation($content_type, $content_id, $language, $field = 'all') {
	// Sample usage : get_content_translation('game', 1, 'en', 'title');
	$conn = open_connection();
	if ($field === 'all') {
		$sql = "SELECT field, translation FROM translations WHERE content_type = :content_type AND content_id = :content_id AND language = :language";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':content_type', $content_type, PDO::PARAM_STR);
		$stmt->bindParam(':content_id', $content_id, PDO::PARAM_INT);
		$stmt->bindParam(':language', $language, PDO::PARAM_STR);
	} else {
		$sql = "SELECT translation FROM translations WHERE content_type = :content_type AND content_id = :content_id AND language = :language AND field = :field";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':content_type', $content_type, PDO::PARAM_STR);
		$stmt->bindParam(':content_id', $content_id, PDO::PARAM_INT);
		$stmt->bindParam(':language', $language, PDO::PARAM_STR);
		$stmt->bindParam(':field', $field, PDO::PARAM_STR);
	}
	$stmt->execute();
	if ($field === 'all') {
		$translations = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // This will fetch the results in key-value pairs ['title' => 'Translation of title', 'description' => 'Translation of description']
		return $translations;
	} else {
		$translation = $stmt->fetchColumn();
		return $translation === false ? null : $translation; // Will return null if no result found
	}
}

function has_content_translation($content_type, $content_id, $language = null, $specific_field = 'all') {
	$conn = open_connection();
	$sql = "SELECT 1 FROM translations WHERE content_type = :content_type AND content_id = :content_id";
	if ($language !== null) {
		$sql .= " AND language = :language";
	}
	if ($specific_field !== 'all') {
		$sql .= " AND field = :field";
	}
	$sql .= " LIMIT 1";  // Added LIMIT 1 for better performance
	$stmt = $conn->prepare($sql);
	$stmt->bindParam(':content_type', $content_type, PDO::PARAM_STR);
	$stmt->bindParam(':content_id', $content_id, PDO::PARAM_INT);
	if ($language !== null) {
		$stmt->bindParam(':language', $language, PDO::PARAM_STR);
	}
	if ($specific_field !== 'all') {
		$stmt->bindParam(':field', $specific_field, PDO::PARAM_STR);
	}
	$stmt->execute();
	return $stmt->fetchColumn() !== false;
}

function get_current_user_hash(){
	// Only current logged in user that can get their password (hashed)
	// used in user.php
	global $login_user;
	if(is_login() && isset($login_user)){
		$user = get_user($login_user->username);
		if($user){
			return $user['password'];
		}
	}
	return null;
}

function is_mobile_device(){
	// Used to check current visitor is using mobile device or not. return boolean.
	// $_SESSION used for caching, no need to call the library multiple times
	if(isset($_SESSION['_is_mobile_device'])){
		return $_SESSION['_is_mobile_device'];
	} else {
		require_once ABSPATH.'vendor/MobileDetect/MobileDetect.php';
		$detect = new \Detection\MobileDetect;
		$_SESSION['_is_mobile_device'] = $detect->isMobile();
		return $_SESSION['_is_mobile_device'];
	}
}

function has_admin_access(){
	if(is_login() && USER_ADMIN && !ADMIN_DEMO){
		return true;
	} else {
		return false;
	}
}

function is_cached_query_allowed(){
	if(defined('SKIP_QUERY_CACHE')){
		return false;
	}
	return true;
}

function get_cached_query($query_key){
	global $caching_system;
	if(!is_null($caching_system)){
		if($caching_system instanceof Memcached || $caching_system instanceof Memcache){
			$data = $caching_system->get($query_key);
			if($data !== false){
				if(get_pref_bool('query-cache_debug')){
					echo '<div style="position: relative; z-index: 1000;"><div style="background: red; color: #fff; position: absolute;">Cached: '.ucfirst(get_pref('query-cache_active')).'</div></div>';
				}
				return $data;
			} else {
				return null;
			}
		} else if($caching_system instanceof Redis){
			if($caching_system->exists($query_key)){
				if(get_pref_bool('query-cache_debug')){
					echo '<div style="position: relative; z-index: 1000;"><div style="background: red; color: #fff; position: absolute;">Cached: '.ucfirst(get_pref('query-cache_active')).'</div></div>';
				}
				return $caching_system->get($query_key);
			} else {
				return null;
			}
		}
	}
	return null;
}

function set_cached_query($query_key, $json_data){
	if(!is_string($json_data)){
		$json_data = json_encode($json_data);
	}
	global $caching_system;
	$expire_time = 7200; // seconds
	if(!is_null($caching_system)){
		if($caching_system instanceof Memcached || $caching_system instanceof Memcache){
			$exists = $caching_system->get('exists_'.$query_key);
			if(!$exists) {
				if($caching_system instanceof Memcached){
					$caching_system->set($query_key, $json_data, $expire_time);
				    $caching_system->set('exists_'.$query_key, true, $expire_time);
				} else {
					$caching_system->set($query_key, $json_data, 0, $expire_time);
				    $caching_system->set('exists_'.$query_key, true, 0, $expire_time);
				}
			}
		} else if($caching_system instanceof Redis){
			if(!$caching_system->exists($query_key)){
				$caching_system->set($query_key, $json_data, ['ex' => $expire_time]);
			}
		}
	}
}

$ca_game_types = [
	'html5' => [
		'name' => 'HTML5',
		'template' => 'default'
	]
];

function register_game_type($type_id, $name, $template) {
    global $ca_game_types;
    
    // Validate inputs
    if (empty($type_id) || empty($name) || empty($template)) {
        return false;
    }
    
    // If type exists, check name
    if (isset($ca_game_types[$type_id])) {
        // If name matches, update template
        if ($ca_game_types[$type_id]['name'] === $name) {
            $ca_game_types[$type_id]['template'] = $template;
            return true;
        }
        // If name doesn't match, don't do anything
        return false;
    }
    
    // Add new game type
    $ca_game_types[$type_id] = [
        'name' => $name,
        'template' => $template
    ];
    
    return true;
}

function get_game_types() {
    global $ca_game_types;
    return $ca_game_types;
}

?>