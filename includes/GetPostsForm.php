<?php
/**
 * GetPostsForm
 * 
 * Generate search forms that feed into GetPostsQuery->get_posts()
 *
 * Requires: GetPostsQuery
 *
 * $args is an array of valid keys from the GetPostsQuery $defaults: each string
 * in the array defines a filter used by the GetPostsQuery::get_posts() function.
 * Including an item in the $args will cause the generate() function to generate
 * the HTML form elements to allow the user to control that filter on the search
 * form. I.e. the more $args supplied, the longer and more complex the search 
 * form will be.
 *
 * Form element names will correspond exactly to the arguments accepted by the 
 * get_posts() function so that this will work: GetPostsQuery::get_posts($_POST);
 */
class GetPostsForm
{
	/**
	 * The super simple default search form includes only a search term.
	 */
	public static $defaults = array(
		'search_term'
	);

	// Set @ __construct so we can localize the "Search" button.
	public static $form_tpl;
	
	/*
	 * This prefix is added before all form element names to avoid collisions
	 * in the $_POST array.
	 * e.g. <input type="text" name="gpf_search_term" />
	 */
	public $name_prefix = 'gpf_';
	// As above, but for the id attribute.
	public $id_prefix = 'gpf_';

	public $nonce_field; // set @ __construct. Contains the whole field to be used.
	public $nonce_action = 'sp_search';
	public $nonce_name = 'sp_search';
	
	/**
	 * Ultimately passed to the parse function, this contains an associative 
	 * array. The key is the name of the placeholder, the value is what it will
	 * get replaced with.
	 */
	public $placeholders = array();

	// Any active properties 
	public $props = array();

	/**
	 * Any valid key from GetPostsQuery (populated @ instantiation)
	 */
	private $valid_props = array();

	//------------------------------------------------------------------------------
	//! Magic Functions
	//------------------------------------------------------------------------------
	public function __construct($args=array()) {
		$this->valid_props = array_keys(GetPostsQuery::$defaults);
		if (empty($args)) {
			// push this through validation.
			//foreach(self::$defaults as $k => $v) {
			//	$this->__set($k, $v);
			//}
			$this->props = self::$defaults;
		}
		
		$this->nonce_field = wp_nonce_field($this->nonce_action, $this->nonce_name, true, false);
		self::$form_tpl = '<form method="post" action="" class="sp_getpostsquery">
			[+nonce+]
			[+content+]
			<input type="submit" value="'.__('Search', SummarizePosts::txtdomain).'" />
			</form>';
	}
	
	//------------------------------------------------------------------------------
	/**
	 * Interface with $this->props
	 */
	public function __get($k)
	{
		if ( in_array($k, $this->props) )
		{
			return $this->props[$k];
		}
		else
		{
			return __('Invalid parameter:') . $k;
		}
	}
	
	//------------------------------------------------------------------------------
	/**
	 * @param	string $k for key
	 * @return	boolean
	 */
	public function __isset($k)
	{
		return isset($this->props[$k]);
	}
	

	//------------------------------------------------------------------------------
	/**
	 * Interface with $this->props
	 */
	public function __unset($k) 
	{
		unset($this->props[$k]);
	}
	
	//------------------------------------------------------------------------------
	/**
	* Validate and set parameters
	* Interface with $this->props	
	* @param	string	$k for key
	* @param	mixed	$v for value
	*/
	public function __set($k, $v)
	{
		if (in_array($k, $this->valid_props)) 
		{
			$this->props[$k] = $v;
		}
		else
		{

		}
    }
    //------------------------------------------------------------------------------
    //! Private Functions (named after GetPostsQuery args)
    //------------------------------------------------------------------------------
    //------------------------------------------------------------------------------
	/**
	 * List which posts to append to search results.
	 */
    private function _append() {
    	return sprintf('<label for="%s" class="sp_text_label">%s</label> <input type="text" name="%s" id="%s" value="" />'
	    	, $this->id_prefix . 'append'
	    	, __('Append the following post IDs to all search results', SummarizePosts::txtdomain)
    		, $this->name_prefix . 'append'
    		, $this->id_prefix . 'append');
    }

