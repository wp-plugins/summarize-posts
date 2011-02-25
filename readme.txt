=== Summarize Posts ===
Contributors: fireproofsocks
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=355ULXBFXYL8N
Tags: 
Requires at least: 3.0.1
Tested up to: 3.1
Stable tag: 0.4

Offers highly flexible alternatives to WordPress' built-in get_posts() function.

== Description ==

Summarize posts offers an improved alternative to the built-in WordPress `get_posts()`, `query_posts()`, and `WP_Query` methods for retrieving posts. The new functions are exposed both to your theme files and to your posts via shortcode tags. You can search by taxonomy terms, post title, status, or just about any other criteria you can think of. You can also paginate the results and format them in a flexible and tokenized matter. These functions are loop-agnostic: they can be used inside or outside of the loop.

`<?php 
$args = array('author'=>'fireproofsocks');
SummarizePosts::summarize($args);
?>`

This plugin is still in development! Beta testers only at this point! Please be willing to file bug reports at http://code.google.com/p/wordpress-summarize-posts/issues/list

== Installation ==

1. Upload this plugin's folder to the `/wp-content/plugins/` directory or install it using the traditional WordPress plugin installation.
1. Activate the plugin through the 'Plugins' menu in the WordPress manager.
1. Now you can use the shortcode to list all types of posts.

== Frequently Asked Questions ==

= How do I file bugs? = 

Thank you for your interest in this plugin! I want it to be as good as possible, so thank you for taking the time to file a bug! You can file bugs at http://code.google.com/p/wordpress-summarize-posts/issues/list

= How can I use this to produce a list of posts? =

This plugin can be used inside theme files or via shortcodes inside of your post's main content block, e.g. paste the following to show posts by a certain author.

`[summarize-posts author="yourname"]`

= How can I use this inside a theme file? =

Accessing the classes directly offers much greater flexibility. 

`
$Q = new GetPostsQuery();
$Q->output_type = OBJECT;
$args = array('date_min'=>'2011-01-01');
$results = $Q->get_posts($args);
foreach ($results as $r)
{
	print $r->post_title;
}
`
= How can I debug this? =

There are a couple things built-in to the classes here to help locate errors: the *SQL* attribute will print out the MySQL query used, and the *format_errors()* method will print a list of errors.

`$Q = new GetPostsQuery();
$args = array('date_min'=>'2011-01-01');
$results = $Q->get_posts($args);
print $Q->format_errors();
print $Q->SQL;
`

== Screenshots ==


== Changelog ==


= 0.4 =

Initial public release.


== Requirements ==

* WordPress 3.0.1 or greater
* PHP 5.2.6 or greater
* MySQL 5.0.41 or greater

These requirements are tested during WordPress initialization; the plugin will not load if these requirements are not met. Error messaging will fail if the user is using a version of WordPress older than version 2.0.11. 


== About ==

This plugin was written to help offer a simpler way to summarize posts of all kinds. There are other similar plugins available, but none of them offered the control in selecting posts or in formatting the results that I wanted.


== Future TO-DO == 

* Add help links to wiki.


== Upgrade Notice ==

= 0.4 =

Initial public release.

== See also and References ==
* See the project homepage: http://code.google.com/p/wordpress-summarize-posts/