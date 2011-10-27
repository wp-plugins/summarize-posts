// closure to avoid namespace collision
(function(){
	// creates the plugin
	tinymce.create('tinymce.plugins.summarize_posts', {
		// creates control instances based on the control's id.
		// our button's id is "summarize_posts_button"
		createControl : function(id, controlManager) {
			if (id == 'summarize_posts_button') {
				// creates the button
				var button = controlManager.createButton('summarize_posts_button', {
					title : 'summarize_posts Shortcode', // title of the button
					image: url + "../../../images/16x16.jpg"
					onclick : function() {
						// triggers the thickbox
						var width = jQuery(window).width(), H = jQuery(window).height(), W = ( 720 < width ) ? 720 : width;
						W = W - 80;
						H = H - 84;
						tb_show( 'My Gallery Shortcode', '#TB_inline?width=' + W + '&height=' + H + '&inlineId=summarize_posts-form' );
					}
				});
				return button;
			}
			return null;
		}
	});
	
	// registers the plugin. DON'T MISS THIS STEP!!!
	tinymce.PluginManager.add('summarize_posts', tinymce.plugins.summarize_posts);
	
	// executes this when the DOM is ready
	jQuery(function(){
		// creates a form to be displayed everytime the button is clicked
		// you should achieve this using AJAX instead of direct html code like this
		var form = jQuery('<div id="summarize_posts-form"><table id="summarize_posts-table" class="form-table">\
			<tr>\
				<th><label for="summarize_posts-columns">Columns</label></th>\
				<td><input type="text" id="summarize_posts-columns" name="columns" value="3" /><br />\
				<small>specify the number of columns.</small></td>\
			</tr>\
			<tr>\
				<th><label for="summarize_posts-id">Post ID</label></th>\
				<td><input type="text" name="id" id="summarize_posts-id" value="" /><br />\
				<small>specify the post ID. Leave blank if you want to use the current post.</small>\
			</tr>\
			<tr>\
				<th><label for="summarize_posts-size">Size</label></th>\
				<td><select name="size" id="summarize_posts-size">\
					<option value="thumbnail">Thumbnail</option>\
					<option value="medium">Medium</option>\
					<option value="large">Large</option>\
					<option value="full">Full</option>\
				</select><br />\
				<small>specify the image size to use for the thumbnail display.</small></td>\
			</tr>\
			<tr>\
				<th><label for="summarize_posts-orderby">Order By</label></th>\
				<td><input type="text" name="orderby" id="summarize_posts-orderby" value="menu_order ASC, ID ASC" /><br /><small>RAND (random) is also supported.</small></td>\
			</tr>\
			<tr>\
				<th><label for="summarize_posts-itemtag">Item Tag</label></th>\
				<td><input type="text" name="itemtag" id="summarize_posts-itemtag" value="dl" /><br />\
					<small>the name of the XHTML tag used to enclose each item in the gallery.</small></td>\
			</tr>\
			<tr>\
				<th><label for="summarize_posts-icontag">Icon Tag</label></th>\
				<td><input type="text" name="icontag" id="summarize_posts-icontag" value="dt" /><br />\
					<small>the name of the XHTML tag used to enclose each thumbnail icon in the gallery.</small></td>\
			</tr>\
			<tr>\
				<th><label for="summarize_posts-captiontag">Caption Tag</label></th>\
				<td><input type="text" name="captiontag" id="summarize_posts-captiontag" value="dd" /><br />\
					<small>the name of the XHTML tag used to enclose each caption.</small></td>\
			</tr>\
			<tr>\
				<th><label for="summarize_posts-link">Link</label></th>\
				<td><input type="text" name="link" id="summarize_posts-link" value="" /><br />\
					<small>you can set it to "file" so each image will link to the image file, otherwise leave blank.</small></td>\
			</tr>\
			<tr>\
				<th><label for="summarize_posts-include">Include Attachment IDs</label></th>\
				<td><input type="text" name="include" id="summarize_posts-include" value="" /><br />\
					<small>comma separated attachment IDs</small>\
				</td>\
			</tr>\
			<tr>\
				<th><label for="summarize_posts-exclude">Exclude Attachment IDs</label></th>\
				<td><input type="text" id="summarize_posts-exclude" name="exclude" value="" /><br />\
					<small>comma separated attachment IDs</small>\
				</td>\
			</tr>\
		</table>\
		<p class="submit">\
			<input type="button" id="summarize_posts-submit" class="button-primary" value="Insert Gallery" name="submit" />\
		</p>\
		</div>');
		
		var table = form.find('table');
		form.appendTo('body').hide();
		
		// handles the click event of the submit button
		form.find('#summarize_posts-submit').click(function(){
			// defines the options and their default values
			// again, this is not the most elegant way to do this
			// but well, this gets the job done nonetheless
			var options = { 
				'columns'    : '3',
				'id'         : '',
				'size'       : 'thumbnail',
				'orderby'    : 'menu_order ASC, ID ASC',
				'itemtag'    : 'dl',
				'icontag'    : 'dt',
				'captiontag' : 'dd',
				'link'       : '',
				'include'    : '',
				'exclude'    : '' 
				};
			var shortcode = '[gallery';
			
			for( var index in options) {
				var value = table.find('#summarize_posts-' + index).val();
				
				// attaches the attribute to the shortcode only if it's different from the default value
				if ( value !== options[index] )
					shortcode += ' ' + index + '="' + value + '"';
			}
			
			shortcode += ']';
			
			// inserts the shortcode into the active editor
			tinyMCE.activeEditor.execCommand('mceInsertContent', 0, shortcode);
			
			// closes Thickbox
			tb_remove();
		});
	});
})()