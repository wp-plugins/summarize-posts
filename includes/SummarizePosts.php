<?php
/**
* SummarizePosts
*
* Handles the 'summarize-posts' shortcode and related template tags.
* Placeholders must be lowercase
*/
class SummarizePosts
{
	const name 			= 'Summarize Posts';
	const wp_req_ver 	= '3.1';
	const php_req_ver 	= '5.2.13';
	const mysql_req_ver	= '5.1.44';
	
	// used in the wp_options table
	const db_key			= 'summarize_posts';
	const admin_menu_slug 	= 'summarize_posts';
	
	public static $default_options = array(
		'group_concat_max_len' 	=> 4096,	// integer
		'output_type'			=> OBJECT, // ARRAY_N, OBJECT
	);
	// The default options after being read from get_option()
	public static $options; 
	
	// This goes to true if we were unable to increase the group_concat_max_len MySQL variable.
	public static $manually_select_postmeta = false;
	
	const txtdomain 	= 'summarize-posts';
	
	const result_tpl = '<li><a href="[+permalink+]">[+post_title+]</li>';
	
	// One placeholder can be designated 
	public static $help_placeholder = '[+help+]';
		
	// These are defaults for OTHER settings, outside of the get_posts()
	public static $formatting_defaults = array(
		'get_meta'		=> false,
		'before' 		=> '<ul class="summarize-posts">',
		'after' 		=> '</ul>',
		'paginate' 		=> false,
		'tpl'			=> null,
		'help'			=> false,
	);
	
	//! Private functions
	//------------------------------------------------------------------------------
	/**
	* 
	* @param	string	$content
	* @param	array	$args associative array
	*/
	private static function _get_tpl($content, $args)
	{

		$content = trim($content);
		if ( empty($content) )
		{
			$content = self::result_tpl; // default
		}
		elseif( !empty($args['tpl']) )
		{
			// strip possible leading slash
			$args['tpl'] = preg_replace('/^\//','',$args['tpl']);
			$file = ABSPATH .$args['tpl'];
			
			if ( file_exists($file) )
			{
				$content = file_get_contents($file);
			}
			else
			{
				// throw an error
			}
		}
		// Read from between [summarize-posts]in between[/summarize-posts]
		else
		{
			$content = html_entity_decode($content); 
			$content = str_replace(array('&#8221;','&#8220;'), '"', $content );
			$content = str_replace(array('&#8216;','&#8217;'), "'", $content );		
		}
		return $content;
	}


	
	
	//! Public Functions
	/**
	* Create custom post-type menu
	*/
	public static function create_admin_menu()
	 {
	 	add_options_page( 
	 		'Summarize Posts', 					// page title
	 		'Summarize Posts', 					// menu title
			'manage_options', 					// capability
	 		self::admin_menu_slug, 				// menu slug
	 		'SummarizePosts::get_admin_page' // callback	 	
	 	);
	}
	
	
	//------------------------------------------------------------------------------
	/**
	* 
	*/
	public static function format_results($results, $args)
	{
		$output = '';
		foreach ( $results as $r )
		{
			$output .= self::parse($args['tpl_str'], $r);
		}
		return $args['before'] . $output . $args['after'];
	}
	
	//------------------------------------------------------------------------------
	/**
	* 
	*/
	public static function get_admin_page()
	{
		if ( !empty($_POST) && check_admin_referer('summarize_posts_options_update','summarize_posts_admin_nonce') )
		{
			$new_values = array();
			$new_values['group_concat_max_len'] = (int) $_POST['group_concat_max_len'];
			$new_values['output_type'] = $_POST['output_type'];
			update_option( self::db_key, $new_values);
			$msg = '<div class="updated"><p>Your settings have been <strong>updated</strong></p></div>';
		}
		// Read Stored Values (i.e. recently saved values)
		self::$options = get_option(self::db_key, self::$default_options);
		
		$object_selected = '';
		$array_a_selected = '';
		if ( self::$options['output_type'] == OBJECT )
		{
			$object_selected = 'selected="selected"';	
		}
		else
		{
			$array_a_selected = 'selected="selected"';	
		}
		
		include('admin_page.php');
	
	}
	
	
	//------------------------------------------------------------------------------
	/**
	* Get from Array. Safely retrieves a value from an array, bypassing the 'isset()' 
	* errors.
	* INPUT:
	* 	$array (array) the array to be searched
	* 	$key (str) the place in that key to return (if available)
	* 	$default (mixed) default value to return if that spot in the array is not set
	*/
	public static function get_from_array($array, $key, $default='') 
	{		
		if ( isset($array[$key]) ) 
		{
			return $array[$key];
		}
		else
		{
			return $default;
		}
	}