	//------------------------------------------------------------------------------
	/**
	 * Post author (display name)
	 */
    private function _author() {
    	$output = '<label class="sp_dropdown_label" for="'.$this->id_prefix .'author">'.__('Author', SummarizePosts::txtdomain).'</label><br/>
    		<select size="5" name="'.$this->name_prefix .'author" id="'.$this->id_prefix .'author">';
    	
    	global $wpdb;
 
		$authors = $wpdb->get_results("SELECT ID, display_name from $wpdb->users ORDER BY display_name");
		foreach ($authors as $a) {
			$output .= sprintf('<option value="%s">%s (%s)</option>', $a->display_name, $a->display_name, $a->ID);
		}
		$output .= '</select>';
		return $output;
    }

	//------------------------------------------------------------------------------
	/**
	 * date_column: some js help, but the user can write in their own value for dates stored in custom fields (i.e. custom columns)
	 * post_date, post_date_gmt, post_modified, post_modified_gmt
	 */
	private function _date_column() {
		$output = '
		<label for="'.$this->id_prefix.'date_column">'.__('Date Column',SummarizePosts::txtdomain).'</label>
			<input type="text" name="'.$this->name_prefix.'date_column" class="" id="'.$this->id_prefix.'date_column"  value="" />
			<span class="button" onclick="jQuery(\'#'.$this->id_prefix.'date_column\').val(\'post_date\');">post_date</span><br/>
			<span class="button" onclick="jQuery(\'#'.$this->id_prefix.'date_column\').val(\'post_date_gmt\');">post_date_gmt</span><br/>
			<span class="button" onclick="jQuery(\'#'.$this->id_prefix.'date_column\').val(\'post_modified\');">post_modified</span><br/>
			<span class="button" onclick="jQuery(\'#'.$this->id_prefix.'date_column\').val(\'post_modified_gmt\');">post_modified_gmt</span><br/>';
	}

	//------------------------------------------------------------------------------
	/**
	 * Date format: some js help, but the user can write in their own value.
	 */
	private function _date_format() {
		$output = '
		<label for="'.$this->id_prefix.'date_format">'.__('Date Format',SummarizePosts::txtdomain).'</label>
			<input type="text" name="'.$this->name_prefix.'date_format" class="" id="'.$this->id_prefix.'date_format"  value="" />
			<span class="button" onclick="jQuery(\'#'.$this->id_prefix.'date_format\').val(\'mm/dd/yy\');">mm/dd/yy</span><br/>
			<span class="button" onclick="jQuery(\'#'.$this->id_prefix.'date_format\').val(\'yyyy-mm-dd\');">yyyy-mm-dd</span><br/>
			<span class="button" onclick="jQuery(\'#'.$this->id_prefix.'date_format\').val(\'yy-mm-dd\');">yy-mm-dd</span><br/>
			<span class="button" onclick="jQuery(\'#'.$this->id_prefix.'date_format\').val(\'d M, y\');">d M, y</span><br/>
			<span class="button" onclick="jQuery(\'#'.$this->id_prefix.'date_format\').val(\'d MM, y\');">d MM, y</span><br/>
			<span class="button" onclick="jQuery(\'#'.$this->id_prefix.'date_format\').val(\'DD, d MM, yy\');">DD, d MM, yy</span><br/>';
	}
	
	//------------------------------------------------------------------------------
	/**
	 * date_max
	 */
    private function _date_max() {
    	$output = '
	    	<script>
				jQuery(function() {
					jQuery("#'.$this->id_prefix.'date_max").datepicker({
						dateFormat : "yy-mm-dd"
					});
				});
			</script>
			<label for="'.$this->id_prefix.'date_max">'.__('Date Maximum',SummarizePosts::txtdomain).'</label>
			<input type="text" name="'.$this->name_prefix.'date_max" class="" id="'.$this->id_prefix.'date_max"  value="" />';
    }

