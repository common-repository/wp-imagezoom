<?php
/*
Plugin Name: Wp-ImageZoom
Plugin URI: http://tempspace.net/plugins/?page_id=74
Description: Zooming and panning large images similar to google maps.
Version: 1.1.0
Author: Atsushi Ueda
Author URI: http://atsushiueda.com/wtest
License: GPL2
*/

include 'set_document_root.php';
include 'zoom-config.php';
include_once 'read_defaults.php';
//WpImageZoom_read_settings();
//include 'work/zoom-config.php';
function dbg($str){$fp=fopen("/tmp/zoomdebug.txt","a");fwrite($fp,$str . "\n");fclose($fp);}

function WpImageZoom_init() {
	wp_enqueue_script('jquery');
}
add_action('init', 'WpImageZoom_init');

	/**
	 * MCE ツールバーが初期化される時に発生します。
	 */
	function onMceInitButtons()
	{
	    // 編集権限のチェック
	    if( !current_user_can( "edit_posts" ) && !current_user_can( "edit_pages" ) ) { return; }
	 
	    // ビジュアル エディタ時のみ追加
	    if( get_user_option( "rich_editing" ) == "true" )
	    {
	        add_filter( "mce_buttons",          "onMceButtons"         );
	        add_filter( "mce_external_plugins", "onMceExternalPlugins" );
	    }
	}
	 
	/**
	 * MCE ツールバーにボタンが追加される時に発生します。
	 *
	 * @param   $buttons    ボタンのコレクション。
	 */
	function onMceButtons( $buttons )
	{
	    array_push( $buttons, "separator", "MyPlugin01" );
	    return $buttons;
	}
	 
	/**
	 * MCE ツールバーのボタン処理が登録される時に発生します。
	 *
	 * @param   $plugins    ボタンの処理のコレクション。
	 */
	function onMceExternalPlugins( $plugins )
	{
	    $pluginDirUrl = WP_PLUGIN_URL . "/" . array_pop( explode( DIRECTORY_SEPARATOR, dirname( __FILE__ ) ) ) . "/";
	    $plugins[ "MyPluginButtons" ] = "{$pluginDirUrl}/editor_plugin.js";
	    return $plugins;
	}
	 
//add_action('init', 'onMceInitButtons');


function WpImageZoom_install() {

	global $wpdb;
	//$wpdb->show_errors();
	$izoom_db_version = "0.37";
	$table_name = $wpdb->prefix . "izoomimage";
	$table_name2 = $wpdb->prefix . "izoomparam";
	$installed_ver = get_option( "izoom_db_version" ,"0" );
	//echo '<script type="text/javascript">alert("'.$installed_ver.'");</script>';

	if( $installed_ver != $izoom_db_version ) {
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');	
		$sql = "CREATE TABLE " . $table_name . " (
			url VARCHAR(512) NOT NULL,
			imgid int,
			complevel tinyint(3),
			maxzoomrate tinyint(3),
			datasize int,
			lastvisit datetime,
			cacheinfo longtext,
			UNIQUE KEY imgid (imgid)
		);";
		dbDelta($sql);
		$sql = "CREATE TABLE " . $table_name2 . " (
			imgid int,
			prmid VARCHAR(8),
			maxzoomrate tinyint(3),
			zoomstep tinyint(3),
			downloadable tinyint(1),
			complevel tinyint(3),
			UNIQUE KEY imgid (imgid)
		);";
		dbDelta($sql);

		//$wpdb->print_error(); 
		/* $rows_affected = $wpdb->insert( $table_name, array( 'time' => current_time('mysql'), 'name' => $welcome_name, 'text' => $welcome_text ) ); */
		 update_option("izoom_db_version", $izoom_db_version);
	}
}
$res = register_activation_hook(WP_PLUGIN_DIR . '/wp-imagezoom/wp-imagezoom.php', 'WpImageZoom_install');


function WpImageZoom_shortcode($atts, $content = null) {
	global $izoom_download_btn;
	global $izoom_compresslevel;
	global $izoom_maxzoomrate;
	global $izoom_zoomstep;

	do_shortcode($content);
	WpImageZoom_read_settings();

	//$zoomstep = $izoom_zoomstep;
	//$maxzoomrate = $izoom_maxzoomrate;
	//$compressionlevel = $izoom_compresslevel;
	//$download = $izoom_download_btn;

	$zoomstep = "-1";
	$maxzoomrate = "-1";
	$compressionlevel = "-1";
	$download = "-1";


	if (is_array($atts)) {
		extract($atts);
	}

	return WpImageZoom_filter($content, $zoomstep, $maxzoomrate, $compressionlevel, $download);
}
add_shortcode('izoom', 'WpImageZoom_shortcode',12);


