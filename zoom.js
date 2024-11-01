
cache_width=new Array(10);
cache_height=new Array(10);

var box_width;	// dimension of display area
var box_height;

var current_width; // current size;
var current_height;

var img_width;	// original image size;
var img_height;	// (if max_zoomrate < 100%, these variables are org*max_zoomrate

var max_width;
var max_height;
var min_width;
var min_height;

var default_level;	/*  suitable level for window size  */
var current_level;   /*  current level  */

var scroll_x = 0;	/*  left-top position of current display area  */
var scroll_y = 0;

var bwidth=0;	// dimension of block
var bheight=0;

var float_init_size = 150;

var float_box_x;
var float_box_y;
var float_display;

var data_url;
var download_file;

var resize_timeout_id = null;



function Scroll(level, x, y)
{
	/*  set display position of current level  */
	var fr=document.getElementById('inframe'+level.toString());
	$(fr).css("position","absolute");
	$(fr).css('left' ,x.toString()+'px');
	$(fr).css('top',y.toString()+'px');
	
	/*  set display position of default level (always there is the image of default level behind the image of current level)  */
	if (level != default_level) {
		Scroll(default_level, x,y);
	}
}



function ZoomIn(prm, pinc_centr_scrn_x, pinc_centr_scrn_y)
{
	//console.log("**** ZoomIn()");
	var ratio;
	if (typeof prm === "undefined") {
		ratio=1+zoomstep/100;	/*  ratio of zoomin  */
	} else {
		ratio = prm;

	}

	if (typeof pinc_centr_scrn_y === "undefined") {
		pinc_centr_scrn_x = box_width / 2;
		pinc_centr_scrn_y = box_height / 2;
	}

	var mz = max_zoomrate / 100;
	
	/*  if the display size of an image become larger than dot-by-dot, then set the size dot-by-dot.   */
	if (current_width*ratio > max_width) {
		dist_x =  box_width/2 - scroll_x;
		dist_x = dist_x * (max_width/current_width);
		dist_y =  box_height/2 - scroll_y;
		dist_y = dist_y * (max_width/current_width);;	
		Resize(max_width, max_height);	
	} else {
		dist_x =  box_width/2 - scroll_x;
		dist_x = dist_x * ratio;
		dist_y =  box_height/2 - scroll_y/* + (pinc_centr_scrn_y-box_height/2)*/;
		dist_y = dist_y * ratio;
		Resize(current_width*ratio, current_height*ratio);
	}
	scroll_x = box_width/2 - dist_x + (pinc_centr_scrn_x-box_width/2)*(1-ratio);
	scroll_y = box_height/2 - dist_y + (pinc_centr_scrn_y-box_height/2)*(1-ratio);

	// adjust scroll position not to overflow.
	AdjustPosition();

	Scroll(current_level, scroll_x, scroll_y);
	float_draw();		
}

//  avoid overflow
function AdjustPosition()
{

	if (scroll_x + current_width < box_width) {
		scroll_x = box_width - current_width;
	}
	if (scroll_y + current_height < box_height) {
		scroll_y = box_height - current_height;
	}

	if (scroll_x > 0) scroll_x = 0;
	if (scroll_y > 0) scroll_y = 0;

	// centering
	if (current_width < box_width) {
		scroll_x = (box_width - current_width)/2;
	} 
	if (current_height < box_height) {
		scroll_y = (box_height - current_height)/2;
	}
}

