<?php
/**
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
 *
 * @package GetPostsForm
 */


class GetPostsForm {

	/**
	 * The super simple default search form includes only a search term.
	 */
	public static $defaults = array(
		'search_term'
	);

	public static $small = array();
	public static $medium = array();
	public static $large = array();

	// Used for text inputs
	public $text_tpl = '
		<div id="[+id+]_wrapper" class="[+wrapper_class+]">
			<label for="[+id_prefix+][+id+]" class="[+label_class+]" id="[+id+]_label">[+label+]</label>
			<span class="[+description_class+]" id="[+id+]_description">[+description+]</span>
			<input class="[+input_class+] input_field" type="text" name="[+name_prefix+][+id+]" id="[+id_prefix+][+id+]" value="[+value+]" />
			[+javascript_options+]
		</div>
		';

	// Used for checkbox inputs: wraps one or more $checkbox_tpl's
	public $checkbox_wrapper_tpl = '
		<div id="[+id+]_wrapper" class="[+wrapper_class+]">
			<span class="[+label_class+]" id="[+id+]_label">[+label+]</span>
			<span class="[+description_class+]" id="[+id+]_description">[+description+]</span>
			[+checkboxes+]
		</div>
		';

	public $checkbox_tpl = '
		<input type="checkbox" class="[+input_class+]" name="[+name_prefix+][+name+]" id="[+id_prefix+][+id+]" value="[+value+]" [+is_checked+]/> <label for="[+id_prefix+][+id+]" class="[+label_class+]" id="[+id+]_label">[+label+]</label>';

	// Used for radio input
	public $radio_tpl = '
		<input class="[+input_class+]" type="radio" name="[+name_prefix+][+name+]" id="[+id_prefix+][+id+]" value="[+value+]" /> <label class="[+label_class+]" id="[+id+]_label" for="[+id_prefix+][+id+]">[+label+]</label>';

	// dropdowns and multiselects
	public $select_wrapper_tpl = '
		<div id="[+id+]_wrapper" class="[+wrapper_class+]">
			<label for="[+id_prefix+][+id+]" class="[+label_class+]" id="[+id+]_label">[+label+]</label>
			<span class="[+description_class+]" id="[+id+]_description">[+description+]</span>
			<select size="[+size+]" name="[+name_prefix+][+name+]" class="[+input_class+]" id="[+id_prefix+][+id+]">
				[+options+]
			</select>
		</div>
		';

	// Options
	public $option_tpl = '<option value="[+value+]" [+is_selected+]>[+label+]</option>
	';

	/**
	 * Full form: contains all search elements. Some attributes only are useful
	 * when used programmatically.
	 */
	public static $full = array('limit', 'offset', 'orderby', 'order', 'include',
		'exclude', 'append', 'meta_key', 'meta_value', 'post_type', 'omit_post_type',
		'post_mime_type', 'post_parent', 'post_status', 'post_title', 'author', 'post_date',
		'post_modified', 'yearmonth', 'date_min', 'date_max', 'date_format', 'taxonomy',
		'taxonomy_term', 'taxonomy_slug', 'taxonomy_depth', 'search_term', 'search_columns',
		'join_rule', 'match_rule', 'date_column', 'paginate');


	// Set @ __construct so we can localize the "Search" button.
	public $form_tpl = '
		<style>
		[+css+]
		</style>
		<form method="[+method+]" action="[+action+]" class="[+form_name+]" id="[+form_name+][+form_number+]">
			[+nonce+]
			[+content+]
			<input type="submit" value="[+search+]" />
		</form>';


	/**
	 * Stores any errors encountered for debugging purposes.
	 */
	public $errors = array();

	public $name_prefix = 'gpf_';
	// As above, but for the id attribute.
	public $id_prefix = 'gpf_';

	public $nonce_field; // set @ __construct. Contains the whole field to be used.
	public $nonce_action = 'sp_search';
	public $nonce_name = 'sp_search';



	/**
	 * Contains the localized message displayed if no results are found. Set @ instantiation.
	 */
	public $no_results_msg;



	/**
	 * Ultimately passed to the parse function, this contains an associative
	 * array. The key is the name of the placeholder, the value is what it will
	 * get replaced with.
	 */
	public $placeholders = array(
		'name_prefix'  		=> 'gpf_',
		'id_prefix'   		=> 'gpf_',
		'wrapper_class'  	=> 'input_wrapper',
		'input_class'  		=> 'input_field',
		'label_class'		=> 'input_title',
		'description_class' => 'input_description',
		'form_name'   		=> 'getpostsform',
		'form_number'  		=> '', // iterated on each instance of generate, even across objects
		'action'   			=> '',
		'method'   			=> 'post',
	);

