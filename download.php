<?php

include 'zoom-config.php';
require_once('../../../wp-blog-header.php');

$prmid = $_GET["id"];

if (!isset($_GET["id"]) || strlen($prmid)!=5 || strstr($prmid,"\"") || strstr($prmid,"'")) {
  echo "<html><title>Error</title><body>Error</body></html>";
  exit;
}

$filename = "";

$result = $wpdb->get_results(
	"select url ".
	"from ".$wpdb->prefix . "izoomparam p, ".$wpdb->prefix . "izoomimage i ".
	"where prmid='".$prmid."'" .
	"and p.imgid = i.imgid");
if (count($result)) {
	$filename = $result[0]->url;
}

// required for IE, otherwise Content-disposition is ignored
if(ini_get('zlib.output_compression'))
  ini_set('zlib.output_compression', 'Off');

// addition by Jorg Weske
$file_extension = strtolower(substr(strrchr($filename,"."),1));

if( $filename == "" ) 
{
  echo "<html><title>eLouai's Download Script</title><body>ERROR: invalid ID.</body></html>";
  exit;
} elseif ( ! file_exists( $filename ) ) 
{
//  echo "<html><title>eLouai's Download Script</title><body>ERROR: File not found. USE force-download.php?file=filepath</body></html>";
//  exit;
};
switch( $file_extension )
{
  case "pdf": $ctype="application/pdf"; break;
  case "exe": $ctype="application/octet-stream"; break;
  case "zip": $ctype="application/zip"; break;
  case "doc": $ctype="application/msword"; break;
  case "xls": $ctype="application/vnd.ms-excel"; break;
  case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
  case "gif": $ctype="image/gif"; break;
  case "png": $ctype="image/png"; break;
  case "jpeg":
  case "jpg": $ctype="image/jpg"; break;
  default: $ctype="application/force-download";
}
header("Pragma: public"); // required
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private",false); // required for certain browsers \
// change, added quotes to allow spaces in filenames, by Rajkumar Singh
header("Content-Disposition: attachment; filename=\"".basename($filename)."\";" );
header("Content-Transfer-Encoding: binary");
//header("Content-Length: ".filesize($filename));
header("Content-Type: application/force-download");
readfile("$filename");
exit();

?>
    