function ZoomOut(prm, pinc_centr_scrn_x, pinc_centr_scrn_y)
{
	//console.log("**** ZoomOut()");
	var ratio;
	if (typeof prm === "undefined") {
		ratio=1+zoomstep/100;
	} else {
		ratio = prm;
	}

	if (typeof pinc_centr_scrn_y === "undefined") {
		pinc_centr_scrn_x = box_width / 2;
		pinc_centr_scrn_y = box_height / 2;
	}

	var old_width = current_width;
	var old_height = current_height;
	current_width /= ratio;
	current_height /= ratio;

	if (current_width <= box_width && current_height <= box_height ) {
		if (current_width >= img_width) {
		} else if (box_width/box_height > img_width/img_height) { // the outer box is horizontally longer than an image
			current_width = box_height * img_width/img_height;
			current_height = box_height;
		} else { // the image is horizontally longer than the outer box
			current_width = box_width;
			current_height = box_width * img_height/img_width;
		}
		if (current_width >= old_width && current_width > img_width) {
			current_width = img_width;
			current_height = img_height;
		}
		//if (current_width > 
		Resize(current_width, current_height);
	} else {
		dist_x =  box_width/2 - scroll_x;
		dist_x = dist_x * (1/ratio);
		dist_y =  box_height/2 - scroll_y;
		dist_y = dist_y * (1/ratio);
		Resize(current_width, current_height);
		scroll_x = box_width/2 - dist_x;
		scroll_y = box_height/2 - dist_y;
		if (scroll_x > 0) scroll_x=0;
		if (scroll_y > 0) scroll_y=0;
	}
	AdjustPosition();
	Scroll(current_level, scroll_x, scroll_y);	
	float_draw();

}

function ZoomReset()
{
	//console.log("**** ZoomReset()");

	if (resize_timeout_id !== null) {
		clearTimeout(resize_timeout_id);
		resize_timeout_id = null;
	}

	WindowResize(1);
}

/*  thumbnail on/off  */
function MapToggle()
{
	float_display = !float_display;
	if (float_display) {
		$("#float_win_outer").fadeIn(300);
	} else {
		$("#float_win_outer").fadeOut(300);
	}
}

/*  draw the thumbnail box  */
function float_draw()
{
	var wd=box_width / current_width * float_width-2;
	var ht=box_height / current_height * float_height-2;
	if (wd > float_width) wd=float_width-2;
	if (ht > float_height) ht=float_height-2;

	if (scroll_x>0) {
		float_box_x = 0;
	} else {
		float_box_x = -(scroll_x / current_width) * float_width;
	}
	if (scroll_y>0) {
		float_box_y = 0;
	} else {
		float_box_y = -(scroll_y / current_height) * float_height;
	}

	$("#float_box_border").css("left", float_box_x.toString()+"px");
	$("#float_box_border").css("top" , float_box_y.toString()+"px");
	$("#float_box_border").css("width" , wd.toString()+"px");
	$("#float_box_border").css("height", ht.toString()+"px");
	$("#float_box").css("width" , wd.toString()+"px");
	$("#float_box").css("height", ht.toString()+"px");
	$("#float_box").css("background-color", "#800");
	$("#float_box").css('opacity',0.3);

}

var window_resize_timeout_id = null;

// floating thumbnail init
function f_init() {

	$("#float_win_outer").css("width", (float_width+12).toString()+"px");
	$("#float_win_outer").css("height", (float_height+12).toString()+"px");	
	$("#float_win").css("width", float_width.toString()+"px");
	$("#float_win").css("height", float_height.toString()+"px");
	if (!$("#test111").length)
		$("#float_win").append('<img id="test111" src="'+MakeTileFileName(Level(float_width,float_height),0,0)+'" ,style="position:relative;left:110px">');
	$("#test111").width(float_width);
	$("#test111").css("position","absolute");
	$("#test111").css("top","0px");
	float_draw();
	$("#float_win_outer").css('display','block');

	float_display = true;


}

function init_screen()
{
	box_width = $(window).width() - 0;
	box_height = window.innerHeight ? window.innerHeight : $(window).height(); - 0;
	$("#outframe").css("width", box_width.toString()+"px");
	$("#outframe").css("height", box_height.toString()+"px");	
$(window).scrollLeft(0);
$(window).scrollTop(0);
}