	//------------------------------------------------------------------------------
	/**
	 * date_min
	 */
    private function _date_min() {
    	$output = '
	    	<script>
				jQuery(function() {
					jQuery("#'.$this->id_prefix.'date_min").datepicker({
						dateFormat : "yy-mm-dd"
					});
				});
			</script>
			<label for="'.$this->id_prefix.'date_min">'.__('Date Minimum',SummarizePosts::txtdomain).'</label>
			<input type="text" name="'.$this->name_prefix.'date_min" class="" id="'.$this->id_prefix.'date_min"  value="" />';
    }
    //------------------------------------------------------------------------------
	/**
	 * Lists which posts to exclude
	 */
    private function _exclude() {
    	return sprintf('<label for="%s" class="sp_text_label">%s</label> <input type="text" name="%s" id="%s" value="" />'
	    	, $this->id_prefix . 'exclude'
	    	, __('Exclude the following post IDs', SummarizePosts::txtdomain)
    		, $this->name_prefix . 'include'
    		, $this->id_prefix . 'include');
    }
    
    //------------------------------------------------------------------------------
	/**
	 * List which posts to include
	 */
    private function _include() {
    	return sprintf('<label for="%s" class="sp_text_label">%s</label> <input type="text" name="%s" id="%s" value="" />'
	    	, $this->id_prefix . 'include'
	    	, __('Include the following post IDs', SummarizePosts::txtdomain)
    		, $this->name_prefix . 'include'
    		, $this->id_prefix . 'include');
    }
    //------------------------------------------------------------------------------
    /**
     * Limits the number of posts returned OR sets the number of posts per page 
     * if pagination is on.
     */
    private function _limit() {
    	return sprintf('<label for="%s" class="sp_text_label">%s</label> <input type="text" name="%s" id="%s" value="" />'
	    	, $this->id_prefix . 'limit'
	    	, __('Limit', SummarizePosts::txtdomain)
    		, $this->name_prefix . 'limit'
    		, $this->id_prefix . 'limit');
    }

	//------------------------------------------------------------------------------
	/**
	 * match_rule
	 */
    private function _match_rule() {
    	$output = '<label class="sp_dropdown_label" for="'.$this->id_prefix .'match_rule">'.__('Match Rule', SummarizePosts::txtdomain).'</label><br/>
    		<select name="'.$this->name_prefix .'match_rule" id="'.$this->id_prefix .'match_rule">';

		$output .= sprintf('<option value="contains">%s</option>', __('Contains', SummarizePosts::txtdomain));
		$output .= sprintf('<option value="starts_with">%s</option>', __('Starts with', SummarizePosts::txtdomain));
		$output .= sprintf('<option value="ends_with">%s</option>', __('Ends with', SummarizePosts::txtdomain));

		$output .= '</select>';
				
		return $output;
    }

    //------------------------------------------------------------------------------
    /**
     * Meta key is the name of a custom field from wp_postmeta: should be used with meta_value
     */
    private function _meta_key() {
    	return sprintf('<label for="%s" class="sp_text_label">%s</label> <input type="text" name="%s" id="%s" value="" />'
	    	, $this->id_prefix . 'meta_key'
	    	, __('Meta Key', SummarizePosts::txtdomain)
    		, $this->name_prefix . 'meta_key'
    		, $this->id_prefix . 'meta_key');
    }

    //------------------------------------------------------------------------------
    /**
     * Meta key is the name of a custom field from wp_postmeta: should be used with meta_value
     */
    private function _meta_value() {
    	return sprintf('<label for="%s" class="sp_text_label">%s</label> <input type="text" name="%s" id="%s" value="" />'
	    	, $this->id_prefix . 'meta_value'
	    	, __('Meta Value', SummarizePosts::txtdomain)
    		, $this->name_prefix . 'meta_value'
    		, $this->id_prefix . 'meta_value');
    }

	//------------------------------------------------------------------------------
	/**
	 * Offset
	 */
    private function _offset() {
    	return sprintf('<label for="%s" class="sp_text_label">%s</label> <input type="text" name="%s" id="%s" value="" />'
	    	, $this->id_prefix . 'offset'
	    	, __('Limit', SummarizePosts::txtdomain)
    		, $this->name_prefix . 'offset'
    		, $this->id_prefix . 'offset');
    }

	//------------------------------------------------------------------------------
	/**
	 * Lets the user select a valid post_type
	 */
    private function _omit_post_type() {
    	$output = '<span class="sp_radio_label">'.__('Omit Post Types', SummarizePosts::txtdomain).'</span><br/>';
    
    	$post_types = get_post_types();
    	foreach ($post_types as $k => $pt) {
    		$output .= sprintf('<input type="checkbox" name="%s[]" id="%s" value="%s"> <label for="%s>%id</label><br/>'
    			, $this->name_prefix . 'omit_post_type'
    			, $this->id_prefix . 'omit_post_type_' . $pt
    			, $pt
    			, $this->id_prefix . 'omit_post_type_' . $pt
    			, $pt
    		);
    	}
		return $output;
    }

