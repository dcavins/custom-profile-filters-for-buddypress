<?php

//Filters for profile output

function cpfb_add_brackets( $field_value ) { //unchanged

	if ( strpos( $field_value, '[' ) | strpos( $field_value, ']' ) ) { // If there is a bracket in a field, this block overrides the auto-linking
		$field_value = strip_tags( $field_value );
		while ( strpos( $field_value, ']') ) {
			$open_delin_pos = strpos( $field_value, '[' );
			$close_delin_pos = strpos( $field_value, ']' );
			$field_value =  substr($field_value, 0, $open_delin_pos) . '<a href="' . site_url( BP_MEMBERS_SLUG ) . '/?s=' . substr($field_value, $open_delin_pos+1, $close_delin_pos - $open_delin_pos - 1) . '">' . substr($field_value, $open_delin_pos+1, $close_delin_pos - $open_delin_pos - 1) . '</a>' . substr($field_value, $close_delin_pos+1);
		}
	}
	
	return $field_value;
}
add_filter( 'bp_get_the_profile_field_value', 'cpfb_add_brackets', 999, 1 );


function cpfb_add_social_networking_links( $field_value ) { 
	//global $bp;

	$options = get_option('cpfb_social_options');

	if ( !empty( $options ) ) {

	$bp_this_field_id = bp_get_the_profile_field_id();

	if ( isset ( $options[$bp_this_field_id] ) ) {

		// Get the meat of the url from the array, like "twitter.com".	
		$social_url = str_replace( '/***', '', $options[$bp_this_field_id] );
		
		// Build a regular expressions replace statement customized to the profile field we're working on. The preg_replace cleans up things like facebook.com/user, http://facebook.com/user and returns "user"
		$regex_social_url = preg_quote($social_url, '/'); //Escapes the target URL, if necessary, like if you're linking to a BuddyPress site that uses the form http://site.com/members/user
		$pattern = '/(http[s]?:\/\/)?(www\.)?' . $regex_social_url . '\/(.+)/i';
		$replacement = '$3';
		$field_value = preg_replace($pattern, $replacement, $field_value);
		
		// Build the anchor and output		
		$field_value = '<a href="http://' . $social_url . '/' . $field_value . '">' . $field_value . '</a>';
		return $field_value;

	}
	
	} //End if ($options)

	return $field_value;
}
add_filter( 'bp_get_the_profile_field_value', 'cpfb_add_social_networking_links', 1 );



function cpfb_unlink_fields( $field_value ) { //updated to use WP options DONE
	
	$options = get_option('cpfb_no_link_options');

	if ( !empty( $options ) ) {

	$bp_this_field_id = bp_get_the_profile_field_id();
	
	if ( in_array( $bp_this_field_id, $options ) )
		$field_value = strip_tags( $field_value );
	
	} //End if ($options)

	return $field_value;

}
add_filter( 'bp_get_the_profile_field_value', 'cpfb_unlink_fields', 998, 1 );



function cpfb_handle_url_fields( $field_value ) { //updated to use WP options Done
	//global $bp;

	$options = get_option('cpfb_url_options');

	if ( !empty($options) ) {

	$bp_this_field_id = bp_get_the_profile_field_id();

   if ( in_array( $bp_this_field_id, $options ) && !empty($field_value) && !empty($options) ) {

    //Create an array of values from the field, using commas, semicolons and spaces as delimiters
    $values = preg_split("/[\s,;]+/", $field_value, 0, PREG_SPLIT_NO_EMPTY);

	    if ( $values ) {
	        foreach ( $values as $value ) {
	            
	            // If the value is a URL, skip it and just make it clickable.
	            if ( preg_match( '@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', $value ) ) {
	                $new_values[] = make_clickable( $value );
	            
	            } else {
	                
	                $value = 'http://' . $value ;   
	                $new_values[] = make_clickable( $value );
	               
	            }
	        }

	        $values = implode( ' ', $new_values );
	        return $values;
	    }
	}

	} //End if ($options)

	return $field_value;
}
add_filter( 'bp_get_the_profile_field_value', 'cpfb_handle_url_fields', 1 );



