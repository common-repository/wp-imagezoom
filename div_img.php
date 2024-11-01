<?php
//require_once "HTTP/Request.php";

//require_once('FirePHPCore/FirePHP.class.php');
$iz_curdir = getcwd();
chdir("../../../");
require_once('wp-blog-header.php');
chdir($iz_curdir);
header("HTTP/1.1 200 OK");

global $wpdb;
$wpdb->show_errors();

ob_start();

include 'zoom-config.php';
include 'set_document_root.php';
//include 'work/zoom-config.php';

$src = $_GET["src"];
$max_zoomrate = "100";/*$_GET["mz"];*/
$compress_level = $_GET["cl"];

$data_dir = "";		// folder name containing divided images
$src_path = "";		// URL or file path of original image (under document root)
$org_img_path = "";	// download file path (full path)
$cache_info = array();
$total_datasize = 0;
$imgk=0; /*  0:GD 1:Imagick  */

div();

function getStatusFileName()
{
	global $data_dir;
	return $data_dir . "/status.txt";
}

/*  Concatenate two strings with slash  */
function connectPath($p1,$p2)
{
	if ($p1[strlen($p1)-1] != '/' && $p2[0]!='/') {
		return $p1 . "/" . $p2;
	}
	if ($p1[strlen($p1)-1] == '/' && $p2[0]=='/') {
		return $p1 . substr($p2,1);
	}
	return $p1 . $p2;
}

/*  Make a directory name from an URL. '/' is replaced by 'sls' and ':' is replaced by '_cln_'. */
function makeDirPathName($url)
{
	//global $data_path;
	$ret = $url;
	$ret=str_replace("/","_sls_",$ret);
	$ret=str_replace(":","_cln_",$ret);
	//if (substr($data_path, 0,1)=="/") {
	//	return connectPath($_SERVER['DOCUMENT_ROOT'], connectPath($data_path , $ret));
	//} else {
	//	return connectPath($data_path , $ret);
	//}
	//return connectPath(dirname(__FILE__), connectPath('work',$ret));
	return str_replace("\\","/",connectPath(get_option("izoom_cachepath", connectPath(str_replace("\\","/",dirname(__FILE__)),"work")) ,$ret));
}

function makeDataDir($src)
{
	global $src_path;

	//if (stripos($src, $host_root_url)==0 && !(stripos($src, $host_root_url)===false)) {
	//	$src_path = substr($src, strlen($host_root_url));
	//} else if (stripos($src, $host_root_url2)==0 && !(stripos($src, $host_root_url2)===false)) {
	//	$src_path = substr($src, strlen($host_root_url2));
	//} else {
		$src_path = $src;
	//}

	return makeDirPathName($src_path);
}


function div() 
{
	global $data_dir, $src, $src_path, $org_img_path, $compress_level;
	global $_GET;
	$data_dir = makeDataDir($src);
	global $wpdb;
	global $cache_info;

	if (!file_exists($data_dir)) {
		if (!@mkdir($data_dir,0777,TRUE)) {
			output_error("Couldnot create work directory.");
		}
	}

	if ($src_path[0]=='/') {
		$org_img_path = $_SERVER['DOCUMENT_ROOT'] . $src_path ;
	} else {
		$org_img_path = $data_dir . "/" . basename($src);
	}

	if (isset($_GET["cmd"])) {
		if ($_GET["cmd"] == "delall") {
			delete_all_data_dir();
			return;
		}
		if ($_GET["cmd"] == "delunn") {
			delete_unn_data_dir();
			return;
		}
		if ($_GET["cmd"] == "delone") {
			delete_one_data_dir($src);
			return;
		}
		if ($_GET["cmd"] == "delovr") {
			data_mnt($_GET["maxsize"]);
			return;
		}
	}

	if (file_exists(getStatusFileName())) {
		wait_another_process();
		$res = $wpdb->get_results("select complevel from ".$wpdb->prefix . "izoomimage where url='" . $src . "'");
		if ($res[0]->complevel != $compress_level) {
			delete_one_data_dir($src);
			div();
			return;
		}
	} else {
		try {
			div_image();
			$wpdb->update( $wpdb->prefix . 'izoomimage', array(
				'complevel' => $compress_level,
				'cacheinfo' => serialize($cache_info)),
				array('url'=>$src));
		} catch (Exception $e) {
			output_error($e->getMessage());
		}
	}

	output_data();
	upd_lastvisit();
}