function init_data(src)
{
	$("#debug_win").css('display','none');	
	$("#float_win_outer").css('display','none');
	prg_anim(1);
//document.write("src="+src+"&cl="+compression_level+"&mz="+max_zoomrate+"&dl="+downloadable_flg);
//return;
	$.ajax({
		type: "GET",
		url: "div_img.php",
		data: "src="+src+"&cl="+compression_level+"&mz="+max_zoomrate+"&dl="+downloadable_flg,
		dataType: "json",
		success: function(data) {
			data_url = data.data_url;
			download_file = src;
			prg_anim(0);
			bwidth = data.cacheinfo['blocksize']['width'];
			bheight = data.cacheinfo['blocksize']['height'];
			for (var i=0; i<=6; i++) {
				cache_width[i] = data.cacheinfo['level'][i]['width'];
				cache_height[i] = data.cacheinfo['level'][i]['height'];
			}

			img_width = cache_width[0];
			img_height = cache_height[0];	

			WindowResize(1);

			f_init();
			ProcessMouseEventOnImageBody();
			ProcessMouseEventOnFloatingBox();
		//	$("#float_win_outer").draggable() ;
			$("#debug_win").draggable();

			register_resize_event();

		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			alert("error:"+textStatus);
			alert("XMLHttpRequest: "+XMLHttpRequest+"\n"+"textStatus:"+textStatus+"\n"+"errorThrown: "+errorThrown);
		}
	});
}

var prg_anim_loc = 1;
var prg_anim_add=1;
var prg_img=new Array();

function prg_anim(onoff)
{
	if (onoff) {
		$('#prg_anim').css('display','block');
		$('#prg_anim').css('position','absolute');
		$('#prg_anim').css('left', (box_width/2-20)+'px');
		$('#prg_anim').css('top',(box_height/2-5)+'px');
//		$('#prg_anim').css('background-image','url("imgs/prg_1.png")');
//		$('#prg_anim').css('background-repeat','no-repeat');
		$('#prg_anim').css('z-index','100000');
		$('#prg_anim').css('width','100px');
		$('#prg_anim').css('height','100px');
		prg_anim_loc = 1;
		setTimeout( prg_anum_sub,100);
		for (var i=1; i<=5; i++) {
			prg_img[i] = new Image();
			$(prg_img[i]).attr('src','imgs/prg_'+i+'.png')
			.css('position','absolute').css('top','0').css('left','0');
		}
	} else {
		$('#prg_anim').css('display','none');
	}
}

function prg_anum_sub()
{
	prg_anim_loc += prg_anim_add;
	if (prg_anim_loc == 6) {
		prg_anim_loc=4;
		prg_anim_add=-1;
	} else if (prg_anim_loc == 0) {
		prg_anim_loc=2;
		prg_anim_add=1;
	}
	$('#prg_anim').append($(prg_img[prg_anim_loc]));
	setTimeout( prg_anum_sub,100);
}