// Admin Options Screen

// add the admin options page
add_action('admin_menu', 'cpfb_admin_add_page');

function cpfb_admin_add_page() {
	
	$page = add_users_page(
		'Custom Profile Filters for BuddyPress Options', 
		'Profile Field Filters', 
		'manage_options', 
		'cpfb_options', 
		'cpfb_options_page'
		);

	/* Using registered $page handle to hook stylesheet loading */
       add_action( 'admin_print_styles-' . $page, 'cfpb_admin_styles' );
}

function cfpb_admin_styles() {

       wp_enqueue_style( 'cfpb-stylesheet' );

   }

   // Add the plugin's admin settings and such at WP's admin init
add_action('admin_init', 'cpfb_admin_init');

function cpfb_admin_init(){

	add_settings_section(
		'cpfb_options', // ID for the section
		__('Custom Profile Filters for BuddyPress Options', 'cpfb'), // Text string. Outputs section title. Make translatable.
		'cpfb_options_main_section_text', // Callback function. Outputs section description.
		'cpfb_options'	// Page name. Must match do_settings_section function call.
		);
	
	add_settings_field(
		'cpfb_no_link_inputs', // ID for the field
		__('Do Not Apply Search Links to These Fields', 'cpfb'), // Text string. Outputs field title. Make translatable.
		'cpfb_display_no_link_inputs', // Callback function. Outputs form field inputs.
		'cpfb_options', // Page name. Must match do_settings_section function call.
		'cpfb_options' // ID of the settings section that this goes into (same as the first argument of add_settings_section).
		);

	register_setting( 
		'cpfb_options', // Group name. Must match the settings_fields function call
		'cpfb_no_link_options', // Name of the option
		'cpfb_integer_validate' // Callback function for validation.
		);

	add_settings_field(
		'cpfb_social_inputs', // ID for the field
		__('Social Networking Profiles', 'cpfb'), // Text string. Outputs field title. Make translatable.
		'cpfb_display_social_inputs', // Callback function. Outputs form field inputs.
		'cpfb_options', // Page name. Must match do_settings_section function call.
		'cpfb_options' // ID of the settings section that this goes into (same as the first argument of add_settings_section).
		);

	register_setting( 
		'cpfb_options', // Group name. Must match the settings_fields function call
		'cpfb_social_options', // Name of the option
		'cpfb_social_validate' // Callback function for validation.
		);

	add_settings_field(
		'cpfb_url_inputs', // ID for the field
		__('Web Address Fields', 'cpfb'), // Text string. Outputs field title. Make translatable.
		'cpfb_display_url_inputs', // Callback function. Outputs form field inputs.
		'cpfb_options', // Page name. Must match do_settings_section function call.
		'cpfb_options' // ID of the settings section that this goes into (same as the first argument of add_settings_section).
		);

	register_setting( 
		'cpfb_options', // Group name. Must match the settings_fields function call
		'cpfb_url_options', // Name of the option
		'cpfb_integer_validate' // Callback function for validation.
		);

	wp_register_style( 'cfpb-stylesheet', plugins_url('stylesheet.css', __FILE__) );

}