function WpImageZoom_filter($raw_text, $zoomstep, $maxzoomrate, $complevel, $downloadable ) {

  	global $table;
	global $wpdb;
	global $enable_id;
	global $wp_root_path;
		
	$str = $raw_text;
	$str2=""; //加工後出力内容
	$pos = 0;

	for ($ii=0; $ii<99999; $ii++) {
		$find = strpos(substr($str, $pos), "href=\"");

		if ($find === false) { 
			break;
		}
			
		$str2 = $str2 . substr($str, $pos, $find+6); //「href="」まで追加
		$find2 = strpos(substr($str, $pos+$find+6), "\"");  //閉じる二重引用符を検索

		if ($find2 === false ) { // in the case "]" not found
			$pos += 8;
			continue;
		}
	
		$url = substr($str, $pos+$find+6, $find2);
		$url_org = $url;
		$find_att = strpos($url, "attachment_id=");
		if ($find_att > 1) {
			$attid = substr($url, $find_att+14);
			$result = $wpdb->get_results("select guid from ".$wpdb->prefix."posts where ID=".$attid);
			$url = $result[0]->guid;
		}

		$result = $wpdb->get_results("select imgid ".
			"from ".$wpdb->prefix."izoomimage ".
			"where url='".$url."'");
		$imgid = 0;
		if (count($result)==0) {
			$result = $wpdb->get_results("select coalesce(max(imgid),0)+1 as id ".
				"from ".$wpdb->prefix."izoomimage ");

			$imgid = $result[0]->id;
			$wpdb->insert( $wpdb->prefix . 'izoomimage', array(
				'url' => $url,
				'imgid' => $imgid,
				'complevel' => $complevel,
				'datasize' => 0,
				'lastvisit' => "1970-01-01 00:00:00"));
		} else {
			$imgid = $result[0]->imgid;
		}

		$prmid = "";
		$result2 = $wpdb->get_results("select prmid from ".$wpdb->prefix."izoomparam where ". 				"imgid='" . $result[0]->imgid ."'".
			" and zoomstep=".$zoomstep.
			" and maxzoomrate=".$maxzoomrate.
			" and complevel=".$complevel.
			" and downloadable=".$downloadable);
			//echo '<script type="text/javascript">alert("'.count($result2).'");</script>';

		if (count($result2)==0) {
			$prmid = wpImageZoom_makePrmID();
			$wpdb->insert( $wpdb->prefix . 'izoomparam', array(
				'imgid' => $imgid,
				'prmid' => $prmid,
				'complevel' => $complevel,
				'zoomstep' => $maxzoomstep,
				'maxzoomrate' => $maxzoomrate,
				'zoomstep' => $zoomstep,
				'downloadable' => $downloadable)
			 );
		} else {
			$prmid = $result2[0]->prmid;
		}

		$str_add = "";

		$str_add = rtrim(plugins_url(),"/")."/". basename(dirname(__FILE__));
		/*  IDが付けられている画像は、URLのパラメタにIDを付加する。そうでなければ各属性を出力  */
		if ($enable_id == true) {
			$str_add = $str_add . "/zoom.php?id=" . $prmid;
		} else {
			$str_add = $str_add . "/zoom.php?src=" . $url . 
				'&mz=' . $maxzoomrate.
				'&cl=' . $complevel.
				'&dl=' . $downloadable;
		}
		$str2 = $str2 . "javascript:void(0)\" onclick=\"wpzoom_fullscr('" . $str_add . "')";

		$pos += $find+6;
		$pos += strlen($url_org);

	}
	$str2 = $str2 . substr($str, $pos);
	$raw_text = $str2;
	return $raw_text;
}


function wpImageZoom_makePrmID()
{
	global $wpdb;
	while (1) {
		$id = "";
		for ($i=0; $i<5; $i++) {
			$id = $id . substr("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz", rand(0,61), 1);
		}
		$result2 = $wpdb->get_results("select srcid from ".$wpdb->prefix."izoomparam where prmid='".$id."'");
		if (count($result2)==0) return $id;
	}
}




/*  <head>sectionに、新規ウインドウを開くためのJavascriptを追加   */
function WpImageZoom_title_filter( $title ) {

	echo  "<script type=\"text/javascript\">\n" . 
		"var wpiz_win = null; \n" . 
		"function wpzoom_fullscr(ref)\n" . 
		"{ \n" . 
		"	flg_resize = !(wpiz_win && wpiz_win.open && !wpiz_win.closed); \n" . 
		"	xw=screen.availWidth-4; yw=screen.availHeight-4; \n" .
		"	wpiz_win = (window.open(ref,\n" . 
		"		'wpimagezoom',\n" . 
		"		'top=2,left=2,width='+xw+',height='+yw+',scrollbars=no,favorites=no,location=no,menu=no,status=no,resizable=no',false));\n" . 
		"	wpiz_win.focus(); \n".
		"	if (0/*flg_resize*/) { \n" . 
		"		wpiz_win.moveTo(2,2);\n" . 
		"		wpiz_win.resizeTo(xw,yw);\n" . 
		"	} \n" . 
		"}\n" . 
		"</script>\n";
} 
add_action( 'wp_head', 'WpImageZoom_title_filter' );




// 設定メニューの追加
function wp_imagezoom_plugin_menu()
{
	/*  設定画面の追加  */
	add_submenu_page('options-general.php', 'WP_ImageZoom Configuration', 'WP_ImageZoom', 'manage_options', 'WpImazeZoom-submenu-handle', 'WpImageZoom_magic_function'); 
}
add_action('admin_menu', 'wp_imagezoom_plugin_menu');