function WindowResize(mode)
{
	init_screen();

	pre_current_width = current_width;
	pre_current_height = current_height;

	/*  mode:all clear  */
	if (mode) {	default_level=-1;for (var i=0; i<=6; i++) LoadVisibleImages(i,1);}	

	if (1) {
		var mz = max_zoomrate / 100;
		max_width = img_width * ((mz<1)?1:mz);
		max_height = img_height * ((mz<1)?1:mz);
		min_width = box_width;
		min_height = box_height;
		if (min_width > max_width) {
			min_width = max_width;
			min_height = max_height;
		}
	}
	if (mode) {
		current_width = min_width;
		current_height = min_height;
	}

	if (1) {
		float_width = float_init_size;
		var float_size_a = Math.min(box_width, box_height)*.3;
		if (float_width > float_size_a) float_width = float_size_a;
		float_height = float_width;

		if (img_height < img_width) {
			float_height = float_width * (img_height/img_width);
		} else {
			float_width = float_height * (img_width/img_height);
		}
		f_init();
	}

	if (box_width>=current_width && box_height>=current_height) {
		if (box_width/box_height > img_width/img_height) { // Outer box is horizontally longer than the image
			current_width = box_height * img_width/img_height;
			current_height = box_height;
			scroll_x = (box_width - current_width)/2;
			scroll_y = 0;
		} else { // The image is horizontally longer than the outer box
			current_width = box_width;
			current_height = box_width * img_height/img_width;
			scroll_x = 0;
			scroll_y = (box_height - current_height)/2;
		}
		if (current_width > max_width) {
			current_width = max_width;
			current_height = max_height;
		}
	}

	if (mode) {
		//  orginal image is smaller than window
		if (current_width>img_width && current_height>img_height) {
			current_width = img_width;
			current_height = img_height;
		}
	}

	AdjustPosition();
	if (pre_current_width != current_width || pre_current_height != current_height) {
		current_level = Level(current_width, current_height);	
	}

	pre_default_level = default_level;
	default_level = Level(current_width, current_height);	
	if (!mode && default_level!=pre_default_level) {
		LoadVisibleImages(default_level, 0,false);
		Resize_lv(default_level, current_width, current_height);
		if (current_level != pre_default_level) {
			LoadVisibleImages(pre_default_level, 1,false);
		}
	}
	debug_out('default_level='+default_level+'<br>');

	if (mode) {
		current_level = default_level;
		MakeTile(default_level);
		Resize_lv(default_level, current_width, current_height);
		Scroll(default_level, scroll_x,scroll_y);
		LoadVisibleImages(default_level, 0,true);
	} else {
		MakeTile(current_level);
		Resize_lv(current_level, current_width, current_height);
		Scroll(current_level, scroll_x,scroll_y);
		LoadVisibleImages(current_level, 0,false);
	}

	float_draw();
	if (mode) ZoomIn(1.0);
}


function ProcessMouseEventOnFloatingBox()
{
	/*  map(thumbnail) drag  */
	$('#float_win_outer').bind('mousedown touchstart', function(e){
		e.preventDefault();
		e.stopPropagation();

		win_left = parseInt( $("#float_win_outer").css("left").replace('px','') );
		win_top = parseInt( $("#float_win_outer").css("top").replace('px','') );

		mousedown_x = (isTouch ? event.touches[0].pageX : e.pageX);
		mousedown_y = (isTouch ? event.touches[0].pageY : e.pageY);
		offset_x = win_left - mousedown_x;
		offset_y = win_top - mousedown_y;

		$(document).bind('mousemove touchmove.flb', function(e) {
			e.preventDefault();
			e.stopPropagation();
			new_left = (isTouch ? event.touches[0].pageX : e.pageX) + offset_x;
			new_top = (isTouch ? event.touches[0].pageY : e.pageY)  + offset_y;
			if (new_left < 0) new_left = 0;
			if (new_top < 0) new_top = 0;
			if (new_left > box_width - float_width-10) new_left = box_width - float_width-10;
			if (new_top > box_height - float_height-15) new_top = box_height - float_height-15;

			$("#float_win_outer").css("left", new_left.toString()+"px");
			$("#float_win_outer").css("top" , new_top.toString()+"px");
			
		});
		$(document).bind('mouseup touchend', function(e) {
			e.preventDefault();
			e.stopPropagation();
			$(document).unbind('mouseup touchend');
			$(document).unbind('mousemove touchmove.flb');
	
		});
	});	

	$('#float_win').mousedown(function(e){
		e.preventDefault();
		e.stopPropagation();
	});	

	$('#float_box').bind('mousedown touchstart', function(e){
		debug_out('#float_box drag start<br>');
		e.preventDefault();
		e.stopPropagation();
		mousedown_float_x = (isTouch ? event.touches[0].pageX : e.pageX);
		mousedown_float_y = (isTouch ? event.touches[0].pageY : e.pageY);
		mouse_pre_x=mousedown_float_x;
		mouse_pre_y=mousedown_float_y;

		$(document).bind('mouseup touchend',function(e){
			if (current_level != default_level) {
				LoadVisibleImages(current_level, 0);
			}
			$(document).unbind('mousemove touchmove.fl');
			$(document).unbind('mouseup touchend');
		});

		$(document).bind('mousemove touchmove.fl',function(e) {
			e.preventDefault();e.stopPropagation();
			var pgx = (isTouch ? event.touches[0].pageX : e.pageX);
			var pgy = (isTouch ? event.touches[0].pageY : e.pageY);

			float_box_y = float_box_y + (pgy - mouse_pre_y);
			float_box_x = float_box_x + (pgx - mouse_pre_x);
	
			if (float_box_x < 0) float_box_x=0;
			if (float_box_y < 0) float_box_y=0;
	
			scroll_x = -float_box_x * current_width / float_width;
			scroll_y = -float_box_y * current_height / float_height;
	
			if (scroll_x + current_width < box_width) scroll_x = box_width - current_width;
			if (scroll_y + current_height < box_height) scroll_y = box_height - current_height;
			AdjustPosition();

			Scroll(current_level, scroll_x, scroll_y);
			float_draw();
			mouse_pre_y = pgy;
			mouse_pre_x = pgx;
		});
	});
}


