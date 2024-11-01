<?php
function WpImageZoom_read_settings()
{
	global $izoom_download_btn;
	global $izoom_compresslevel;
	global $izoom_maxzoomrate;
	global $izoom_zoomstep;
	global $izoom_maxcachesize;
	global $izoom_cachepath;

	$izoom_download_btn = get_option("izoom_download_btn", "1");
	$izoom_compresslevel= get_option("izoom_compresslevel", "70");
	$izoom_zoomstep  = get_option("izoom_zoomstep", "30");
	$izoom_maxzoomrate  = get_option("izoom_maxzoomrate", "100");
	$izoom_maxcachesize  = get_option("izoom_maxcachesize", "5");
	$izoom_cachepath  = get_option("izoom_cachepath", dirname(__FILE__) . "/work");

	$sv = (substr($izoom_cachepath,0,1)=="\\");
	for (;;) {
		$cnt = 0;
		$izoom_cachepath = str_replace("\\\\","\\", $izoom_cachepath, $cnt);
		if ($cnt==0) break;
	}
	if ($sv) {
		$izoom_cachepath = "\\".$izoom_cachepath;
	}
}
?>