	//------------------------------------------------------------------------------
	/**
	 * Order of results: ascending, descending 
	 */
    private function _order() {
    	return '<span class="sp_radio_label">'.__('Sort Order', SummarizePosts::txtdomain).'</span><br/>	
    		<input name="'.$this->name_prefix . 'order" id="'. $this->id_prefix.'order_asc" value="ASC" /> <label for="'. $this->id_prefix.'order_asc">'.__('Ascending', SummarizePosts::txtdomain).'</label><br/>
    		<input name="'.$this->name_prefix . 'order" id="'. $this->id_prefix.'order_desc" value="DESC" /> <label for="'. $this->id_prefix.'order_desc">'.__('Descending', SummarizePosts::txtdomain).'</label>';
    }

	//------------------------------------------------------------------------------
	/**
	 * Enable pagination?
	 */
    private function _paginate() {
    	return '
    	<input name="'.$this->name_prefix . 'paginate" id="'. $this->id_prefix.'paginate" value="1" /> <label class="sp_checkbox_label">'.__('Paginate Results?', SummarizePosts::txtdomain).'</label>';
    }       


	//------------------------------------------------------------------------------
	/**
	 * post_date
	 */
    private function _post_date() {
    	$output = '
	    	<script>
				jQuery(function() {
					jQuery("#'.$this->id_prefix.'post_date").datepicker({
						dateFormat : "yy-mm-dd"
					});
				});
			</script>
			<label for="'.$this->id_prefix.'post_date">'.__('Post Date',SummarizePosts::txtdomain).'</label>
			<input type="text" name="'.$this->name_prefix.'post_date" class="" id="'.$this->id_prefix.'post_date"  value="" />';
    }

	//------------------------------------------------------------------------------
	/**
	 * post_mime_type
	 */
    private function _post_mime_type() {
    	return sprintf('<label for="%s" class="sp_text_label">%s</label> <input type="text" name="%s" id="%s" value="" />'
	    	, $this->id_prefix . 'post_mime_type'
	    	, __('Post MIME Type', SummarizePosts::txtdomain)
    		, $this->name_prefix . 'post_mime_type'
    		, $this->id_prefix . 'post_mime_type');
    }
    
	//------------------------------------------------------------------------------
	/**
	 * post_modified
	 */
    private function _post_modified() {
    	$output = '
	    	<script>
				jQuery(function() {
					jQuery("#'.$this->id_prefix.'post_modified").datepicker({
						dateFormat : "yy-mm-dd"
					});
				});
			</script>
			<label for="'.$this->id_prefix.'post_modified">'.__('Post Modified',SummarizePosts::txtdomain).'</label>
			<input type="text" name="'.$this->name_prefix.'post_modified" class="" id="'.$this->id_prefix.'post_modified"  value="" />';
    }

	//------------------------------------------------------------------------------
	/**
	 * post_parent
	 */
    private function _post_parent() {
    	return sprintf('<label for="%s" class="sp_text_label">%s</label> <input type="text" name="%s" id="%s" value="" />'
	    	, $this->id_prefix . 'post_parent'
	    	, __('Post Parent(s)', SummarizePosts::txtdomain)
    		, $this->name_prefix . 'post_parent'
    		, $this->id_prefix . 'post_parent');
    }

	//------------------------------------------------------------------------------
	/**
	 * post_status
	 */
    private function _post_status() {
    	$output = '<span class="sp_radio_label">'.__('Post Status', SummarizePosts::txtdomain).'</span><br/>';
    
    	$post_statuses = array('draft','inherit','publish','auto-draft');
    	
    	foreach ($post_statuses as $ps) {
    		$output .= sprintf('<input type="checkbox" name="%s[]" id="%s" value="%s"> <label for="%s>%id</label><br/>'
    			, $this->name_prefix . 'post_status'
    			, $this->id_prefix . 'post_status' . $ps
    			, $ps
    			, $this->id_prefix . 'post_status' . $ps
    			, $ps
    		);
    	}
		return $output;
    }