	//------------------------------------------------------------------------------
	/**
	* @param	object	$QueryObj	Instantiation of GetPostsQuery
	* @param	array	$raw_args	Raw arguments passed to SummarizePosts::summarize
	* @param	array	$formatting_args	Arguments that control the output format.
	*/
	public static function get_help_msg($QueryObj, $raw_args, $formatting_args)
	{
		return 'Helpful message goes here...';
	}
	
	//------------------------------------------------------------------------------
	/**
	* Retrieves a complete post object, including all meta fields.
	* Ah... get_post_custom() will treat each custom field as an array, because in WP
	* you can tie multiple rows of data to the same fieldname (which can cause some
	* architectural related headaches).
	* 
	* At the end of this, I want a post object that can work like this:
	* 
	* print $post->post_title;
	* print $post->my_custom_field; // not $post->my_custom_fields[0];
	* 
	* INPUT: $id (int) valid ID of a post (regardless of post_type).
	* OUTPUT: post object with all attributes, including custom fields.
	*/
	public function get_post_complete($id)
	{
		$complete_post = get_post($id, self::$options['output_type']);
		if ( empty($complete_post) )
		{
			return array();
		}
		$custom_fields = get_post_custom($id);
		if (empty($custom_fields))
		{
			return $complete_post;
		}
		foreach ( $custom_fields as $fieldname => $value )
		{
			if ( self::$options['output_type'] == OBJECT )
			{			
				$complete_post->$fieldname = $value[0];
			}
			// ARRAY_A
			else
			{
				$complete_post[$fieldname] = $value[0];		
			}
		}
		
		return $complete_post;	
	}

	//------------------------------------------------------------------------------
	/**
	* This is our __construct() effectively.
	* Handle a couple misspellings here
	*/
	public static function initialize()
	{	
		add_shortcode('summarize-posts', 'SummarizePosts::summarize');
		add_shortcode('summarize_posts', 'SummarizePosts::summarize');
	}

	//------------------------------------------------------------------------------
	/**
	SYNOPSIS: a simple parsing function for basic templating.
	INPUT:
		$tpl (str): a string containing [+placeholders+]
		$hash (array): an associative array('key' => 'value');
	OUTPUT
		string; placeholders corresponding to the keys of the hash will be replaced
		with the values and the string will be returned.
	*/
	public static function parse($tpl, $hash) 
	{
		$verbose_placeholders = array(); // used for populating [+help+]
		
	    foreach ($hash as $key => $value) 
	    {
	    	if ( !is_array($value) )
	    	{
	        	$tpl = str_replace('[+'.$key.'+]', $value, $tpl);
	        }
	    }
	    
	    // Remove any unparsed [+placeholders+]
	    $tpl = preg_replace('/\[\+(.*?)\+\]/', '', $tpl);
	    
	    return $tpl;
	}

