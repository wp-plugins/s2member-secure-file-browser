// jQuery File Tree Plugin
//
// Version 1.01
// + Modified by potsky to make it work with Wordpress plugin s2member-files-browser
//
// Cory S.N. LaViska
// A Beautiful Site (http://abeautifulsite.net/)
// 24 March 2008
//
// Visit http://abeautifulsite.net/notebook.php?article=58 for more information
//
// Usage: $('.fileTreeDemo').fileTree( options, callback )
//
// Options:  root           - root folder to display; default = /
//           script         - location of the serverside AJAX file to use; default = jqueryFileTree.php
//           folderevent    - event to trigger expand/collapse; default = click
//           expandspeed    - default = 500 (ms); use -1 for no animation
//           collapsespeed  - default = 500 (ms); use -1 for no animation
//           expandeasing   - easing function to use on expand (optional)
//           collapseeasing - easing function to use on collapse (optional)
//           multifolder    - whether or not to limit the browser to one subfolder at a time
//           loadmessage    - Message to display while initial tree loads (can be HTML)
//
// History:
//
// 1.01 - updated to work with foreign characters in directory/file names (12 April 2008)
// 1.00 - released (24 March 2008)
//
// TERMS OF USE
//
// This plugin is dual-licensed under the GNU General Public License and the MIT License and
// is copyright 2008 A Beautiful Site, LLC.
//
if(jQuery) (function($){

	$.extend($.fn, {
		fileTree: function(o, h) {
			// Defaults
			if( !o ) var o = {};
			if( o.script === undefined ) o.script = s2memberFilesBrowser.ajaxurl;
			if( o.folderevent === undefined ) o.folderevent = 'click';
			if( o.expandspeed === undefined ) o.expandspeed = 500;
			if( o.collapsespeed === undefined ) o.collapsespeed = 500;
			if( o.expandeasing === undefined ) o.expandeasing = null;
			if( o.collapseeasing === undefined ) o.collapseeasing = null;
			if( o.multifolder === undefined ) o.multifolder = true;
			if( o.loadmessage === undefined ) o.loadmessage = '';
			if( o.hidden === undefined ) o.hidden = '0';
			if( o.dirfirst === undefined ) o.dirfirst = '1';
			if( o.names === undefined ) o.names = '';
			o.root = '/';
			o.collapsespeed = parseInt(o.collapsespeed,10);
			o.expandspeed   = parseInt(o.expandspeed,10);
			if (o.multifolder=="0") o.multifolder = false;


			$(this).each( function() {

				function showTree(c, t) {
					$(c).addClass('wait');
					$(".jqueryFileTree.start").remove();
					$.post(o.script, {	action: 's2member-files-browser',
										dir: t,
										hidden: o.hidden,
										dirfirst: o.dirfirst,
										names: o.names,
										nonce: s2memberFilesBrowser.nonce}, function(data) {
						$(c).find('.start').html('');
						$(c).removeClass('wait').append(data);
						if( o.root == t ) {
							$(c).find('UL:hidden').show();
						}
						else {
							$(c).find('UL:hidden').slideDown({ duration: o.expandspeed, easing: o.expandeasing });
						}
						bindTree(c);
					});
				}

				function bindTree(t) {
					$(t).find('LI A').bind(o.folderevent, function() {
						if( $(this).parent().hasClass('directory') ) {
							if( $(this).parent().hasClass('collapsed') ) {
								// Expand
								if( !o.multifolder ) {
									$(this).parent().parent().find('UL').slideUp({ duration: o.collapsespeed, easing: o.collapseeasing });
									$(this).parent().parent().find('LI.directory').removeClass('expanded').addClass('collapsed');
								}
								$(this).parent().find('UL').remove(); // cleanup
								showTree( $(this).parent(), escape($(this).attr('rel').match( /.*\// )) );
								$(this).parent().removeClass('collapsed').addClass('expanded');
							} else {
								// Collapse
								$(this).parent().find('UL').slideUp({ duration: o.collapsespeed, easing: o.collapseeasing });
								$(this).parent().removeClass('expanded').addClass('collapsed');
							}
						}
						else {
							h($(this).attr('rel'));
						}
						return false;
					});
					// Prevent A from triggering the # on non-click events
					if( o.folderevent.toLowerCase != 'click' ) $(t).find('LI A').bind('click', function() { return false; });
				}
				// Loading message
				$(this).html('<ul class="jqueryFileTree start"><li class="wait">' + o.loadmessage + '<li></ul>');
				// Get the initial file list
				showTree( $(this), escape(o.root) );
			});
		}
	});

})(jQuery);