// display the admin options page
function cpfb_options_page() {
	global $bp, $wpdb, $groups, $xprofile_field_names, $fields_as_array;
	
	if ( ! isset( $_REQUEST['settings-updated'] ) )
		$_REQUEST['settings-updated'] = false;
	
	$groups = BP_XProfile_Group::get( array(
		'fetch_fields' => true
	) );

	$xprofile_field_names_build = array();
	if ( !empty( $groups ) ) : foreach ( $groups as $group ) :

	if ( !empty( $group->fields ) ) : foreach ( $group->fields as $field ) :

				// Load the field
				$field = new BP_XProfile_Field( $field->id );
				$visibility = !empty( $field->default_visibility ) ? $field->default_visibility : 'public';
				$vis_allowed = !empty( $field->allow_custom_visibility ) ? $field->allow_custom_visibility : 'allowed';

				//$fields_as_array[] = $field;

				if ( ( $visibility == 'adminsonly' ) && ( $vis_allowed == 'disabled' ) ) :
					//don't bother to show the field if the visibility is the most restrictive and the user can't change
					else: 

					$field_to_array = array(
						'id' => $field->id,
						'value' => $field->name,
						);
					$xprofile_field_names_build[] = $field_to_array;
				endif;
				
			endforeach; // end $group->fields as $field

		endif; // end $group->fields

	endforeach; //end $groups as $group
	endif; //end $groups

	$xprofile_field_names = $xprofile_field_names_build; 

	?>
	<div class="wrap">
	<?php screen_icon( 'users' ); ?>
	<h2>Custom Profile Field Filters for BuddyPress</h2>
	<?php if ( $_REQUEST['settings-updated'] !== false ) : ?>
		<div class="updated fade"><p><strong><?php _e( 'Options saved', 'cpfb' ); ?></strong></p></div>
	<?php endif; ?>
	<form action="options.php" method="post">

	<p>Out of the box, BuddyPress automatically turns some words and phrases in the fields of a user&rsquo;s profile into links that, when clicked, search the user&rsquo;s community for other profiles containing those phrases. When activated, this plugin allows users and administrators to have more control over these links, in the following ways:</p>

	<ol>

		<li>
			<p>By using square brackets in a profile field, users can specify which words or phrases in their profile turn into links. For example: under Interests, I might list &ldquo;Cartoons about dogs&rdquo;. By default, Buddypress will turn the entire phrase into a link that searches the community for others who like cartoons about dogs. If I instead type &ldquo;[Cartoons] about [dogs]&rdquo;, then the two words in brackets will turn into independent links.</p>

			<p>You might want to insert a small explanation into your BP profile edit template (/wp-content/bp-themes/[your-member-theme]/profile/edit.php that tells your site&rsquo;s users how to use these brackets. Here&rsquo;s what I use:<p>
	
			<blockquote>&ldquo;Words or phrases in your profile can be linked to the profiles of other members that contain the same phrases. To specify which words or phrases should be linked, add square brackets: e.g. &ldquo;I enjoy [English literature] and [technology].&rdquo; If you do not specify anything, phrases will be chosen automatically.&rdquo;</blockquote>

		</li>

		<li>
			<p>Administrators can specify certain profile fields that will not turn into links at all. For instance, you might not want to add search links to fields like &lsquo;Phone&rsquo;, &lsquo;IM&rsquo;, and &lsquo;Skype ID&rsquo; will not become linkable (it doesn&rsquo;t make much sense to search a community for what should be a unique handle, after all).</p>

		</li>


		<li>

			<p>Administrators can specify certain profile fields that link to social networking profiles. If I enter my Twitter handle &lsquo;boonebgorges&rsquo; into a field labeled &lsquo;Twitter&rsquo;, for example, this plugin will bypass the default link to a BuddyPress search on &lsquo;boonebgorges&rsquo; and instead link to http://twitter.com/boonebgorges.</p>

		</li>

		<li>

			<p>Administrators can specify certain profile fields that are web addresses only, like &ldquo;web sites&rdquo; or &ldquo;blog.&rdquo; Then, the filter will clean up partial addresses like www.sample.com and output them as links to http://www.sample.com.</p>

		</li>

	</ol>

	<?php settings_fields('cpfb_options'); ?>
	<?php do_settings_sections('cpfb_options'); ?>

	<?php
	// echo "Field Names:";
	// print_r($xprofile_field_names);
	?>


	 
	<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
	</form>

	</div>
	 
	<?php
}

function cpfb_options_main_section_text() {}

function cpfb_display_social_inputs() {
	global $xprofile_field_names, $fields_as_array;
	$options = get_option('cpfb_social_options');

	//print_r($options); ?>

	<p>Enter the URL format for profiles on each social networking site with *** in place of the user name. Thus, since the URL for the profile of awesometwitteruser is twitter.com/awesometwitteruser, you should enter &lsquo;twitter.com/***&rsquo; in the text field for &lsquo;Twitter&rsquo;. <strong>Don&rsquo;t include the &lsquo;http://&rsquo;</strong>.</p>

	<?php
	if ( !empty( $xprofile_field_names ) ) : 

		echo '<ul class="nobullet">';
		foreach ( $xprofile_field_names as $xprofile_field_name ) :
		$f_key = $xprofile_field_name['id'];
		$f_value = $xprofile_field_name['value'];
		//$text_string = 'text_string_' . $f_key
		?>
		<li>
		<!-- <input type="checkbox" id="cpfb_social_fields[<?php echo $f_key ?>]" name="cpfb_social_options[<?php echo $f_key ?>]" value="<?php echo $f_key ?>"<?php checked( isset( $options[$f_key] ) ); ?> /> -->
		<label for="cpfb_social_fields[<?php echo $f_key; ?>]"><?php echo $f_value; ?></label>
		<input id='cpfb_social_fields[<?php echo $f_key ?>]' name='cpfb_social_options[<?php echo $f_key ?>]' size='40' type='text' value='<?php if (isset($options[$f_key]) ) { echo $options[$f_key]; } ?>' />
		</li>
		<?php
		endforeach; // $xprofile_field_names as $xprofile_field_name
		echo '</ul>';

	endif;
}

function cpfb_display_no_link_inputs() {
	global $xprofile_field_names;
	$no_link_options = get_option('cpfb_no_link_options');
	// print_r($no_link_options);

	if ( !empty( $xprofile_field_names ) ) : 
		
		echo '<ul class="nobullet">';
		foreach ( $xprofile_field_names as $xprofile_field_name ) :
		$f_key = $xprofile_field_name['id'];
		$f_value = $xprofile_field_name['value'];
		?>
		<li>
		<input type="checkbox" id="cpfb_no_link_fields[<?php echo $f_key ?>]" name="cpfb_no_link_options[<?php echo $f_key ?>]" value="<?php echo $f_key ?>"<?php checked( isset( $no_link_options[$f_key] ) ); ?> />
		<label for="cpfb_no_link_fields[<?php echo $f_key; ?>]"><?php echo $f_value; ?></label>
		</li>
		<?php
		endforeach; // $xprofile_field_names as $xprofile_field_name
		echo '</ul>';

	endif;
}

function cpfb_display_url_inputs() {
	global $xprofile_field_names;
	$url_options = get_option('cpfb_url_options');
	
	// print_r($url_options);
	
	if ( !empty( $xprofile_field_names ) ) :

		echo '<ul class="nobullet">';
		foreach ( $xprofile_field_names as $xprofile_field_name ) :
		$f_key = $xprofile_field_name['id'];
		$f_value = $xprofile_field_name['value'];
		?>
		<li>
		<input type="checkbox" id="cpfb_url_fields[<?php echo $f_key ?>]" name="cpfb_url_options[<?php echo $f_key ?>]" value="<?php echo $f_key ?>"<?php checked( isset( $url_options[$f_key] ) ); ?> />
		<label for="cpfb_url_fields[<?php echo $f_key; ?>]"><?php echo $f_value; ?></label>
		</li>
		<?php
		endforeach; // $xprofile_field_names as $xprofile_field_name
		echo '</ul>';

	endif;
}

function cpfb_integer_validate( $options ) {
    if( !is_array( $options ) || empty( $options ) || ( false === $options ) )
        return array();

    $clean_options = array_map('intval', $options);

    unset( $options );

    return $clean_options;
}

function cpfb_social_validate( $options ) {

	// Don't save empty values
   	foreach( $options as $key => $value ) :

    	if( empty( $options[ $key ] ) )
        	unset( $options[ $key ] );

    endforeach;

    // Check text input values
    $clean_options = array_map('sanitize_text_field', $options);

    unset( $options );
    
    return $clean_options;
}