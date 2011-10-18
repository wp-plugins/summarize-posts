<?php
/*------------------------------------------------------------------------------
Wraps the form elements
------------------------------------------------------------------------------*/
?>
<form id="getpostquery_search_form" method="post">

	<?php print $data['content']; ?>

	<?php wp_nonce_field( GetPostsQuery::action_name, GetPostsQuery::nonce_name); ?>

	<div class="custom_content_type_mgr_form_controls">
		<input type="submit" name="submit" class="button-primary" value="<?php print $data['submit']; ?>" />
	</div>

</form>
