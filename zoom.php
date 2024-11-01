<!DOCTYPE html
PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
<meta name="viewport" content="initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
<title></title>

<?php 

$iz_curdir = getcwd();
chdir ("../../..");
require_once('wp-blog-header.php');
chdir ($iz_curdir);
include 'zoom-config.php';
include_once 'read_defaults.php';
WpImageZoom_read_settings();

if (isset($_GET["id"])) {
	$prmid = $_GET["id"];

	if (strlen($prmid)>5) die();
	if (!preg_match("/^[a-zA-Z0-9]+$/", $prmid)) die();

	global $wpdb;
	global $src, $dl, $mz, $cl;
	$sql = "select i.url, p.maxzoomrate, p.complevel,downloadable,zoomstep ".
		"from ".$wpdb->prefix . "izoomparam p, ".$wpdb->prefix . "izoomimage i ".
		"where prmid='".$prmid."'" .
		"and p.imgid = i.imgid";
	$result = $wpdb->get_results($sql);
	if (count($result)) {
		$src = $result[0]->url;
		$dl = $result[0]->downloadable;
		$mz = $result[0]->maxzoomrate;
		$cl = $result[0]->complevel;
		$zs = $result[0]->zoomstep;
	}
} else {
	$src = $_GET["src"];
	$dl = $_GET["dl"];
	$mz = $_GET["mz"];
	$cl = $_GET["cl"];
	$zs = $_GET["zs"];
}
if ($dl=="-1") $dl=$izoom_download_btn;
if ($mz=="-1") $mz=$izoom_maxzoomrate;
if ($cl=="-1") $cl=$izoom_compresslevel;
if ($zs=="-1") $zs=$izoom_zoomstep;

$izoom_zoomstep_wheel=30;

echo '<script type="text/javascript">'; 
echo 'var prmid="'.$_GET["id"].'";';
echo 'var src="'.$src.'";';
echo 'var downloadable_flg="'.$dl.'";';
echo 'var zoomstep="'.$zs.'";';
echo 'var zoomstep_wheel="'.$izoom_zoomstep_wheel.'";' . "\n";
echo 'var max_zoomrate="'.$mz.'";';
echo 'var compression_level="'.$cl.'";';
echo 'var host_root_url="'. $_SERVER['HTTP_HOST'].'";';
//echo 'alert(downloadable_flg+" "+zoomstep+" "+max_zoomrate+" "+compression_level);';
echo '</script>'; ?>

<link rel="stylesheet" href="wp-imagezoom.css" type="text/css" media="screen" />
<script type="text/javascript" src="js/jquery-1.10.2.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.10.3.custom.min.js"></script>
<script type="text/javascript" src="js/jquery.mousewheel.js"></script>


<script type="text/javascript" src="zoom.js"></script>

<script type="text/javascript">
var isTouch = ('ontouchstart' in window);


function hideNavigationBar( event )
{
	if( Math.abs( window.orientation ) != 90 || event.type == "load" )
	{
		document.body.style.minHeight = Math.round( window.innerHeight + 60 / ( window.outerWidth / window.innerWidth ) ) + "px";
	}
	else
	{
		document.body.style.minHeight = window.innerHeight + "px";
	}
	window.scrollTo( 0, 0 );
}

if (isTouch) {
	window.addEventListener( 'load', hideNavigationBar, false );
	window.addEventListener( 'orientationchange', hideNavigationBar, false );
}

$(document).ready(function() {
	init_screen();
	init_data(src);
});
</script>

</head>

<body scroll="no" onselectstart="event.returnValue=false;">

<div id="wiz-buttons">
<a href="#" onclick="ZoomIn()"><img src="imgs/btn_plus.png" style="border:none" title="Zoom IN"/></a>
<div class="wiz-buttons-spacer"></div>
<a href="#" onclick="ZoomOut()"><img src="imgs/btn_minus.png" style="border:none" title="Zoom OUT"/></a>
<div class="wiz-buttons-spacer"></div>
<a href="#" onclick="ZoomReset()"><img src="imgs/btn_reset.png" style="border:none" title="Display the entire picture"/></a>
<div id="wiz-buttons-spacer-2"></div>
<a href="#" onclick="MapToggle()"><img src="imgs/btn_map.png" style="border:none" title="Thumbnail ON/OFF"/></a>
<div class="wiz-buttons-spacer"></div>
<?php
if ($dl == "1") {
	echo '<a href="#" onclick="download_f()"><img src="imgs/btn_dl.png" style="border:none" title="Download JPEG"/></a>';
}
?>
</div>
<div id="container">
<div id="outframe">
	<div id="inframe6" class="inframe"> </div>
	<div id="inframe5" class="inframe"> </div>
	<div id="inframe4" class="inframe"> </div>
	<div id="inframe3" class="inframe"> </div>
	<div id="inframe2" class="inframe"> </div>
	<div id="inframe1" class="inframe"> </div>
	<div id="inframe0" class="inframe"> </div>
</div>
<div id="float_win_outer"> 
	<div id="float_win"> 
		
		<div id="float_box_border"> <div id="float_box"> </div> </div>
	</div>
</div>
</div>
<div id="prg_anim"> </div>
</body>
</html>