/*--  drag main screen  --*/

var zzz=0;

var ptcx=0;
var ptcy=0;
var pre_ptx1=0;
var pre_pty1=0;
var pre_ptx2=0;
var pre_pty2=0;
var ptx1=0;
var pty1=0;
var ptx2=0;
var pty2=0;
var pinch_first = false;


function ProcessMouseEventOnImageBody()
{
	$('#outframe').bind('mousedown touchstart', function(e){
		OnImageBody_mousedown(e);
	});

	if (!isTouch) {
		$(document).mousewheel(function(eo, delta, deltaX, deltaY) {
			if (delta>0) {
				ZoomIn(1+zoomstep_wheel/100);
			} else {
				ZoomOut(1+zoomstep_wheel/100);
			}
		});
	}
}

$(document).bind('touchmove', function(e){
	OnImageBody_mousemove(e);
});


function OnImageBody_mousedown(e)
{
	e.preventDefault();
	if (isTouch) {
		var num = e.originalEvent.touches.length;
		if ( num == 2 ) {
			zzz = 0;
			ptx2 = /*e.originalEvent*/event.touches[0].pageX;
			pty2 = /*e.originalEvent*/event.touches[0].pageY;
			pre_ptx1 = ptx1;
			pre_pty1 = pty1;
			pre_ptx2 = ptx2;
			pre_pty2 = pty2;
			pinch_first = true;

			ptcx = (ptx1+ptx2)/2;
			ptcy = (pty1+pty2)/2;

			return;
		}
		ptx1 = event.touches[0].pageX;
		pty1 = event.touches[0].pageY;
	}

	zzz=1;
	mousedown_x = (isTouch ? /*e.originalEvent*/event.touches[0].pageX : e.pageX);
	mousedown_y = (isTouch ? /*e.originalEvent*/event.touches[0].pageY : e.pageY);
	mouse_pre_x=mousedown_x;
	mouse_pre_y=mousedown_y;

	$(document).bind('mouseup touchend',function(e){
		OnImageBody_mouseup(e)
	});

	$(document).bind('mousemove',function(e){
		OnImageBody_mousemove(e);
	});
}

function OnImageBody_mouseup(e)
{
	e.preventDefault();
	if (!zzz) return;
	zzz=0;
	if (current_level != default_level) {
		LoadVisibleImages(current_level, 0);
	}
	$(document).unbind('mousemove');
	$(document).unbind('mouseup');
	//$(document).unbind('touchmove');
	$(document).unbind('touchend');
}


