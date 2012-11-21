<?php

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
	global $bp, $social_networking_fields;

	$bp_this_field_name = bp_get_the_profile_field_name();
	
	if ( isset ( $social_networking_fields[$bp_this_field_name] ) ) {
		// Get the meat of the url from the array, like "twitter.com".	
		$social_url = str_replace( '/***', '', $social_networking_fields[$bp_this_field_name] );
		
		// Build a regular expressions replace statement customized to the profile field we're working on. The preg_replace cleans up things like facebook.com/user, http://facebook.com/user and returns "user"
		$regex_social_url = preg_quote($social_url, '/'); //Escapes the target URL, if necessary, like if you're linking to a BuddyPress site that uses the form http://site.com/members/user
		$pattern = '/(http[s]?:\/\/)?(www\.)?' . $regex_social_url . '\/(.+)/i';
		$replacement = '$3';
		$field_value = preg_replace($pattern, $replacement, $field_value);
		
		// Build the anchor and output		
		$field_value = '<a href="http://' . $social_url . '/' . $field_value . '">' . $field_value . '</a>';
		return $field_value;		
	}
	
	return $field_value;
}
add_filter( 'bp_get_the_profile_field_value', 'cpfb_add_social_networking_links', 1 );



function cpfb_unlink_fields( $field_value ) { //updated to use WP options DONE
	
	$options = get_option('cpfb_no_link_options');
	
	$bp_this_field_id = bp_get_the_profile_field_id();
	
	if ( in_array( $bp_this_field_id, $options ) )
		$field_value = strip_tags( $field_value );
	
	return $field_value;
		
}
add_filter( 'bp_get_the_profile_field_value', 'cpfb_unlink_fields', 998, 1 );



function cpfb_handle_url_fields( $field_value ) { //updated to use WP options
	global $bp;

	$options = get_option('cpfb_url_options');

	$bp_this_field_id = bp_get_the_profile_field_id();

   if ( in_array( $bp_this_field_id, $options ) && !empty($field_value) && ((strtolower($field_value) != 'n/a')) ) {

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

	return $field_value;
}
add_filter( 'bp_get_the_profile_field_value', 'cpfb_handle_url_fields', 999 );

/*Admin Options Screen
*
*/

// add the admin options page
add_action('admin_menu', 'cpfb_admin_add_page');

function cpfb_admin_add_page() {
	
	add_users_page(
		'Custom Profile Filters for BuddyPress Options', 
		'Profile Field Filters', 
		'manage_options', 
		'cpfb_options', 
		'cpfb_options_page'
		);
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

	add_settings_field(
		'cpfb_social_inputs', // ID for the field
		__('Social Web IDs', 'cpfb'), // Text string. Outputs field title. Make translatable.
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

}

function cpfb_social_section_text() {
	echo '<p>Main description of this section here.</p>';
}

function cpfb_display_social_inputs() {
	global $xprofile_field_names, $fields_as_array;
	$options = get_option('cpfb_social_options');

	print_r($options); ?>
	<?php
	//print_r($xprofile_field_names);

	//echo "<input id='cpfb_social_fields' name='cpfb_options[checkboxes]' size='40' type='text' value='{$options['text_string']}' />";
	//print_r($fields_as_array); 
	if ( !empty( $xprofile_field_names ) ) : foreach ( $xprofile_field_names as $xprofile_field_name ) :
		$f_key = $xprofile_field_name['id'];
		$f_value = $xprofile_field_name['value'];
		//$text_string = 'text_string_' . $f_key
		?>
		<li>
		<!-- <input type="checkbox" id="cpfb_social_fields[<?php echo $f_key ?>]" name="cpfb_social_options[<?php echo $f_key ?>]" value="<?php echo $f_key ?>"<?php checked( isset( $options[$f_key] ) ); ?> /> -->
		<label for="cpfb_social_fields[<?php echo $f_key; ?>]"><?php echo $f_value; ?></label>
		<input id='cpfb_social_fields[<?php echo $f_key ?>]' name='cpfb_social_options[<?php echo $f_key ?>]' size='40' type='text' value='<?php echo $options[$f_key]; ?>' />
		
		</li>

		<?php
	endforeach; // $xprofile_field_names as $xprofile_field_name
	endif;
}

function cpfb_no_link_section_text() {
	echo '<p>Main description of the no link section here.</p>';
}

function cpfb_display_no_link_inputs() {
	global $xprofile_field_names;
	$no_link_options = get_option('cpfb_no_link_options');
	print_r($no_link_options);
	//print_r($xprofile_field_names);

	//echo "<input id='cpfb_social_fields' name='cpfb_options[checkboxes]' size='40' type='text' value='{$options['text_string']}' />";
	if ( !empty( $xprofile_field_names ) ) : foreach ( $xprofile_field_names as $xprofile_field_name ) :
	$f_key = $xprofile_field_name['id'];
	$f_value = $xprofile_field_name['value'];
	?>
	<li>
	<input type="checkbox" id="cpfb_no_link_fields[<?php echo $f_key ?>]" name="cpfb_no_link_options[<?php echo $f_key ?>]" value="<?php echo $f_key ?>"<?php checked( isset( $no_link_options[$f_key] ) ); ?> />
	<label for="cpfb_no_link_fields[<?php echo $f_key; ?>]"><?php echo $f_value; ?></label>
	</li>

	<?php
	endforeach; // $xprofile_field_names as $xprofile_field_name
	endif;
}

function cpfb_url_section_text() {
echo '<p>Main description of the no link section here.</p>';
}

function cpfb_display_url_inputs() {
	global $xprofile_field_names;
	$url_options = get_option('cpfb_url_options');
	print_r($url_options);
	// print_r($xprofile_field_names);

	//echo "<input id='cpfb_social_fields' name='cpfb_options[checkboxes]' size='40' type='text' value='{$options['text_string']}' />";
	if ( !empty( $xprofile_field_names ) ) : foreach ( $xprofile_field_names as $xprofile_field_name ) :
	$f_key = $xprofile_field_name['id'];
	$f_value = $xprofile_field_name['value'];
	?>
	<li>
	<input type="checkbox" id="cpfb_url_fields[<?php echo $f_key ?>]" name="cpfb_url_options[<?php echo $f_key ?>]" value="<?php echo $f_key ?>"<?php checked( isset( $url_options[$f_key] ) ); ?> />
	<label for="cpfb_url_fields[<?php echo $f_key; ?>]"><?php echo $f_value; ?></label>
	</li>

	<?php
	endforeach; // $xprofile_field_names as $xprofile_field_name
	endif;
}

function cpfb_integer_validate( $options ) {
    // if( !is_array( $options ) || empty( $options ) || ( false === $options ) )
    //     return array();

    // $valid_names = array_keys( $this->defaults );
    // $clean_options = array();

    // foreach( $valid_names as $option_name ) {
    //     if( isset( $options[$option_name] ) && ( 1 == $options[$option_name] ) )
    //         $clean_options[$option_name] = 1;
    //     continue;
    // }
    // unset( $options );
    // return $clean_options;
    return $options;
}

function cpfb_social_validate( $options ) {

	// Don't save empty values
   	foreach( $options as $key => $value ) :
    	if( empty( $options[ $key ] ) )
        	unset( $options[ $key ] );
    endforeach;
    
    return $options;
}