	//------------------------------------------------------------------------------
	/**
	 * post_title
	 */
    private function _post_title() {
    	return sprintf('<label for="%s" class="sp_text_label">%s</label> <input type="text" name="%s" id="%s" value="" />'
	    	, $this->id_prefix . 'post_title'
	    	, __('Post Title', SummarizePosts::txtdomain)
    		, $this->name_prefix . 'post_title'
    		, $this->id_prefix . 'post_title');
    }
    
	//------------------------------------------------------------------------------
	/**
	 * Lets the user select a valid post_type
	 */
    private function _post_type() {
    	$output = '<span class="sp_radio_label">'.__('Post Types', SummarizePosts::txtdomain).'</span><br/>';
    
    	$post_types = get_post_types();
    	foreach ($post_types as $k => $pt) {
    		$output .= sprintf('<input type="checkbox" name="%s[]" id="%s" value="%s"> <label for="%s>%id</label><br/>'
    			, $this->name_prefix . 'post_type'
    			, $this->id_prefix . 'post_type_' . $pt
    			, $pt
    			, $this->id_prefix . 'post_type_' . $pt
    			, $pt
    		);
    	}
		return $output;
    }

    //------------------------------------------------------------------------------
	/**
	 * Which columns to search
	 */
    private function _search_columns() {
    	return sprintf('<label for="%s" class="sp_text_label">%s</label> <input type="text" name="%s" id="%s" value="" />'
	    	, $this->id_prefix . 'search_columns'
	    	, __('Columns to search', SummarizePosts::txtdomain)
    		, $this->name_prefix . 'search_columns'
    		, $this->id_prefix . 'search_columns');
    }

	//------------------------------------------------------------------------------
	/**
	 * Generates simple search term box.
	 */
    private function _search_term() {
    	return sprintf('<label for="%s" class="sp_text_label">%s</label><input type="text" name="%s" id="%s" value="" />'
	    	, $this->id_prefix . 'search_term'
	    	, __('Search Term', SummarizePosts::txtdomain)
    		, $this->name_prefix . 'search_term'
    		, $this->id_prefix . 'search_term');
    }

	//------------------------------------------------------------------------------
	/**
	 * taxonomy
	 */
    private function _taxonomy() {
    	$output = '<label class="sp_dropdown_label" for="'.$this->id_prefix .'taxonomy">'.__('Taxonomy', SummarizePosts::txtdomain).'</label><br/>
    		<select name="'.$this->name_prefix .'taxonomy" id="'.$this->id_prefix .'taxonomy">';
    		
    	$taxonomies = get_taxonomies();
		foreach ($taxonomies as $t) {
			$output .= sprintf('<option value="%s">%s</option>', $t, $t);
		}
		$output .= '</select>';
		
		return $output;
    }

	//------------------------------------------------------------------------------
	/**
	 * How deep to search the taxonomy
	 */
    private function _taxonomy_depth() {
    	return sprintf('<label for="%s" class="sp_text_label">%s</label><input type="text" name="%s" id="%s" value="" />'
	    	, $this->id_prefix . 'taxonomy_depth'
	    	, __('Taxonomy Depth', SummarizePosts::txtdomain)
    		, $this->name_prefix . 'taxonomy_depth'
    		, $this->id_prefix . 'taxonomy_depth');
    }

	//------------------------------------------------------------------------------
	/**
	 * taxonomy_slug
	 */
    private function _taxonomy_slug() {
    	return sprintf('<label for="%s" class="sp_text_label">%s</label><input type="text" name="%s" id="%s" value="" />'
	    	, $this->id_prefix . 'taxonomy_slug'
	    	, __('Taxonomy Slug', SummarizePosts::txtdomain)
    		, $this->name_prefix . 'taxonomy_slug'
    		, $this->id_prefix . 'taxonomy_slug');
    }

	//------------------------------------------------------------------------------
	/**
	 * taxonomy_term
	 */
    private function _taxonomy_term() {
    	return sprintf('<label for="%s" class="sp_text_label">%s</label><input type="text" name="%s" id="%s" value="" />'
	    	, $this->id_prefix . 'taxonomy_term'
	    	, __('Taxonomy Term', SummarizePosts::txtdomain)
    		, $this->name_prefix . 'taxonomy_term'
    		, $this->id_prefix . 'taxonomy_term');
    }