function OnImageBody_mousemove(e)
{
	e.preventDefault();
	if (isTouch) {
		if (event.touches.length == 2) {
			OnImageBody_pinchInOut();
			return;
		}
	}

	if (!zzz) return;
	var pageX = (isTouch ? event.touches[0].pageX : e.pageX);
	var pageY = (isTouch ? event.touches[0].pageY : e.pageY);
	scroll_y = scroll_y + (pageY - mouse_pre_y);
	scroll_x = scroll_x + (pageX - mouse_pre_x);

	if (scroll_x > 0) scroll_x=0;
	if (scroll_y > 0) scroll_y=0;

	if (scroll_x + current_width < box_width) scroll_x = box_width - current_width;
	if (scroll_y + current_height < box_height) scroll_y = box_height - current_height;
	AdjustPosition();
	Scroll(current_level, scroll_x, scroll_y);
	float_draw();

	mouse_pre_y = pageY;
	mouse_pre_x = pageX;
}

var zzcnt=0;

function OnImageBody_pinchInOut()
{
	if (event.touches.length != 2) return;

	pre_ptx1 = ptx1;
	pre_pty1 = pty1;
	pre_ptx2 = ptx2;
	pre_pty2 = pty2;

	ptx1 = event.touches[0].pageX;
	pty1 = event.touches[0].pageY;
	ptx2 = event.touches[1].pageX;
	pty2 = event.touches[1].pageY;

	if (pinch_first) {
		pinch_first = false;
		return;
	}

	pre_dist = Math.sqrt( (pre_ptx2-pre_ptx1)*(pre_ptx2-pre_ptx1)+(pre_pty2-pre_pty1)*(pre_pty2-pre_pty1) );
	dist = Math.sqrt( (ptx2-ptx1)*(ptx2-ptx1) + (pty2-pty1)*(pty2-pty1) );

	if (pre_dist < dist)  {
		dt = dist - pre_dist;
		mul = 1 + (dt*8/current_width);
		ZoomIn(mul, ptcx, ptcy);
	} else if (pre_dist > dist)  {
		dt = dist - pre_dist;
		mul = 1 + (-dt*12/current_width);
		ZoomOut(mul, ptcx, ptcy);
	}
}



var mousedown_x = -1;
var mousedown_y = -1;
var mousedown_float_x = -1;
var mousedown_float_y = -1;
var mouse_pre_x = -1;
var mouse_pre_y = -1;



// get level from image size
function Level(wdt, hgt)
{
	var ret_level;
	for (ret_level=6; ret_level>=0; ret_level--) {
		if (wdt <= cache_width[ret_level] && hgt <= cache_height[ret_level]) {
//debug_out("level="+ret_level.toString()+"-----"+wdt.toString()+","+hgt.toString()+"-----"+cache_width[ret_level].toString()+","+cache_height[ret_level].toString()+"<br>");
			return ret_level;
		}
	}
	return 0;
}

// get filename of tiled-image from level and position
function MakeTileFileName(level, vpos, hpos)
{
	ret = data_url + '/div' + 
		level.toString()+'-'+vpos.toString()+'-'+hpos.toString() + '.jpg';
	return ret;
}

// make tiled DIVs of specified level
function MakeTile(level)
{
	var num_x = Math.floor(cache_width[level] / bwidth) + ((cache_width[level] % bwidth) ? 1:0);
	var num_y = Math.floor(cache_height[level] / bheight) + ((cache_height[level] % bheight) ? 1:0);
	//document.write(num_x,',',num_y,' ',bwidth,',',bheight,' ',cache_width[level],',',cache_height[level]);

	var fr=document.getElementById('inframe' + level.toString());
	if (fr== null) {
		alert('null!!! inframe' + level.toString());	
	}
	
	for (var x=0; x<num_x; x++) {
		for (var y=0; y<num_y; y++) {

			div_id = 'tile'+level.toString()+'_'+y.toString()+'_'+x.toString();
			var div_el = document.getElementById(div_id);
			if ($(div_el).length==0) {
				div_el = document.createElement("div");
				div_el.id = div_id;
				fr.appendChild(div_el);				
				$(div_el).css("position","absolute");
				$.data(div_el, 'width', 0);
				$.data(div_el, 'height', 0);
				$.data(div_el, 'xpos', 0);
				$.data(div_el, 'ypos', 0);
				fr.appendChild(div_el);
			}
		}
	}
}



