=== s2member Secure File Browser ===
Contributors: Potsky
Donate link: http://www.potsky.com/donate/
Tags: s2member, file, browser, shortcode, upload, manager, files
Requires at least: 3.3
Tested up to: 3.5.1
Stable tag: 0.3.5
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A plugin for browsing files from the secure-files location of the s2member WordPress Membership plugin.

== Description ==

s2Member Secure File Browser is a wordpress plugin for browsing files from the secure-files location of the [s2Member® WordPress Memberships](http://wordpress.org/extend/plugins/s2member/ "s2Member") plugin.

**Shortcode**

You can display the file browser via the shortcode `[s2member_secure_files_browser /]`.

The shortcode will display a file browser item with only granted directories for current user.

The shortcode can handle :

* `access-s2member-level0` directory for level #0 and more users
* `access-s2member-level1` directory for level #1 and more users
* `access-s2member-level2` directory for level #2 and more users
* `access-s2member-level3` directory for level #3 and more users
* `access-s2member-level4` directory for level #4 and more users
* `access-s2member-ccap-*` custom capabilities directories for according users
* any directory for all users in read only (unable to download)

All these featured folders can be located anywhere and they can be used several times.

Clicking on a file will launch the download according to the s2member files access control.

Please use the shortcode generator in the *Dashboard > s2Member Menu > Secure File Browser* to generate complex values.


**Available shortcode options**

* `dirbase` : initial directory from the s2member-files directory  
* `hidden` : show hidden files or not  
* `dirfirst` : show directories above files  
* `names` : replace files name with custom values.  
* `folderevent` : event to trigger expand/collapse  
* `expandspeed` : speed of the expand folder action in ms  
* `expandeasing` : easing function to use on expand  
* `collapsespeed` : speed of the collapse folder action in ms  
* `collapseeasing` : easing function to use on collapse  
* `multifolder` : whether or not to limit the browser to one subfolder at a time  
* `openrecursive` : whether or not to open all subdirectories when opening a directory  
* `filterdir` : a full regexp directories have to match to be displayed ([regexp format](http://www.php.net/manual/en/pcre.pattern.php "PCRE"), `preg_match` PHP function is used)  
* `filterfile` : a full regexp files have to match to be displayed ([regexp format](http://www.php.net/manual/en/pcre.pattern.php "PCRE"), `preg_match` PHP function is used)  
* `displayall` : display all items without checking if user is granted to download them
* `s2alertbox` : display the s2member confirmation box when a user tries to download a file

All informations about these options are well documented in :

* `Dashboard > s2Member > Secure File Browser` panel for admin (manage_options capability)
* `Dashboard > Tools > Secure File Browser` panel for users


**Example** (*A shortcode has to be defined on one line, here is on several lines below only for better understanding*) :  

`[s2member_secure_files_browser  
    folderevent="mouseover"  
    expandeasing="linear"  
    expandspeed="200"   
    collapseeasing="swing"  
    collapsespeed="200"  
    multifolder="0"  
    openrecursive="1"  
    dirbase="/"  
    hidden="1"  
    dirfirst="0"  
    openrecursive="1"  
    filterdir="%2F(access%7Ctata)%2Fi"
    filterfile="%2F%5C.(png%7Cjpe%3Fg%7Cgif%7Czip)%24%2Fi"
    names="access-s2member-level0:General|access-s2member-ccap-video:Videos"  
/]`  

You can generate a shortcode with complex options with the `Shortcode Generator` in the `Dashboard > s2Member > Secure File Browser` panel


**Widgets**

You can display a fully customizable widget for :

* Top downloads
* Latest downloads


**Dashboard**

The admin panel is reachable via the *Dashboard > s2Member Menu > Secure File Browser* menu.

Available features are :

* Statistics : display all downloads/top downloads/top downloaders, sort and apply filters by date, user, file, IP Address, ...
* Statistics : display current s2Member accounting, sort and apply filters by date, user, file and file
* File Browser : Rename and delete files and folders
* Shortcode generator
* Shortcode documentation
* Settings : Received an email each time a user downloads a file
* Settings : Received scheduled reports
* Settings : How many logs you want to keep ?
* Settings : Give access to others users to some parts of the admin menu


Don't hesitate to ask me new features or report bugs on [potsky.com](https://www.potsky.com/code/wordpress-plugins/s2member-secure-file-browser/ "Plugin page") !  


== Installation ==

**Requirement** : you need to install first the wonderful and free s2Member® plugin [available here](http://wordpress.org/extend/plugins/s2member/ "s2Member")  

**s2member Secure File Browser** is very easy to install (instructions) :  
* Upload the `/s2member-secure-file-browser` folder to your `/wp-content/plugins/` directory.  
* Activate the plugin through the Plugins menu in WordPress®.  


== Frequently asked questions ==

= s2Member secure files are always directly downloadable, how can I protect them by forcing php handling ? =

It is recommended to add a `deny from all` directive in your `httpd.conf` for your s2member-files directory in order to avoid people directly access your protected files. Do not put the `deny` directive in the `s2member-files/.htaccess` because this file is always regenerated by s2member and your modifications are always overwritten.

= Why s2member-files/.htaccess is not displayed ? =

Even if you set shortcode option `hidden` to `1`, `.htaccess` will never been displayed.

= Are directories `access-s2member-level*` protected if they are not in the root directory ? =

Yes ! `And access-s2member-ccap*` too !


== What's next? ==

All futures requests are handled on [GitHub](https://github.com/potsky/WordPressS2MemberFileBrowser/issues?sort=comments&state=open "GitHub")

Available in upcoming version 0.5 :

* Upload any file in the `s2member-files` directory
* Move, copy files and folders
* Create directories


== Screenshots ==

1. File browser in action
2. Admin > File browser in action
3. Admin > File browser in action when deleting a directory
4. Admin > File browser in action when renaming a directory
5. Admin > Download statistics
6. Admin > Shortcode generator
7. Admin > Shortcode documentation
8. Admin > General settings for logs management and access
9. Admin > Notification settings for email reporting
10. Widget

== Changelog ==

= 0.3.5 =
* New feature : New admin submenu with top rated downloads, higher downloaders, ...
* New feature : New shortcode option to display the s2member alert box before a download
* New feature : New shortcode option to let people view directories but must be logged in to download
* New feature : Add rights in settings for file manager and stats access
* New feature : Widget for top downloads or latest downloads
* New feature : Notification daily reports
* Enhancement : HTML entities for email reports
* Enhancement : Add WP and PHP version checks
* Security fix : Protect plugin subdirectories

= 0.3.2 =
* Hotfix for recursive browsing

= 0.3.1 =
* Publishing fix

= 0.3 =
* New language : french
* New feature : display file size
* New feature : admin : Statistics - display all downloads, sort and apply filters by date, user, file, IP Address, ...
* New feature : admin : Statistics - display current s2Member accounting, sort and apply filters by date, user, file and file
* New feature : admin : File Browser - Rename and delete files and folders
* New feature : admin : Shortcode generator
* New feature : admin : Shortcode documentation
* New feature : admin : Settings - Received an email each time a user downloads a file
* New feature : admin : Settings - How many logs you want to keep ?
* Bug fix : dirbase could not work as expected sometimes
* Enhancement : total plugin rewriting for best performance, practices and security

= 0.2.1 =
* Publishing fix

= 0.2 =
* Enhancement : file and directories icons are now clickable
* New feature : shortag option filterdir
* New feature : shortag option filterfile
* New feature : shortag option openrecursive
* Security fix : real path check perform to forbid browsing above s2member-files directory
* Bug fix : dirbase now works as expected

= 0.1 =
* First release

== Upgrade Notice ==

= 0.3.5 =
A lot of new features ! Upgrade now, seriously, it rocks !

= 0.3.2 =
This version fixes a serious browsing bug. Upgrade immediately.

= 0.3 =
This version adds improvements and admin features. Plugin is fully optimized now, upgrade immediately!

= 0.2.1 =
This version fixes a security related bug. Upgrade immediately.