	// Contains css stuff, populated at instantiation
	public $css;

	// Describes how we're going to search
	public $search_by = array();

	/**
	 * Any valid key from GetPostsQuery (populated @ instantiation)
	 */
	private $valid_props = array();

	//------------------------------------------------------------------------------
	//! Magic Functions
	//------------------------------------------------------------------------------
	/**
	 * The inputs describe how you want to search: each element provided will trigger
	 * the generation of various form elements.
	 *
	 * @param array   $search_by (optional)
	 */
	public function __construct($search_by=array()) {
	
		// Default CSS stuff
		$dir = dirname(dirname(__FILE__));
		$this->set_css( $dir.'/css/searchform.css');
		
		$this->no_results_msg = '<p>'. __('Sorry, no results matched your search criteria.', SummarizePosts::txtdomain) . '</p>';
		// some localization
		$this->placeholders['search'] = __('Search', SummarizePosts::txtdomain);
		$this->placeholders['label_class'] = 'input_title';
		$this->placeholders['wrapper_class'] = 'input_wrapper';
		$this->placeholders['description_class'] = 'input_description';
		$this->placeholders['input_class'] = 'input_field';
		
		
		$this->valid_props = array_keys(GetPostsQuery::$defaults);
		if (empty($search_by)) {
			// push this through validation.
			//foreach(self::$defaults as $k => $v) {
			// $this->__set($k, $v);
			//}
			$this->search_by = self::$defaults;
		}
		else {
			$this->search_by = $search_by;
		}

		$this->nonce_field = wp_nonce_field($this->nonce_action, $this->nonce_name, true, false);
		
	}


	//------------------------------------------------------------------------------
	/**
	 * Interface with $this->search_by
	 *
	 * @param unknown $k
	 * @return unknown
	 */
	public function __get($k) {
		if ( in_array($k, $this->search_by) ) {
			return $this->search_by[$k];
		}
		else {
			return __('Invalid parameter:') . $k;
		}
	}


	//------------------------------------------------------------------------------
	/**
	 *
	 *
	 * @param string  $k for key
	 * @return boolean
	 */
	public function __isset($k) {
		return isset($this->search_by[$k]);
	}


	//------------------------------------------------------------------------------
	/**
	 * Interface with $this->search_by
	 *
	 * @param unknown $k
	 */
	public function __unset($k) {
		unset($this->search_by[$k]);
	}


	//------------------------------------------------------------------------------
	/**
	 * Validate and set parameters
	 * Interface with $this->search_by
	 *
	 * @param string  $k for key
	 * @param mixed   $v for value
	 */
	public function __set($k, $v) {
		if (in_array($k, $this->valid_props)) {
			$this->search_by[$k] = $v;
		}
		else {

		}
	}