	//------------------------------------------------------------------------------
	/**
	* print_notices
	
	Print errors if they were thrown by the tests. Currently this is triggered as 
	an admin notice so as not to disrupt front-end user access, but if there's an
	error, you should fix it! The plugin may behave erratically!
	INPUT: none... ideally I'd pass this a value, but the WP interface doesn't make
		this easy, so instead I just read the class variable: SummarizePostsTests::$errors
	OUTPUT: none directly.  But errors are printed if present.
	*/
	public static function print_notices()
	{
		if ( !empty(SummarizePostsTests::$errors) )
		{

			$error_items = '';
			foreach ( SummarizePostsTests::$errors as $e )
			{
				$error_items .= "<li>$e</li>";
			}

			$msg = sprintf( __('The %s plugin encountered errors! It cannot load!', self::txtdomain)
				, self::name);

			printf('<div id="summarize-posts-warning" class="error">
					<p>
					<strong>%1$s</strong>
					<ul style="margin-left:30px;">
						%2$s
					</ul>
				</p>
				</div>'
				, $msg
				, $error_items);

		}
	}
	//------------------------------------------------------------------------------
	/**
	*
	http://codex.wordpress.org/Template_Tags/get_posts
	sample usage
	
	shortcode params:
	
	'numberposts'     => 5,
    'offset'          => 0,
    'category'        => ,
    'orderby'         => any valid column from the wp_posts table (minus the "post_")
    	ID
		author
		date
		date_gmt
		content
		title
		excerpt
		status
		comment_status
		ping_status
		password
		name
		to_ping
		pinged
		modified
		modified_gmt
		content_filtered
		parent
		guid
		menu_order
		type
		mime_type
		comment_count
		
		rand -- randomly sort results. This is not compatible with the paginate options! If set, 
			the 'paginate' option will be ignored!
    
    'order'           => 'DESC',
    'include'         => ,
    'exclude'         => ,
    'meta_key'        => ,
    'meta_value'      => ,
    'post_type'       => 'post',
    'post_mime_type'  => ,
    'post_parent'     => ,
    'post_status'     => 'publish'

	** CUSTOM **
	get_meta
	before
	after
	paginate	true|false


placeholders:
	[+help+]
	
	[shortcode x="1" y="2"]<ul>Formatting template goes here</ul>[/shortcode]
	
	The $content comes from what's between the tags.
	
A standard post has the following attributes:
    [ID] => 6
    [post_author] => 2
    [post_date] => 2010-11-13 20:13:28
    [post_date_gmt] => 2010-11-13 20:13:28
    [post_content] => http://pretasurf.com/blog/wp-content/uploads/2010/11/cropped-LIFE_04_DSC_0024.bw_.jpg
    [post_title] => cropped-LIFE_04_DSC_0024.bw_.jpg
    [post_excerpt] => 
    [post_status] => inherit
    [comment_status] => closed
    [ping_status] => open
    [post_password] => 
    [post_name] => cropped-life_04_dsc_0024-bw_-jpg
    [to_ping] => 
    [pinged] => 
    [post_modified] => 2010-11-13 20:13:28
    [post_modified_gmt] => 2010-11-13 20:13:28
    [post_content_filtered] => 
    [post_parent] => 0
    [guid] => http://pretasurf.com/blog/wp-content/uploads/2010/11/cropped-LIFE_04_DSC_0024.bw_.jpg
    [menu_order] => 0
    [post_type] => attachment
    [post_mime_type] => image/jpeg
    [comment_count] => 0
    [filter] => raw



But notice that some of these are not very friendly.  E.g. post_author, the user expects the author's name.  So we do some duplicating, tweaking to make this easier on the user.

Placeholders:

Generally, these correspond to the names of the database columns in the wp_posts table, but some 
convenience placeholders were added.

drwxr-xr-x   8 everett2  staff   272 Feb  5 20:16 .
[+ID+]
[+post_author+]
[+post_date+]
[+post_date_gmt+]
[+post_content+]
[+post_title+]
[+post_excerpt+]
[+post_status+]
[+comment_status+]
[+ping_status+]
[+post_password+]
[+post_name+]
[+to_ping+]
[+pinged+]
[+post_modified+]
[+post_modified_gmt+]
[+post_content_filtered+]
[+post_parent+]
[+guid+]
[+menu_order+]
[+post_type+]
[+post_mime_type+]
[+comment_count+]
[+filter+]

Convenience:

[+permalink+]
[+the_content+]
[+the_author+]
[+title+]
[+date+]
[+excerpt+]
[+mime_type+]
[+modified+]
[+parent+]
[+modified_gmt+]

;

	*/
	public static function summarize($raw_args=array(), $content_tpl = null)
	{	
		$formatting_args = shortcode_atts( self::$formatting_defaults, $raw_args );

		$formatting_args['tpl_str'] = self::_get_tpl($content_tpl, $formatting_args);		

		//print_r($formatting_args); exit;
		$Q = new GetPostsQuery( $raw_args );
		$results = $Q->get_posts();

		// Print help message.  Should include the SQL statement, errors
		if ($other_params['help'])
		{
			print self::get_help_msg($Q, $raw_args, $formatting_args);
		}

		print self::format_results($results, $formatting_args);

	}
}
/*EOF*/