function Resize(wd, ht)
{
	var new_level;

	if (resize_timeout_id) {
		clearTimeout(resize_timeout_id);
		resize_timeout_id = null;
	}

	Resize_lv(default_level, wd, ht);	

	new_level = Level(wd, ht);
	debug_out('new_level='+new_level+' w='+wd+' h='+ht+' ('+cache_width[new_level]+','+cache_height[new_level]+')');
	current_width = wd;
	current_height = ht;

	// if level changes, remove images of old level from memory and make tiles of new level
	if (new_level != current_level) {
		if (current_level != default_level) {
			LoadVisibleImages(current_level, 1);
		}
		MakeTile(new_level);
	}

	/*  他のリサイズが１秒行われなかった場合に画像読み込み(短時間に素早いリサイズ時のレスポンス改善のため)  */
	if (resize_timeout_id!=null) {
		clearTimeout(resize_timeout_id);
	}
	if (new_level != default_level) {
		Resize_lv(new_level, wd, ht);
		resize_timeout_id=setTimeout(function(){
			debug_out('delayed_resize current_level='+new_level+'<br>');
			LoadVisibleImages(new_level, 0);
			resize_timeout_id = null;
		}, 1000);
	}
	
	current_level = new_level;
}


/*  resize image of specified level  */
function Resize_lv(level, wd, ht)
{
	var num_x = Math.floor(cache_width[level] / bwidth) + ((cache_width[level] % bwidth) ? 1:0);
	var num_y = Math.floor(cache_height[level] / bheight) + ((cache_height[level] % bheight) ? 1:0);
	var ratio_w = wd / cache_width[level];
	var ratio_h = ht / cache_height[level];
	var t;
	
	var bwidth_norm = (bwidth * ratio_w);
	var bwidth_right = wd - bwidth_norm * (num_x-1);
	var bheight_norm = (bheight * ratio_h);	
	var bheight_bottom = ht - bheight_norm * (num_y-1);

	var xw, yw;

	for (var x=0; x<num_x; x++) {
		for (var y=0; y<num_y; y++) {
			div_id = 'tile'+level.toString()+'_'+y.toString()+'_'+x.toString();
			t = document.getElementById(div_id);			
			
//			if (x == num_x-1) {
				//xw = bwidth_right;
//			} else {
				//xw = bwidth_norm;
//			}
			//if (y == num_y-1) {
//				yw = bheight_bottom;
			//} else {
//				yw = bheight_norm;
			//}
			//$.data(t, 'width', xw);
			//$.data(t, 'height', yw);

			if (t === null) continue;

			$.data(t, 'xpos', Math.floor(x*(bwidth_norm)));
			$.data(t, 'ypos', Math.floor(y*(bheight_norm)));

			$(t).css( 'top', $.data(t, 'ypos').toString()+'px');
			$(t).css( 'left',$.data(t, 'xpos').toString()+'px');

			if (x==num_x-1) {
				$.data(t, 'width', wd - $.data(t, 'xpos') +1);
			} else {
				$.data(t, 'width', Math.floor((x+1)*(bwidth_norm)) - $.data(t, 'xpos'));
			} 

			if (y==num_y-1) {
				$.data(t, 'height', ht - $.data(t, 'ypos') +1);
			} else {
				$.data(t, 'height', Math.floor((y+1)*(bheight_norm)) - $.data(t, 'ypos'));
			} 

			var tt=document.getElementById(div_id+'p');
			$(tt).css('width', $.data(t, 'width').toString()+'px');
			$(tt).css('height',$.data(t, 'height').toString()+'px');
		}
	}
}