function wait_another_process()
{
	global $data_dir;
	$count = 0;

	$fps=fopen(getStatusFileName(),"r");
	for (;;) {
		$str = fgets($fps);
		if ($str[0]=='1') {
			break;
		}
		sleep(1);
		fseek($fps,0,SEEK_SET);
		$count++;
		if ( $count==600 ) {
			output_error("Timeout");
		}
	}
	return TRUE;
}



function div_image()
{
	global $wpdb;
	global $imgk;
	global $data_dir, $src, $src_path, $org_img_path, $max_zoomrate, $compress_level, $total_datasize ;

	$image=0; $gd_img=0;

	if ($imgk) {
		$image=new Imagick();
	}

	/*  making a lock file for mutual exclusion.  */
	$fps = @fopen(getStatusFileName(), "w");
	if (!$fps) output_error("Couldnot create lock file");
	set_file_buffer($fps, 0);
	if (!flock($fps, LOCK_EX)) {
		wait_another_process();
		return;
	}
	fputs($fps,"0");  /*  0:in progress 1:finished  */

	ini_set ('user_agent', "User-Agent: Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)");

	/*  if an original image exists in the same server, read it.  */
	@ini_set('memory_limit', '512M');
	@ini_set("max_execution_time",1800);
	if ($src_path[0] == '/') {
		if ($imgk) {
			$image->readImage($org_img_path);
		} else {
			$gd_img = imagecreatefromjpeg($org_img_path);
		}
	} else {
	// if an original image exists in the another server, read it by http protocol and save (for download)
		/*$fp=fopen($src, "rb");
		$fpw=fopen($data_dir . "/" . basename($src),"wb");
		if ($fpw==FALSE) output_error("Open error");

		for (;;) {
			$imgc=fread($fp, 65536);
			if ($imgc==FALSE) break;
			if (fwrite($fpw,$imgc)===FALSE) {
				output_error("Write error");
			}
		}
		fclose($fp);
		fclose($fpw);
		$org_img_path = $data_dir . "/" . basename($src);*/
		if ($imgk) {
			$image->readImage($src/*$org_img_path*/);
		} else {
			$gd_img = imagecreatefromjpeg($src/*$org_img_path*/);
		}
	}

	/*  block(tile) size, 512x512  */
	$blk_width = 512;
	$blk_height = 512;

	if ($imgk) {
		$image_dim = $image->getImageGeometry();
		$image->resizeImage($image_dim['width']*$max_zoomrate/100, $image_dim['height']*$max_zoomrate/100,imagick::FILTER_UNDEFINED,1);
		$image_dim = $image->getImageGeometry();
	} else {
		$ix = imagesx($gd_img)*$max_zoomrate/100;
		$iy = imagesy($gd_img)*$max_zoomrate/100;
		$gd_imgs = imagecreatetruecolor  ( $ix  , $iy  );
		$image_dim['width'] = imagesx($gd_imgs);
		$image_dim['height'] = imagesy($gd_imgs);
		imagecopyresampled  ( $gd_imgs  , $gd_img  , 0  , 0  , 0, 0, $image_dim['width'], $image_dim['height'], $image_dim['width'], $image_dim['height']);
		imagedestroy($gd_img);
	}

	global $cache_info;
	$cache_info['blocksize']['width'] = $blk_width;
	$cache_info['blocksize']['height'] = $blk_height;
	$cache_info['maxzoomrate'] = $max_zoomrate;
	$cache_info['compresslevel'] = $compress_level;
	
	if ($imgk) {
		$compress_type = $image->getCompression();
		$compress_rate = $image->getCompressionQuality();
		$compress_rate = $compress_level;
	}

	for ($i=0; $i<=6; $i++) {
		$cache_info['level'][$i]['width'] = $image_dim['width'];
		$cache_info['level'][$i]['height'] = $image_dim['height'];

		if ($imgk) {
			$image2 = $image->clone();
		}
		for ($v=0; $v*$blk_height < $image_dim['height']; $v++) {
			for ($h=0; $h*$blk_width < $image_dim['width']; $h++) {
				$fna = $i . "-" . $v . "-" . $h;
				$fn = $data_dir . "/" .   "div" . $fna . ".jpg";
				if ($imgk) {
					$image2 = $image->clone();
					$image2->cropImage($blk_width, $blk_height, $h*$blk_width, $v*$blk_height);
					$image2->setImageFileName($fn);
					$image2->setCompression($compress_type);$image2->setCompressionQuality($compress_level);
					$image2->writeImage();
					$total_datasize += $image2->getImageSize();
				} else {
					$new_width = $blk_width;
					$new_height = $blk_height;
					if ( ($h+1)*$blk_width > $image_dim['width'] ) {
						$new_width  = $image_dim['width'] - $blk_width*$h;
				} 
					if ( ($v+1)*$blk_height > $image_dim['height'] ) {
						$new_height  = $image_dim['height'] - $blk_height*$v;
					} 
					$image2 = imagecreatetruecolor( $new_width, $new_height );
					imagecopy( $image2  , $gd_imgs  , 0, 0,  $h*$blk_width, $v*$blk_height, $new_width, $new_height );
					imagejpeg( $image2, $fn, $compress_level);
					imagedestroy($image2);
					$total_datasize += filesize($fn);
				}
			}
		}
		/*  Shrink the original image half w,h  */
		if ($imgk) {
			$image->resizeImage( $image_dim['width']/2 , $image_dim['height']/2, imagick::FILTER_UNDEFINED,1);
			$image_dim = $image->getImageGeometry();
		} else {
			$gd_img_tmp = imagecreatetruecolor  ( $image_dim['width']/2 , $image_dim['height']/2  );
			imagecopyresampled( $gd_img_tmp, $gd_imgs  , 0  , 0  , 0, 0, $image_dim['width']/2 , $image_dim['height']/2, $image_dim['width'] , $image_dim['height']);
			imagedestroy($gd_imgs);
			$gd_imgs = $gd_img_tmp;
			$image_dim['width']/=2;
			$image_dim['height']/=2;
		}
	}

	/*  output cache information as a json file  */
	$fpp=fopen($data_dir . '/zoom.json',"w");
	fputs($fpp, json_encode($cache_info));
	fclose($fpp);

	/* Close exclusive control file */
	fseek($fps,0,SEEK_SET);
	fputs($fps,"1");
	flock($fps, LOCK_UN);
	fclose($fps);
	
	upd_lastvisit();
	data_mnt();
}

