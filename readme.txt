=== Plugin Name ===
Contributors: aueda
Donate link: http://tempspace.net/plugins/
Tags: image,images,large,big,zoom,zooming,pan,panning,imagezoom,jpeg,jpg,detail,ajax,photo,photos,photograph,photography,foto,fotos,gallery,picture,magnify
Requires at least: 3.6.0
Tested up to: 3.6.1
Stable tag: 1.1.0

Zooming and panning large images similar to google maps.

== Description ==

This plugin enable you to view detail of large images. <br>Like the google maps, this plugin makes divided images from an original image in several zoom level and store them as cache. So visitors can zoom in/out large images without waiting long time if cache is prepared.

(Demo page)<br>
http://atsushiueda.com/wtest/

You can get the documentation from the following URL:<br>
http://tempspace.net/plugins/?page_id=74

(Basic usage)
Write a shortcode like following:<br>
[izoom]&lt;a href="URL of an image to zoom.jpg"&gt;&lt;img src="URL of a thumbnail.jpg"&gt;&lt;/a&gt;[/izoom]

You can put multiple images between shortcode tags.


== Installation ==

Install the plugin like usual ones. Then activate it.

You have to specify the cache directory in the admin page. Also you may have to set the permission of the directory so that the plugin can write data into it.

== Screenshots ==

1. Entire screen.

2. Controles<br>
Please visit demo page: http://tempspace.net/plugins/?page_id=76

== Changelog ==

= 1.1.0 =
* Added support for smartphones (pinch zoom, swipe)
* Solved a security problem.

= 1.0.7 =
* Solved the problem that image was not shown when some other plugins are installed.

= 1.0.6 =
* Solved the problem that image was not shown when the link of the image was the attachment post URL.

= 1.0.5 =
* Solved a security problem.

= 1.0.4 =
* Solved a security problem.

= 1.0.3 =
* Solved the problem that the plugin does not work with IE.
* Solved the problem that the number of backslashes in the setting screen is doubled each time when you press 'Save Changes' button.
* Previously cache infomation was stored in XML files. Now that is stored in the database.

= 1.0.2 =
* Modified to solve environment-specific problems (IIS).

= 1.0.1 =
* Setting page moved from plugin tab to setting tab.
* Modified to solve environment-specific problems (IIS, multi-site).

= 1.0.0 =
* Release version.

= 0.9 =
* Pre-release version 2.

= 0.2 =
* Pre-release version.
