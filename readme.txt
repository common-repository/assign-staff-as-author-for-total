=== Assign Staff as Author for Total ===
Contributors: WPExplorer
Donate link: https://www.wpexplorer.com/donate/
Tags: total theme, staff post type, custom author
Requires at least: 4.6
Requires PHP: 7.0
Tested up to: 6.6
Stable Tag: 2.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Assign staff members as the "author" for any page or post to be displayed in the post meta or author bio.

== Description ==
Adds a new field to the post edit screen for all public post types so you can select a "Staff Member" (a post from the staff custom post type) as the "author" of the article to be displayed on the front-end. This will not actually change the author of the post itself but simply change the name displayed on the front-end in the author bio and post meta sections.

Note: This plugin is intended to be used with the [Total Theme](https://totalwptheme.com/) for WordPress and the Total Theme Core plugin which registers the Staff post type. If you are using it with a different theme that has a built-in staff post type or a different theme using a [custom post types plugin](https://wordpress.org/plugins/post-types-unlimited/) to register the staff members, it may or may not work depending how the theme is coded. This plugin hooks into the author_link, the_author and get_the_author_description to make it's changes.

== Installation ==

1. Upload 'assign-staff-as-author-for-total' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. You can now assign any post to a Staff Member so that they will appear as the author on the front-end

== Changelog ==

= 2.0 =

* Added support for assigning staff members as authors via quick edit.
* Added bulk edit for easily assiging staff members as authors to multiple posts.
* Added new settings page under Settings > Staff as Authors with a couple useful settings.
* Added support for WPML.
* Updated the Tested up to version.

= 1.1 =

* Updated plugin to hook into pre_get_avatar which attempts to display the staff member featured image instead of the author avatar so it can display the correct avatar in theme card styles.
* Fixed display issues in Gutenberg editor.
* Updated the save mechanism.

= 1.0 =

* First official release