function divimg_path2url($pathname)
{
	$path0 = str_replace("\\","/",$pathname);
	$path = str_replace("//","/",$path0);
	if (stripos($path0,"//")===0) $path = "/".$path;
	$urlroot = divimg_get_urlroot();
	$docroot = $_SERVER['DOCUMENT_ROOT'];
	if (stripos($path, $docroot) != 0) {
		return "";
	}
	$ret1 = substr($path, strlen($docroot));
	return rtrim($urlroot,"/") . "/" . ltrim($ret1,"/");
}

function divimg_get_urlroot()
{
	$urlroot = get_bloginfo('url');
	$pos = strpos($urlroot, "//");
	if (!$pos) return "";
	$pos = strpos($urlroot, "/", $pos+2);
	if ($pos) {
		$urlroot = substr($urlroot, 0, $pos);
	}
	return $urlroot;
}

function output_data()
{
	global $org_img_path, $data_dir, $src, $total_datasize;
	global $cache_info;
	global $imgk;
	global $wpdb;
	
	if (!count($cache_info)) {
		$res = $wpdb->get_results("select cacheinfo from ".$wpdb->prefix . "izoomimage where url = '".$src."'");
		if (count($res)) {
			$cache_info = unserialize($res[0]->cacheinfo);
		}
	}

	// Until Ver1.0.2, cache info was set into an xml file, 
	// so if the info is not in the DB, create cache info from existing original image.
	if (!(count($cache_info)>1)) {
		@ini_set('memory_limit', '512M');

		$cache_info['blocksize']['width'] = 512;
		$cache_info['blocksize']['height'] = 512;
		
		$image=0; $gd_img=0; $x=0; $y=0;

		if ($imgk) {
			$image=new Imagick();
			$image->readImage($src);
			$image_dim = $image->getImageGeometry();
			$x=$image_dim['width'];
			$y=$image_dim['height'];
		} else {
			$gd_img = imagecreatefromjpeg($src);
			$x = imagesx($gd_img);
			$y = imagesy($gd_img);
		}
		for ($i=0; $i<=6; $i++) {
			$cache_info['level'][$i]['width'] = $x;
			$cache_info['level'][$i]['height'] = $y;
			$x /= 2;
			$y /= 2;
		}
		$wpdb->update( $wpdb->prefix . 'izoomimage', array(
			'cacheinfo' => serialize($cache_info)),
			array('url'=>$src));
	}
	
	$cacheinfo = $cache_info;

	$out = array(
		'data_url'=>(divimg_path2url($data_dir)),
		'org_img'=> $org_img_path,
		'cacheinfo' => $cacheinfo
	);
	echo json_encode($out);
}