	//------------------------------------------------------------------------------
	//! Private Functions (named after GetPostsQuery args)
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
	/**
	 * List which posts to append to search results.
	 *
	 * @return string
	 */
	private function _append() {
		$ph = $this->placeholders;
		$ph['value'] = '';
		$ph['name'] = 'append';
		$ph['id']  = 'append';
		$ph['label'] = __('Append', SummarizePosts::txtdomain);
		$ph['description'] = __('List posts by their ID that you wish to include on every search. Comma-separate multiple values.', SummarizePosts::txtdomain);
		return self::parse($this->text_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * Post author (display name)
	 *
	 * @return string
	 */
	private function _author() {
		$ph = $this->placeholders;
		
		global $wpdb;
		$authors = $wpdb->get_results("SELECT ID, display_name from $wpdb->users ORDER BY display_name");
		
		$ph['options'] = '';
		foreach ($authors as $a) {
			$ph['value'] = $a->display_name;
			$ph['label'] = $a->display_name .'('.$a->ID.')';
			$ph['options'] .=  self::parse($this->option_tpl, $ph);
		}

		$ph['value'] = '';
		$ph['name'] = 'author';
		$ph['id']  = 'author';
		$ph['label'] = __('Author', SummarizePosts::txtdomain);
		$ph['description'] = __('List posts by their ID that you wish to include on every search.', SummarizePosts::txtdomain);
		$ph['size'] = 5;

		return self::parse($this->select_wrapper_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * date_column: some js help, but the user can write in their own value for dates stored in custom fields (i.e. custom columns)
	 * post_date, post_date_gmt, post_modified, post_modified_gmt
	 *
	 * @return string
	 */
	private function _date_column() {
		$ph = $this->placeholders;
		
		$ph['value'] = '';
		$ph['name'] = 'date_column';
		$ph['id']  = 'date_column';
		$ph['label'] = __('Date Columns', SummarizePosts::txtdomain);
		$ph['description'] = __('Which column should be used for date comparisons? Select one, or write in a custom field.', SummarizePosts::txtdomain);
		$ph['javascript_options'] = '
			<div class="js_button_wrapper">
				<span class="js_button" onclick="jQuery(\'#'.$this->id_prefix.'date_column\').val(\'post_date\');">post_date</span><br/>
				<span class="js_button" onclick="jQuery(\'#'.$this->id_prefix.'date_column\').val(\'post_date_gmt\');">post_date_gmt</span><br/>
				<span class="js_button" onclick="jQuery(\'#'.$this->id_prefix.'date_column\').val(\'post_modified\');">post_modified</span><br/>
				<span class="js_button" onclick="jQuery(\'#'.$this->id_prefix.'date_column\').val(\'post_modified_gmt\');">post_modified_gmt</span><br/>
			</div>';

		return self::parse($this->text_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * Date format: some js help, but the user can write in their own value.
	 *
	 * @return string
	 */
	private function _date_format() {
		$ph = $this->placeholders;

		$ph['value'] = '';
		$ph['name'] = 'date_format';
		$ph['id']  = 'date_format';
		$ph['label'] = __('Date Format', SummarizePosts::txtdomain);
		$ph['description'] = __('How do you want the dates in the results formatted? Use one of the shortcuts, or supply a use any value valid for the <a href="http://php.net/manual/en/function.date-format.php">date_format()</a>', SummarizePosts::txtdomain);

		$ph['javascript_options'] = '
			<span class="button" onclick="jQuery(\'#'.$ph['id_prefix'].'date_format\').val(\'mm/dd/yy\');">mm/dd/yy</span>
			<span class="button" onclick="jQuery(\'#'.$ph['id_prefix'].'date_format\').val(\'yyyy-mm-dd\');">yyyy-mm-dd</span>
			<span class="button" onclick="jQuery(\'#'.$ph['id_prefix'].'date_format\').val(\'yy-mm-dd\');">yy-mm-dd</span>
			<span class="button" onclick="jQuery(\'#'.$ph['id_prefix'].'date_format\').val(\'d M, y\');">d M, y</span>
			<span class="button" onclick="jQuery(\'#'.$ph['id_prefix'].'date_format\').val(\'d MM, y\');">d MM, y</span>
			<span class="button" onclick="jQuery(\'#'.$ph['id_prefix'].'date_format\').val(\'DD, d MM, yy\');">DD, d MM, yy</span>';

		return self::parse($this->text_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * date_max
	 *
	 * @return string
	 */
	private function _date_max() {
		$ph = $this->placeholders;
		
		$ph['value'] = '';
		$ph['name'] = 'date_max';
		$ph['id']  = 'date_max';
		$ph['label'] = __('Date Maximum', SummarizePosts::txtdomain);
		$ph['description'] = __('Only results from this date or before will be returned', SummarizePosts::txtdomain);

		$ph['javascript_options'] = '
	    	<script>
				jQuery(function() {
					jQuery("#'.$ph['id_prefix'].'date_max").datepicker({
						dateFormat : "yy-mm-dd"
					});
				});
			</script>';

		return self::parse($this->text_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * date_min
	 *
	 * @return string
	 */
	private function _date_min() {
		$ph = $this->placeholders;
		
		$ph['value'] = '';
		$ph['name'] = 'date_min';
		$ph['id']  = 'date_min';
		$ph['label'] = __('Date Minimum', SummarizePosts::txtdomain);
		$ph['description'] = __('Only results from this date or after will be returned.', SummarizePosts::txtdomain);

		$ph['javascript_options'] = '
	    	<script>
				jQuery(function() {
					jQuery("#'.$ph['id_prefix'].'date_min").datepicker({
						dateFormat : "yy-mm-dd"
					});
				});
			</script>';

		return self::parse($this->text_tpl, $ph);

	}


	//------------------------------------------------------------------------------
	/**
	 * Lists which posts to exclude
	 *
	 * @return string
	 */
	private function _exclude() {
		$ph = $this->placeholders;
		$ph['value'] = '';
		$ph['name'] = 'exclude';
		$ph['id']  = 'exclude';
		$ph['label'] = __('Exclude', SummarizePosts::txtdomain);
		$ph['description'] = __('List posts by their ID that you wish to exclude from search results. Comma-separate multiple values.', SummarizePosts::txtdomain);

		return self::parse($this->text_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * List which posts to include
	 *
	 * @return string
	 */
	private function _include() {
		$ph = $this->placeholders;
		$ph['value'] = '';
		$ph['name'] = 'include';
		$ph['id']  = 'include';
		$ph['label'] = __('Include', SummarizePosts::txtdomain);
		$ph['description'] = __('List posts by their ID that you wish to return.  Usually this option is not used with any other search options. Comma-separate multiple values.', SummarizePosts::txtdomain);

		return self::parse($this->text_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * Limits the number of posts returned OR sets the number of posts per page
	 * if pagination is on.
	 *
	 * @return string
	 */
	private function _limit() {
		$ph = $this->placeholders;
		
		$ph['value'] = '';
		$ph['name'] = 'limit';
		$ph['id']  = 'limit';
		$ph['label'] = __('Limit', SummarizePosts::txtdomain);
		$ph['description'] = __('Limit the number of results returned. If pagination is enabled, this number will be the number of results shown per page.', SummarizePosts::txtdomain);

		return self::parse($this->text_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * match_rule
	 *
	 * @return string
	 */
	private function _match_rule() {
		$ph = $this->placeholders;

		$ph['value'] = '';
		$ph['name'] = 'match_rule';
		$ph['id']  = 'match_rule';
		$ph['label'] = __('Match Rule', SummarizePosts::txtdomain);
		$ph['description'] = __('Define how your search term should match.', SummarizePosts::txtdomain);
		$ph['size'] = 1;

		$match_rules = array(
			'contains'   => __('Contains', SummarizePosts::txtdomain),
			'starts_with'  => __('Starts with', SummarizePosts::txtdomain),
			'ends_with'  => __('Ends with', SummarizePosts::txtdomain),
		);
		$ph['options'] = '';
		foreach ($match_rules as $value => $label) {
			$ph['value'] = $value;
			$ph['label'] = $label;
			$ph['options'] .=  self::parse($this->option_tpl, $ph);
		}

		return self::parse($this->select_wrapper_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * Meta key is the name of a custom field from wp_postmeta: should be used with meta_value
	 *
	 * @return string
	 */
	private function _meta_key() {
		$ph = $this->placeholders;
		$ph['value'] = '';
		$ph['name'] = 'meta_key';
		$ph['id']  = 'meta_key';
		$ph['label'] = __('Meta Key', SummarizePosts::txtdomain);
		$ph['description'] = __('Name of a custom field, to be used in conjuncture with <em>meta_value</em>.', SummarizePosts::txtdomain);

		return self::parse($this->text_tpl, $ph);			
	}


	//------------------------------------------------------------------------------
	/**
	 * Meta key is the name of a custom field from wp_postmeta: should be used with meta_value
	 *
	 * @return unknown
	 */
	private function _meta_value() {
		$ph = $this->placeholders;
		$ph['value'] = '';
		$ph['name'] = 'meta_value';
		$ph['id']  = 'meta_value';
		$ph['label'] = __('Meta Value', SummarizePosts::txtdomain);
		$ph['description'] = __('Value of a custom field, to be used in conjuncture with <em>meta_key</em>.', SummarizePosts::txtdomain);

		return self::parse($this->text_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * Offset
	 *
	 * @return string
	 */
	private function _offset() {
		$ph = $this->placeholders;
		$ph['value'] = '';
		$ph['name'] = 'offset';
		$ph['id']  = 'offset';
		$ph['label'] = __('Offset', SummarizePosts::txtdomain);
		$ph['description'] = __('Number of results to skip.  Usually this is used only programmatically when pagination is enabled.', SummarizePosts::txtdomain);

		return self::parse($this->text_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * Lets the user select a valid post_type
	 *
	 * @return string
	 */
	private function _omit_post_type() {
		$ph = $this->placeholders;

		$ph['label'] = __('Omit Post Types', SummarizePosts::txtdomain);
		$ph['id']  = 'omit_post_type';
		$ph['value'] = '';
		$ph['name'] = 'omit_post_type[]';
		$ph['description'] = __('Check which post-types you wish to omit from search results.', SummarizePosts::txtdomain);

		$i = 0;
		$ph['checkboxes'] = ''; 
		$post_types = get_post_types();
		foreach ($post_types as $k => $pt) {
			$ph2 = $this->placeholders;
			$ph2['value'] = $k;
			$ph2['label'] = $pt;
			$ph2['input_class'] = 'input_checkbox';
			$ph2['label_class'] = 'label_checkbox';
			$ph2['id'] = 'omit_post_type' . $i;
			$ph['checkboxes'] .= self::parse($this->checkbox_tpl, $ph2);
			$i++;
		}
		
		return self::parse($this->checkbox_wrapper_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * Order of results: ascending, descending
	 *
	 * @return unknown
	 */
	private function _order() {
		$ph = $this->placeholders;
		$ph['value'] = '';
		$ph['name'] = 'order';
		$ph['id']  = 'order';
		$ph['label'] = __('Order', SummarizePosts::txtdomain);
		$ph['description'] = __('What order should search results be returned in? See also the <em>orderby</em> parameter.', SummarizePosts::txtdomain);
		$ph['checkboxes'] = '';
		
		$ph2 = $this->placeholders;
		$ph2['value'] = 'ASC';
		$ph2['label'] = __('Ascending', SummarizePosts::txtdomain);
		$ph2['id'] = 'order_asc';
		$ph2['name'] = 'order';
		$ph2['input_class'] = 'input_radio';
		$ph2['label_class'] = 'label_radio';
		$ph['checkboxes'] .= self::parse($this->radio_tpl, $ph2);


		$ph2['value'] = 'DESC';
		$ph2['label'] = __('Descending', SummarizePosts::txtdomain);
		$ph2['id'] = 'order_desc';
		
		$ph['checkboxes'] .= self::parse($this->radio_tpl, $ph2);
		
		
		return self::parse($this->checkbox_wrapper_tpl, $ph);

	}

	//------------------------------------------------------------------------------
	/**
	 * 
	 */
	private function _orderby() {
		$ph = $this->placeholders;
		$ph['value'] = '';
		$ph['name'] = 'orderby';
		$ph['id']  = 'orderby';
		$ph['label'] = __('Order By', SummarizePosts::txtdomain);
		$ph['description'] = __('Which column should results be sorted by. Default: ID', SummarizePosts::txtdomain);
		return self::parse($this->text_tpl, $ph);	
	}
	
	//------------------------------------------------------------------------------
	/**
	 * Enable pagination?
	 *
	 * @return unknown
	 */
	private function _paginate() {
		$ph = $this->placeholders;
		
		$ph['value'] = '';
		$ph['name'] = 'paginate';
		$ph['id']  = 'paginate';
		$ph['label'] = __('Paginate Results', SummarizePosts::txtdomain);
		$ph['description'] = ''; // __('.', SummarizePosts::txtdomain);

		$ph['checkboxes'] = self::parse($this->checkbox_tpl, $ph);
		return self::parse($this->checkbox_wrapper_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * post_date
	 */
	private function _post_date() {
		$ph = $this->placeholders;
		$ph['value'] = '';
		$ph['name'] = 'post_date';
		$ph['id']  = 'post_date';
		$ph['label'] = __('Post Date', SummarizePosts::txtdomain);
		$ph['description'] = __('Find posts from this date.  Use the <em>date_column</em> parameter to determine which column should be considered.', SummarizePosts::txtdomain);

		$ph['javascript_options'] = '
	    	<script>
				jQuery(function() {
					jQuery("#'.$ph['id_prefix'].'post_date").datepicker({
						dateFormat : "yy-mm-dd"
					});
				});
			</script>';

		return self::parse($this->text_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * post_mime_type
	 *
	 * @return unknown
	 */
	private function _post_mime_type() {
		$ph = $this->placeholders;
		$ph['value'] = '';
		$ph['name'] = 'post_mime_type';
		$ph['id']  = 'post_mime_type';
		$ph['label'] = __('Post MIME Type', SummarizePosts::txtdomain);
		$ph['description'] = __('This is useful for searching media posts.', SummarizePosts::txtdomain);
		return self::parse($this->text_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * post_modified
	 *
	 * @return	string
	 */
	private function _post_modified() {
		$ph = $this->placeholders;
		$ph['value'] = '';
		$ph['name'] = 'post_modified';
		$ph['id']  = 'post_modified';
		$ph['label'] = __('Post Modified', SummarizePosts::txtdomain);
		$ph['description'] = __('Find posts modified on this date.', SummarizePosts::txtdomain);

		$ph['javascript_options'] = '
	    	<script>
				jQuery(function() {
					jQuery("#'.$ph['id_prefix'].'post_modified").datepicker({
						dateFormat : "yy-mm-dd"
					});
				});
			</script>';

		return self::parse($this->text_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * post_parent
	 *
	 * @return string
	 */
	private function _post_parent() {
		$ph = $this->placeholders;
		$ph['value'] = '';
		$ph['name'] = 'post_parent';
		$ph['id']  = 'post_title';
		$ph['label'] = __('Post Parent', SummarizePosts::txtdomain);
		$ph['description'] = __('Retrieve all posts that are children of the post ID specified. Comma-separate multiple values.', SummarizePosts::txtdomain);

		return self::parse($this->text_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * post_status
	 *
	 * @return string
	 */
	private function _post_status() {
		$ph = $this->placeholders;
		$ph['value'] = '';
		$ph['name'] = 'post_status';
		$ph['id']  = 'post_status';
		$ph['label'] = __('Post Status', SummarizePosts::txtdomain);
		$ph['description'] = __('Most searches will be for published posts.', SummarizePosts::txtdomain);

		$i = 0;
		$ph['checkboxes'] = ''; 
		$post_statuses = array('draft', 'inherit', 'publish', 'auto-draft');

		foreach ($post_statuses as $ps) {
			$ph2 = $this->placeholders;
			$ph2['value'] = $ps;
			$ph2['label'] = $ps;
			$ph2['input_class'] = 'input_checkbox';
			$ph2['label_class'] = 'label_checkbox';
			$ph2['id'] = 'post_status' . $i;
			$ph['checkboxes'] .= self::parse($this->checkbox_tpl, $ph2);
			$i++;
		}
		
		return self::parse($this->checkbox_wrapper_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * post_title
	 *
	 * @return string
	 */
	private function _post_title() {
		$ph = $this->placeholders;
		$ph['value'] = '';
		$ph['name'] = 'post_title';
		$ph['id']  = 'post_title';
		$ph['label'] = __('Post Title', SummarizePosts::txtdomain);
		$ph['description'] = __('Retrieve posts with this exact title.', SummarizePosts::txtdomain);

		return self::parse($this->text_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * Lets the user select a valid post_type
	 *
	 * @return string
	 */
	private function _post_type() {
		$ph = $this->placeholders;

		$ph['label'] = __('Post Types', SummarizePosts::txtdomain);
		$ph['id']  = 'post_type';
		$ph['value'] = '';
		$ph['name'] = 'post_type[]';
		$ph['description'] = __('Check which post-types you wish to search.', SummarizePosts::txtdomain);

		$i = 0;
		$ph['checkboxes'] = ''; 
		$post_types = get_post_types();
		foreach ($post_types as $k => $pt) {
			$ph2 = $this->placeholders;
			$ph2['value'] = $k;
			$ph2['label'] = $pt;
			$ph2['input_class'] = 'input_checkbox';
			$ph2['label_class'] = 'label_checkbox';
			$ph2['id'] = 'post_type' . $i;
			$ph['checkboxes'] .= self::parse($this->checkbox_tpl, $ph2);
			$i++;
		}
		
		return self::parse($this->checkbox_wrapper_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * Which columns to search
	 *
	 * @return string
	 */
	private function _search_columns() {
		$ph = $this->placeholders;
		$ph['value'] = '';
		$ph['name'] = 'search_columns';
		$ph['id']  = 'search_columns';
		$ph['label'] = __('Search Columns', SummarizePosts::txtdomain);
		$ph['description'] = __('When searching by a <em>search_term</em>, which define columns should be searched. Comma-separate multiple values.', SummarizePosts::txtdomain);

		return self::parse($this->text_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * Generates simple search term box.
	 *
	 * @return unknown
	 */
	private function _search_term() {
		$ph = $this->placeholders;
		$ph['value'] = '';
		$ph['name'] = 'search_term';
		$ph['id']  = 'search_term';
		$ph['label'] = __('Search Term', SummarizePosts::txtdomain);
		$ph['description'] = __('Search posts for this term. Use the <em>search_columns</em> parameter to specify which columns are searched for the term.', SummarizePosts::txtdomain);

		return self::parse($this->text_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * taxonomy
	 *
	 * @return unknown
	 */
	private function _taxonomy() {
		$ph = $this->placeholders;
		
		$ph['options'] = '';
		$taxonomies = get_taxonomies();
		foreach ($taxonomies as $t) {
			$ph2 = $this->placeholders;
			$ph2['value'] = $t;
			$ph2['label'] = $t;
			$ph['options'] .=  self::parse($this->option_tpl, $ph2);
		}

		$ph['value'] = '';
		$ph['name'] = 'taxonomy';
		$ph['id']  = 'taxonomy';
		$ph['label'] = __('Author', SummarizePosts::txtdomain);
		$ph['description'] = __('Choose which taxonomy to search in. Used in conjunction with <em>taxonomy_term</em>.', SummarizePosts::txtdomain);
		$ph['size'] = 1;

		return self::parse($this->select_wrapper_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * How deep to search the taxonomy
	 *
	 * @return string
	 */
	private function _taxonomy_depth() {
		$ph = $this->placeholders;
		
		$ph['value'] = '';
		$ph['name'] = 'taxonomy_depth';
		$ph['id']  = 'taxonomy_depth';
		$ph['label'] = __('Taxonomy Depth', SummarizePosts::txtdomain);
		$ph['description'] = __('When doing a hierarchical taxonomical search (e.g. by sub-categories), increase this number to reflect how many levels down the hierarchical tree should be searched. For example, 1 = return posts classified with the given taxonomical term (e.g. mammals), 2 = return posts classified with the given term or with the sub-taxonomies (e.g. mammals or dogs). (default: 1).', SummarizePosts::txtdomain);

		return self::parse($this->text_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * taxonomy_slug
	 *
	 * @return string
	 */
	private function _taxonomy_slug() {
		$ph = $this->placeholders;
		
		$ph['value'] = '';
		$ph['name'] = 'taxonomy_slug';
		$ph['id']  = 'taxonomy_slug';
		$ph['label'] = __('Taxonomy Slug', SummarizePosts::txtdomain);
		$ph['description'] = __('The taxonomy slug is the URL-friendly taxonomy term.', SummarizePosts::txtdomain);

		return self::parse($this->text_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	/**
	 * taxonomy_term
	 *
	 * @return unknown
	 */
	private function _taxonomy_term() {
		$ph = $this->placeholders;
		
		$ph['value'] = '';
		$ph['name'] = 'taxonomy_term';
		$ph['id']  = 'taxonomy_term';
		$ph['label'] = __('Taxonomy Term', SummarizePosts::txtdomain);
		$ph['description'] = __('', SummarizePosts::txtdomain);

		return self::parse($this->text_tpl, $ph);

	}


	//------------------------------------------------------------------------------
	/**
	 * yearmonth: uses the date-column
	 *
	 * @return string
	 */
	private function _yearmonth() {
	
		$ph = $this->placeholders;
		$ph['options'] = '';
		global $wpdb;
		// if date_column is part of wp_posts: //!TODO
		$yearmonths = $wpdb->get_results("SELECT DISTINCT DATE_FORMAT(post_date,'%Y%m') as 'yearmonth'
			, DATE_FORMAT(post_date,'%M') as 'month'
			, YEAR(post_date) as 'year'
			FROM wp_posts
			ORDER BY yearmonth");
		foreach ($yearmonths as $ym) {
			$ph2 = $this->placeholders;
			$ph2['value'] = $ym->yearmonth;
			$ph2['label'] = $ym->year . ' ' . $ym->month;
			$ph['options'] .=  self::parse($this->option_tpl, $ph2);
		}

		$ph['value'] = '';
		$ph['name'] = 'yearmonth';
		$ph['id']  = 'yearmonth';
		$ph['label'] = __('Month', SummarizePosts::txtdomain);
		$ph['description'] = __("Choose which month's posts you wish to view. This relies on the <em>date_column</em> parameter.", SummarizePosts::txtdomain);
		$ph['size'] = 5;

		return self::parse($this->select_wrapper_tpl, $ph);
	}


	//------------------------------------------------------------------------------
	//! Public Functions
	//------------------------------------------------------------------------------
	/**
	* Format any errors in an unordered list, or returns a message saying there were no errors.
	*/
	public function get_errors()
	{

		if (!empty($this->errors))
		{
			$output = '';
			$items = '';
			foreach ($this->errors as $id => $e)
			{
				$items .= '<li>'.$e.'</li>' ."\n";
			}
			$output = '<ul>'."\n".$items.'</ul>'."\n";
			return $output;
		}
		else
		{
			return __('There were no errors.');
		}			
	}

	//------------------------------------------------------------------------------
	/**
	 * Generate a form.  This is the main event.
	 *
	 * @param array   specify which parameters you want to search by
	 * @param array   Limit selectable options, e.g. you may want the user to search
	 *     only some (but not all) post_types.
	 * @param array   Hard limits. These ar invisible to the user on the generated form,
	 *     but if set, they ensure that the user cannot view data they aren't
	 *     supposed to see.
	 * @param string  string to format the output.
	 * @param unknown $search_by (optional)
	 * @param unknown $tpl       (optional)
	 * @return unknown
	 */
	public function generate($search_by=array(), $tpl=null) {

		static $instantiation_count = 0;
		$instantiation_count++;
		$this->placeholders['form_number'] = $instantiation_count;
		$this->placeholders['css'] = $this->get_css();
		
		if (empty($tpl)) {
			$tpl = $this->form_tpl;
		}
		if (!empty($search_by)) {
			// override
			$this->search_by = $search_by;
		}

		$output = '';
		$this->placeholders['content'] = '';
		foreach ($this->search_by as $p) {
			$function_name = '_'.$p;
			if (method_exists($this, $function_name)) {
				$this->placeholders[$p] = $this->$function_name();
				// Keep the main 'content' bit populated.
				$this->placeholders['content'] .= $this->placeholders[$p];
			}
			else {
				$this->errors['invalid_searchby_parameter'] = sprintf( __('Invalid search_by parameter:'), "<em>$p</em>");
			}
		}

		// Get help
		$all_placeholders = array_keys($this->placeholders);
		foreach ($all_placeholders as &$ph) {
			$ph = "&#91;+$ph+&#93;";
		}
		$this->placeholders['nonce'] = $this->get_nonce_field();
		$this->placeholders['help'] = implode(', ', $all_placeholders);

		return $this->parse($tpl, $this->placeholders);
	}


	//------------------------------------------------------------------------------
	/**
	 *
	 *
	 * @return string
	 */
	public function get_css() {
		return $this->css;
	}


	//------------------------------------------------------------------------------
	/**
	 * Retrieves the "No Results" message.
	 *
	 * @return unknown
	 */
	public function get_no_results_msg() {
		return $this->no_results_msg;
	}


	//------------------------------------------------------------------------------
	/**
	 * Retrieves a nonce field (set @ __construct or overriden via set_nonce)
	 *
	 * @return unknown
	 */
	public function get_nonce_field() {
		return $this->nonce_field;
	}


	//------------------------------------------------------------------------------
	/**
	 * Set CSS for the form.  Due to WP's way of printing everything instead of 
	 * returning it, we can't add stylesheets easily via a shortcode, so instead
	 * we slurp the CSS defintions (either from a file or string), and print them
	 * into a <style> tag above the form.  Janky-alert!
	 *
	 * @param string $css
	 * @param boolean $is_file (optional)
	 */
	public function set_css($css, $is_file=true) {
		if ($is_file) {
			if (file_exists($css)) {
				$this->css = file_get_contents($css);
			}
			else{
				$this->errors['css_file_not_found'] = sprintf(__('CSS file not found %s'), "<em>$css</em>");
			}
		}
		else {
			$this->css = $css;
		}
	}


	//------------------------------------------------------------------------------
	/**
	 *
	 *
	 * @param string  prefix used in the field id's.
	 * @param unknown $prefix
	 */
	public function set_id_prefix($prefix) {
		if (is_scalar($prefix)) {
			$this->id_prefix = $prefix;
		}
		else {
			$this->errors['set_id_prefix'] = sprintf( __('Invalid data type passed to %s function. Input must be a string.', SummarizePosts::txtdomain), __FUNCTION__);
		}
	}


	//------------------------------------------------------------------------------
	/**
	 *
	 *
	 * @param unknown $prefix
	 */
	public function set_name_prefix($prefix) {
		if (is_scalar($prefix)) {
			$this->name_prefix = $prefix;
		}
		else {
			$this->errors['set_id_prefix'] = sprintf( __('Invalid data type passed to %s function. Input must be a string.', SummarizePosts::txtdomain), __FUNCTION__);
		}
	}


	//------------------------------------------------------------------------------
	/**
	 * Sets the "No Results" message.
	 *
	 * @param string  New message
	 * @param unknown $msg
	 */
	public function set_no_results_msg($msg) {
		if (is_scalar($msg)) {
			$this->no_results_msg;
		}
		else {
			$this->errors['set_id_prefix'] = sprintf( __('Invalid data type passed to %s function. Input must be a string.', SummarizePosts::txtdomain), __FUNCTION__);
		}
	}


	//------------------------------------------------------------------------------
	/**
	 * This allows for a dumb field override, but you could also pass it your own
	 * values, e.g.
	 * $str = wp_nonce_field('my_action', 'my_nonce_name', true, false);
	 *
	 * @param unknown $str
	 */
	public function set_nonce_field($str) {
		if (is_scalar($str)) {
			$this->nonce_field = $str;
		}
		else {
			$this->errors['set_id_prefix'] = sprintf( __('Invalid data type passed to %s function. Input must be a string.', SummarizePosts::txtdomain), __FUNCTION__);
		}
	}


	//------------------------------------------------------------------------------
	/**
	 * SYNOPSIS: a simple parsing function for basic templating.
	 *
	 * @param boolean if true, will not remove unused [+placeholders+]
	 *
	 * with the values and the string will be returned.
	 * @param string  $tpl:                         a string containing [+placeholders+]
	 * @param array   $hash:                        an associative array('key' => 'value');
	 * @param unknown $preserve_unused_placeholders (optional)
	 * @return string placeholders corresponding to the keys of the hash will be replaced
	 */
	public static function parse($tpl, $hash, $preserve_unused_placeholders=false) {

		foreach ($hash as $key => $value) {
			if ( !is_array($value) ) {
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