/*  設定画面出力  */
function WpImageZoom_magic_function()
{
	global $izoom_download_btn;
	global $izoom_compresslevel;
	global $izoom_maxzoomrate;
	global $izoom_zoomstep;
	global $izoom_maxcachesize;
	global $izoom_cachepath;

	/*  Save Changeボタン押下でコールされた場合、$_POSTに格納された設定情報を保存  */
	if ( isset($_POST['updateIZoomSetting'] ) ) {
		echo '<div id="message" class="updated fade"><p><strong>Options saved.</strong></p></div>';
		update_option('izoom_download_btn', $_POST['izoom_download_btn']);
		update_option('izoom_compresslevel', $_POST['izoom_compresslevel']);
		update_option('izoom_maxzoomrate', $_POST['izoom_maxzoomrate']);
		update_option('izoom_zoomstep', $_POST['izoom_zoomstep']);
		update_option('izoom_maxcachesize', $_POST['izoom_maxcachesize']);
		update_option('izoom_cachepath', $_POST['izoom_cachepath']);
	}


	WpImageZoom_read_settings();

	$download_btn = $izoom_download_btn;
	$compresslevel = $izoom_compresslevel;
	$maxzoomrate = $izoom_maxzoomrate;
	$zoomstep = $izoom_zoomstep;
	$maxcachesize = $izoom_maxcachesize;
	$cachepath = $izoom_cachepath;

	$plugin = dirname(__FILE__);
	?>
	<script type="text/javascript">
	function clear_all_cache() {
		ret = confirm("All cache is about to be cleared. Remaking cache is hard job for your server. Are you sure?");
		if (ret == true) {
			jQuery.ajax({
				url: "../wp-content/plugins/wp-imagezoom/div_img.php",
				type: 'GET',
				data: 'cmd=delall',
				success: function (data) {
					alert(data);
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					alert('<?php echo $plugin;?>error');
					alert("XMLHttpRequest: "+XMLHttpRequest+"\n"+
						"textStatus:"+textStatus+"\n"+
						"errorThrown: "+errorThrown);
				},
				async: false
			});
		}
	}
	function clear_unr_cache() {
		jQuery.ajax({
			url: "../wp-content/plugins/wp-imagezoom/div_img.php",
			type: 'GET',
			data: 'cmd=delunn',
			success: function (data) {
				alert(data);
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				alert('error');
			},
			async: false
		});
	}
	function clear_oversized_cache(form) {
		jQuery.ajax({
			url: "../wp-content/plugins/wp-imagezoom/div_img.php",
			type: 'GET',
			data: 'cmd=delovr&maxsize='+form.izoom_maxcachesize.value,
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				alert('error');
			},
			async: false
		});
	}
	</script>
	<div class="wrap">
		<h2>WP-ImageZoom configuration</h2>
		<!-- <form method="post" action="../wp-content/plugins/wp-imagezoom/db.php"> -->
		<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">

		<h3>Default settings</h3>
		<table class="form-table">
		<tr>
		<th>Image quality (1-100)</th>
		<td><input type="text" name="izoom_compresslevel" value="<?php echo $compresslevel; ?>" /></td>
		</tr>
		<tr>
		<th>Download button</th>
		<td>
		<input type="radio" name="izoom_download_btn" value="1" <?php echo $download_btn=="1"?"checked":""; ?>>Enable<br>
		<input type="radio" name="izoom_download_btn" value="0" <?php echo $download_btn=="0"?"checked":""; ?>>Disable
		</td>
		</tr>
		<tr>
		<th>Zoom Step (%)</th>
		<td><input type="text" name="izoom_zoomstep" value="<?php echo $zoomstep; ?>" /></td>
		</tr>
		<tr>
		<th>Maximum zoom rate (%)</th>
		<td><input type="text" name="izoom_maxzoomrate" value="<?php echo $maxzoomrate; ?>" /></td>
		</tr>

		</table>

		<h3>Cache settings</h3>

		<table class="form-table">
		<tr>
		<th scope="row">Maximum cache size (GB) </th>
		<td><input type="text" name="izoom_maxcachesize" value="<?php echo $maxcachesize; ?>" /></td>
		</tr>
		<tr>
		<th scope="row">Cache location</th>
		<td><input type="text" name="izoom_cachepath" value="<?php echo $cachepath;?>" style="width:500px" /></td>
		</tr>
		<tr>
		<th><a href="#" onclick="clear_all_cache();">Clear all cache</a></th>
		</tr>
		<tr>
		<th><a href="#" onclick="clear_unr_cache();">Clear cache of unused images</a></th>
		</tr>
		</table>
		<input type="hidden" name="_action" value="update" />
		<input type="hidden" name="_page_options" value="izoom_maxcachesize,izoom_maxzoomrate" />
		<p class="submit">
			<input type="submit" name="updateIZoomSetting" class="button-primary" value="<?php _e('Save  Changes')?>" onclick="clear_oversized_cache(this.form);" />
		</p>
		</form>


	</div>
	<?php 
}
?>