var v_cnt;
var img_trash_cnt=0;

// load visible part and unload unvisible part.
function LoadVisibleImages(level, all_clear, flg_fadein)
{
	//console.log("LoadVisibleImages(%d,%d,%d)", level, all_clear, flg_fadein);
	flg_fadein = typeof(flg_fadein) != 'undefined' ? flg_fadein : false;

	var num_x = Math.floor(cache_width[level] / bwidth) + ((cache_width[level] % bwidth) ? 1:0); /*  num of blocks (horizontal)  */
	var num_y = Math.floor(cache_height[level] / bheight) + ((cache_height[level] % bheight) ? 1:0);  /*  num of blocks (vertical)  */
	var t,tt, x1,y1,xw,yw;

	var dbg1="", dbg2="";

	/*  progression animation  */
	if (flg_fadein) prg_anim(1);

	v_cnt = 0;
	for (var y=0; y<num_y; y++) {
		for (var x=0; x<num_x; x++) {
			div_id = 'tile'+level.toString()+'_'+y.toString()+'_'+x.toString();
			t = document.getElementById(div_id);
			if (t===null) continue;
			x1 = $.data(t, 'xpos') + scroll_x;
			y1 = $.data(t, 'ypos') + scroll_y;
			xw = $.data(t, 'width') ;
			yw = $.data(t, 'height');
			
			div_id_p = div_id + "p";
			var tt=document.getElementById(div_id_p);
			var visible = (x1 >= -xw && x1 <= box_width && y1 >= -yw && y1 <= box_height);

			if ((visible || (!visible && level==default_level)) && all_clear == 0 ) {
		window.scrollTo(0, 0);

				if (tt === null) {
					v_cnt ++;
					var img = new Image();
					$(img).data('loaded',0);
					$(img).data('del',0);
					$(t).append($(img));
					$(img).hide()
					.addClass('zxc')
					.load(function() {
						$(this).data('loaded',1);
						if ($(this).data('del')==1) {
							$(this).remove();
							return;
						}
						v_cnt --;
						if (flg_fadein) {
							if (v_cnt == 0) {
								prg_anim(0);
								$('.zxc').fadeIn(1000);
							}
						} else {
							$(this).fadeIn(200);
						}
						
					})
					.error(function(x,e) {
						str = x.e + ' ' + e;
						alert("error "+x.e);
					})
					.attr('src',MakeTileFileName(level, y, x))
					.attr('width',xw)
					.attr('height',yw)
					.attr('id',div_id_p);
				}
					

//					var html_str='<img id="' + div_id_p +'" src="' + MakeTileFileName(level, y, x) + '" width=' + xw.toString() + ' height=' + yw.toString() + ' oncontextmenu="return false"/>'
//					$(t).append(html_str);
//				}
				dbg1='1 ';
			} else {
				if (tt !== null && level!=default_level) {
					if ($('#'+div_id_p).data('loaded')==1) {
						$('#'+div_id_p).remove();
					} else {
						$('#'+div_id_p).data('del',1);
						$('#'+div_id_p).attr('id','img_del'+img_trash_cnt++);
					}
				}
				dbg1='0 ';
			}
			dbg2=dbg2+dbg1;
		}
		dbg2=dbg2+"<br>";
	}
	dbg2=dbg2+"LoadVisibleImages("+level+","+all_clear+")<br>";
	//debug_out(dbg2);
}

var register_resize_id = -1;

function register_resize_event()
{
	register_resize_id = setInterval(
		function() {
			if (v_cnt) {
				return;
			}
			$(window).resize(function(){
				WindowResize(0);
			});
			clearInterval(register_resize_id);
		}, 100
	);
}


function download_f()
{
	location.href="download.php?id="+prmid;
}


function debug_out(str)
{
	//console.log(str);
}