function output_error($str)
{
	global $org_img_path, $data_url, $src;
	$out = array(
		'data_url'=> $data_url,
		'org_img'=> $str
	);
	echo _json_encode($out);
	if (strlen($data_dir)) {
		@unlink(getStatusFileName());
	}
	exit(-1);
}

function _json_encode($ary)
{
	$out = "{";
	$keys = array_keys($ary);
	for ($i=0; $i<count($keys); $i++) {
		$ary[$keys[$i]] = str_replace("/","\\/",$ary[$keys[$i]]);
		$out = $out . "\"" . $keys[$i] . "\":\"" . $ary[$keys[$i]] . "\"";
		if ($i < count($keys)-1) {
			$out = $out . ",";
		}
	}
	$out = $out . "}";
	return $out;
}


/**********************************************************************************************************/


/*  Delete cache of image which was not used for the longest time when cache is overflow.  */
function data_mnt($maxsize = -1)
{
	global $wpdb;
	global $total_datasize, $src;

	/*  Store size of data created this time to the database. */
	$wpdb->update( $wpdb->prefix . 'izoomimage', array(
		'datasize' => $total_datasize),
		array('url'=>$src)
	);
	//$wpdb->print_error(); 

	/*  get the limitation of cache size. */
	if ($maxsize == -1) {
		$maxcachesize = get_option("izoom_maxcachesize", "50");
	} else {
		$maxcachesize = $maxsize;
	}

	while (1) {
		/*  get total data size.  */
		$totalsize = $wpdb->get_results("select sum(datasize) / 1024 / 1024 / 1024 as sm from " . $wpdb->prefix . "izoomimage");

		if ($totalsize[0]->sm > $maxcachesize ) {
			/*  delete data of image that has been unused for the longest time  */
			$del_url = $wpdb->get_results("select url from ".$wpdb->prefix."izoomimage where lastvisit=(select min(lastvisit) from ".$wpdb->prefix . "izoomimage where datasize>0) and datasize>0");
//$fp=fopen("/tmp/b.txt","a+");fwrite($fp, $del_url[0]->url."   (J\n");fclose($fp);(B
			delete_one_data_dir($del_url[0]->url);
		} else {
			break;
		}
	}
}

/*  Delete data(cache, database) of specified URL  */
function delete_one_data_dir($url)
{
	global $wpdb;
	/*  get cache path of URL  */
	$del_dir = makeDataDir($url);

	/*  set datasize of image 0 on database  */
	$wpdb->update( $wpdb->prefix . 'izoomimage', 
			array('datasize' => 0),
			array('url'=>$url));
	$d = dir($del_dir) ;
	if (!is_object($d)) {
		return;
	}
	/*  delete cache files  */
	while(false !== ($entry = $d->read())) { 
		@unlink("$del_dir/$entry");
	}
	/*  delete cache dir  */
	@rmdir($del_dir);
}

/*  Delete all cache  */
function delete_all_data_dir()
{
	global $wpdb;
	$rmv = $wpdb->get_results("select url from ".$wpdb->prefix . "izoomimage where datasize > 0");
	for ($i=0; $i<count($rmv); $i++) {
		delete_one_data_dir($rmv[$i]->url);
	}
	echo 'All data deleted.';
}

/*  Delete cache of images which are not used in posts or pages. */
function delete_unn_data_dir()
{
	global $wpdb;
	$num = 0;
	$rmv = $wpdb->get_results("select url from ".$wpdb->prefix . "izoomimage where datasize > 0");
	for ($i=0; $i<count($rmv); $i++) {
		$bbb = $wpdb->get_results("select count(*) as cnt from ".$wpdb->prefix . "posts where post_content like '%" . $rmv[$i]->url . "%' and post_status = 'publish'");
		if ($bbb[0]->cnt == "0") {
			delete_one_data_dir($rmv[$i]->url);
			$num ++;
		}
	}
	echo $num . ' data deleted.';
}


/*  Update visited date and time  */
function upd_lastvisit()
{
	global $wpdb, $src;

	$wpdb->update( $wpdb->prefix . 'izoomimage', array(
		'lastvisit' => date("Y-m-d H:i:s")),
		array('url'=>$src)
	);
}



?>