	//------------------------------------------------------------------------------
	/**
	 * yearmonth: uses the date-column
	 */
    private function _yearmonth() {
    	$output = '<label class="sp_dropdown_label" for="'.$this->id_prefix .'author">'.__('Author', SummarizePosts::txtdomain).'</label><br/>
    		<select size="5" name="'.$this->name_prefix .'author" id="'.$this->id_prefix .'author">';
    	
    	global $wpdb;
 		// if date_column is part of wp_posts: //!TODO
		$yearmonths = $wpdb->get_results("SELECT DISTINCT DATE_FORMAT(post_date,'%Y%m') as 'yearmonth'
			, DATE_FORMAT(post_date,'%M') as 'month'
			, YEAR(post_date) as 'year'
			FROM wp_posts
			ORDER BY yearmonth");
		foreach ($yearmonths as $ym) {
			$output .= sprintf('<option value="%s">%s %s</option>', $ym->yearmonth, $ym->year, $ym->month);
		}
		$output .= '</select>';
		
		return $output;
    }
    
    
    //------------------------------------------------------------------------------
	//! Public Functions
	//------------------------------------------------------------------------------
	/**
	 * Generate a form.  This is the main event.
	 */
	public function generate($tpl=null, $args=array()) {
		if (empty($tpl)) {
			$tpl = self::$form_tpl;
		}
		if (!empty($args)) {
			// override
		}
		
		$output = '';
		$this->placeholders['content'] = '';
		foreach ($this->props as $p) {
			$function_name = '_'.$p;
			$this->placeholders[$p] = $this->$function_name();
			// Keep the main 'content' bit populated.
			$this->placeholders['content'] .= $this->placeholders[$p];
		}
		
		// Get help
		$all_placeholders = array_keys($this->placeholders);
		foreach($all_placeholders as &$ph){
			$ph = "&#91;+$ph+&#93;";
		}
		$this->placeholders['nonce'] = $this->get_nonce_field();
		$this->placeholders['help'] = implode(', ', $all_placeholders);
		
		return $this->parse($tpl, $this->placeholders);
	}
	
	//------------------------------------------------------------------------------
	/**
	 * Retrieves a nonce field (set @ __construct or overriden via set_nonce)
	 */
	public function get_nonce_field(){
		return $this->nonce_field;
	}
	
	//------------------------------------------------------------------------------
	/**
	 * @param	string	prefix used in the field id's.
	 */
	public function set_id_prefix($prefix) {
		if (is_scalar($prefix)) {
			$this->id_prefix = $prefix;
		}	
	}
	
	//------------------------------------------------------------------------------
	/**
	 * @param	string	prefix used in the $_POST array
	 */
	public function set_name_prefix($prefix) {
		if (is_scalar($prefix)) {
			$this->name_prefix = $prefix;
		}	
	}

	//------------------------------------------------------------------------------
	/**
	 * This allows for a dumb field override, but you could also pass it your own
	 * values, e.g. 
	 * $str = wp_nonce_field('my_action', 'my_nonce_name', true, false);
	 */
	public function set_nonce_field($str){
		if (is_scalar($str)){
			$this->nonce_field = $str;
		}
	}
	
	//------------------------------------------------------------------------------
	/**
	 *
	 * SYNOPSIS: a simple parsing function for basic templating.
	 *
	 * @param	string	$tpl: a string containing [+placeholders+]
	 * @param	array	$hash: an associative array('key' => 'value');
	 * @param	boolean	if true, will not remove unused [+placeholders+]
	 *
	 * @return string	placeholders corresponding to the keys of the hash will be replaced
	 *	with the values and the string will be returned.
	 */	
	public static function parse($tpl, $hash, $preserve_unused_placeholders=false) 
	{
	
	    foreach ($hash as $key => $value) 
	    {
	    	if ( !is_array($value) )
	    	{
	        	$tpl = str_replace('[+'.$key.'+]', $value, $tpl);
	        }
	    }
	    
	    // Remove any unparsed [+placeholders+]
	    if (!$preserve_unused_placeholders) {
	    	$tpl = preg_replace('/\[\+(.*?)\+\]/', '', $tpl);
	    }
	    return $tpl;
	}	
}
/*EOF*/
