<?php
/*
Plugin Name: Krieger Flagship Content Types
Plugin URI: http://krieger.jhu.edu/
Description: This plugin should be only be used on the flagship krieger.jhu.edu site.  Creates the custom post types: Fields of Study and Evergreens (Homepage Slides).
Version: 2.0
Author: KSAS Communications
Author URI: mailto:ksasweb@jhu.edu
License: GPL2
*/

// registration code for Fields of Study post type
function register_studyfields_posttype() {
	$labels = array(
		'name'               => _x( 'Fields of Study', 'post type general name' ),
		'singular_name'      => _x( 'Field of Study', 'post type singular name' ),
		'add_new'            => __( 'Add New Field of Study' ),
		'add_new_item'       => __( 'Add New Field of Study' ),
		'edit_item'          => __( 'Edit Field of Study' ),
		'new_item'           => __( 'New Field of Study' ),
		'view_item'          => __( 'View Field of Study' ),
		'search_items'       => __( 'Search Fields of Study' ),
		'not_found'          => __( 'No Fields of Study found' ),
		'not_found_in_trash' => __( 'No Fields of Study found in Trash' ),
		'parent_item_colon'  => __( '' ),
		'menu_name'          => __( 'Fields of Study' ),
	);

	$taxonomies = array();

	$supports = array( 'title', 'revisions', 'page-attributes' );

	$post_type_args = array(
		'labels'             => $labels,
		'singular_label'     => __( 'Field of Study' ),
		'public'             => true,
		'show_ui'            => true,
		'publicly_queryable' => true,
		'query_var'          => true,
		'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => true,
		'rewrite'            => array(
			'slug'       => 'fields',
			'with_front' => false,
		),
		'supports'           => $supports,
		'menu_position'      => 5,
		'taxonomies'         => $taxonomies,
		'show_in_nav_menus'  => true,
		'show_in_rest'       => true,
	);
	 register_post_type( 'studyfields', $post_type_args );
}
	add_action( 'init', 'register_studyfields_posttype' );


$taxonomies_4_metabox = array(
	'id'       => 'taxonomies',
	'title'    => 'Taxonomies',
	'page'     => array( 'studyfields' ),
	'context'  => 'side',
	'priority' => 'high',
	'fields'   => array(
		array(
			'name'    => 'Level',
			'desc'    => '',
			'id'      => 'ecpt_field_level',
			'class'   => 'ecpt_field_level',
			'type'    => 'select',
			'options' => array( 'undergraduate', 'full-graduate', 'part-graduate' ),
			'max'     => 0,
			'std'     => '',
		),
	),
);

add_action( 'admin_menu', 'ecpt_add_taxonomies_4_meta_box' );
function ecpt_add_taxonomies_4_meta_box() {

	global $taxonomies_4_metabox;

	foreach ( $taxonomies_4_metabox['page'] as $page ) {
		add_meta_box( $taxonomies_4_metabox['id'], $taxonomies_4_metabox['title'], 'ecpt_show_taxonomies_4_box', $page, 'side', 'high', $taxonomies_4_metabox );
	}
}

// function to show meta boxes
function ecpt_show_taxonomies_4_box() {
	global $post;
	global $taxonomies_4_metabox;
	global $ecpt_prefix;
	global $wp_version;

	// Use nonce for verification
	echo '<input type="hidden" name="ecpt_taxonomies_4_meta_box_nonce" value="', wp_create_nonce( basename( __FILE__ ) ), '" />';

	echo '<table class="form-table">';

	foreach ( $taxonomies_4_metabox['fields'] as $field ) {
		// get current post meta data

		$meta = get_post_meta( $post->ID, $field['id'], true );

		echo '<tr>',
				'<th style="width:20%"><label for="', $field['id'], '">', stripslashes( $field['name'] ), '</label></th>',
				'<td class="ecpt_field_type_' . str_replace( ' ', '_', $field['type'] ) . '">';
		switch ( $field['type'] ) {
			case 'select':
				echo '<select name="', $field['id'], '" id="', $field['id'], '">';
				foreach ( $field['options'] as $option ) {
					echo '<option value="' . $option . '"', $meta == $option ? ' selected="selected"' : '', '>', $option, '</option>';
				}
				echo '</select>', '', stripslashes( $field['desc'] );
				break;
			case 'multicheck':
				foreach ( $field['options'] as $option ) {
					echo '<input type="checkbox" name="' . $field['id'] . '[' . $option . ']" value="' . $option . '"' . checked( true, in_array( $option, $meta ), false ) . '/> ' . $option;
				}
				echo '<br/>' . stripslashes( $field['desc'] );
				break;

		}
		echo '<td>',
			'</tr>';
	}

	echo '</table>';
}

// Save data from meta box
add_action( 'save_post', 'ecpt_taxonomies_4_save' );
function ecpt_taxonomies_4_save( $post_id ) {
	global $post;
	global $taxonomies_4_metabox;

	// verify nonce
	if ( ! isset( $_POST['ecpt_taxonomies_4_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['ecpt_taxonomies_4_meta_box_nonce'], basename( __FILE__ ) ) ) {
		return $post_id;
	}

	// check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}

	// check permissions
	if ( 'page' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		}
	} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
		return $post_id;
	}

	foreach ( $taxonomies_4_metabox['fields'] as $field ) {

		$old = get_post_meta( $post_id, $field['id'], true );
		$new = $_POST[ $field['id'] ];

		if ( $new && $new != $old ) {
			if ( $field['type'] == 'date' ) {
				$new = ecpt_format_date( $new );
				update_post_meta( $post_id, $field['id'], $new );
			} else {
				if ( is_string( $new ) ) {
					$new = $new;
				}
				update_post_meta( $post_id, $field['id'], $new );

			}
		} elseif ( '' == $new && $old ) {
			delete_post_meta( $post_id, $field['id'], $old );
		}
	}
}

$basicinformation_5_metabox = array(
	'id'       => 'basicinformation',
	'title'    => 'Basic Information',
	'page'     => array( 'studyfields' ),
	'context'  => 'normal',
	'priority' => 'default',
	'fields'   => array(


		array(
			'name'        => 'Phone Number',
			'desc'        => '',
			'id'          => 'ecpt_phonenumber',
			'class'       => 'ecpt_phonenumber',
			'type'        => 'text',
			'rich_editor' => 0,
			'max'         => 0,
			'std'         => '',
		),

		array(
			'name'        => 'Email Address',
			'desc'        => '',
			'id'          => 'ecpt_emailaddress',
			'class'       => 'ecpt_emailaddress',
			'type'        => 'text',
			'rich_editor' => 0,
			'max'         => 0,
			'std'         => '',
		),

		array(
			'name'        => 'Majors',
			'desc'        => 'Separate with commas',
			'id'          => 'ecpt_majors',
			'class'       => 'ecpt_majors',
			'type'        => 'textarea',
			'rich_editor' => 0,
			'max'         => 0,
			'std'         => '',
		),

		array(
			'name'        => 'Minors',
			'desc'        => 'Separate with commas',
			'id'          => 'ecpt_minors',
			'class'       => 'ecpt_minors',
			'type'        => 'textarea',
			'rich_editor' => 0,
			'max'         => 0,
			'std'         => '',
		),

		array(
			'name'        => 'Degrees Offered',
			'desc'        => 'Separate with commas',
			'id'          => 'ecpt_degreesoffered',
			'class'       => 'ecpt_degreesoffered',
			'type'        => 'text',
			'rich_editor' => 1,
			'max'         => 0,
			'std'         => '',
		),

		array(
			'name'        => 'Alt Major/Minor Area text',
			'desc'        => 'Separate with commas',
			'id'          => 'ecpt_pcitext',
			'class'       => 'ecpt_pcitext',
			'type'        => 'textarea',
			'rich_editor' => 0,
			'max'         => 0,
			'std'         => '',
		),
		array(
			'name'        => 'Location',
			'desc'        => 'Building and room number only',
			'id'          => 'ecpt_location',
			'class'       => 'ecpt_location',
			'type'        => 'text',
			'rich_editor' => 0,
			'max'         => 0,
			'std'         => '',
		),
	),
);

add_action( 'admin_menu', 'ecpt_add_basicinformation_5_meta_box' );
function ecpt_add_basicinformation_5_meta_box() {

	global $basicinformation_5_metabox;

	foreach ( $basicinformation_5_metabox['page'] as $page ) {
		add_meta_box( $basicinformation_5_metabox['id'], $basicinformation_5_metabox['title'], 'ecpt_show_basicinformation_5_box', $page, 'normal', 'default', $basicinformation_5_metabox );
	}
}

// function to show meta boxes
function ecpt_show_basicinformation_5_box() {
	global $post;
	global $basicinformation_5_metabox;
	global $ecpt_prefix;
	global $wp_version;

	// Use nonce for verification
	echo '<input type="hidden" name="ecpt_basicinformation_5_meta_box_nonce" value="', wp_create_nonce( basename( __FILE__ ) ), '" />';

	echo '<table class="form-table">';

	foreach ( $basicinformation_5_metabox['fields'] as $field ) {
		// get current post meta data

		$meta = get_post_meta( $post->ID, $field['id'], true );

		echo '<tr>',
				'<th style="width:20%"><label for="', $field['id'], '">', stripslashes( $field['name'] ), '</label></th>',
				'<td class="ecpt_field_type_' . str_replace( ' ', '_', $field['type'] ) . '">';
		switch ( $field['type'] ) {
			case 'text':
				echo '<input type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : $field['std'], '" size="30" style="width:97%" /><br/>', '', stripslashes( $field['desc'] );
				break;
			case 'textarea':
				if ( $field['rich_editor'] == 1 ) {
						echo wp_editor(
							$meta,
							$field['id'],
							array(
								'textarea_name' => $field['id'],
								'wpautop'       => false,
							)
						); } else {
					echo '<div style="width: 100%;"><textarea name="', $field['id'], '" class="', $field['class'], '" id="', $field['id'], '" cols="60" rows="8" style="width:97%">', $meta ? $meta : $field['std'], '</textarea></div>', '', stripslashes( $field['desc'] );
						}

				break;
		}
		echo '<td>',
			'</tr>';
	}

	echo '</table>';
}

// Save data from meta box
add_action( 'save_post', 'ecpt_basicinformation_5_save' );
function ecpt_basicinformation_5_save( $post_id ) {
	global $post;
	global $basicinformation_5_metabox;

	// verify nonce
	if ( ! isset( $_POST['ecpt_basicinformation_5_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['ecpt_basicinformation_5_meta_box_nonce'], basename( __FILE__ ) ) ) {
		return $post_id;
	}

	// check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}

	// check permissions
	if ( 'page' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		}
	} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
		return $post_id;
	}

	foreach ( $basicinformation_5_metabox['fields'] as $field ) {

		$old = get_post_meta( $post_id, $field['id'], true );
		$new = $_POST[ $field['id'] ];

		if ( $new && $new != $old ) {
			if ( $field['type'] == 'date' ) {
				$new = ecpt_format_date( $new );
				update_post_meta( $post_id, $field['id'], $new );
			} else {
				if ( is_string( $new ) ) {
					$new = $new;
				}
				update_post_meta( $post_id, $field['id'], $new );

			}
		} elseif ( '' == $new && $old ) {
			delete_post_meta( $post_id, $field['id'], $old );
		}
	}
}

$websiteurls_6_metabox = array(
	'id'       => 'websiteurls',
	'title'    => 'Website URLs (do NOT include http://)',
	'page'     => array( 'studyfields' ),
	'context'  => 'normal',
	'priority' => 'default',
	'fields'   => array(


		array(
			'name'        => 'Homepage',
			'desc'        => 'Required for all',
			'id'          => 'ecpt_homepage',
			'class'       => 'ecpt_homepage',
			'type'        => 'text',
			'rich_editor' => 1,
			'max'         => 0,
			'std'         => '',
		),

		array(
			'name'        => 'Faculty page',
			'desc'        => 'Only for departments',
			'id'          => 'ecpt_facultypage',
			'class'       => 'ecpt_facultypage',
			'type'        => 'text',
			'rich_editor' => 1,
			'max'         => 0,
			'std'         => '',
		),

		array(
			'name'        => 'Undergraduate program page',
			'desc'        => 'Only for departments',
			'id'          => 'ecpt_undergraduatepage',
			'class'       => 'ecpt_undergraduatepage',
			'type'        => 'text',
			'rich_editor' => 1,
			'max'         => 0,
			'std'         => '',
		),

		array(
			'name'        => 'Graduate program page',
			'desc'        => 'Only for departments',
			'id'          => 'ecpt_graduatepage',
			'class'       => 'ecpt_graduatepage',
			'type'        => 'text',
			'rich_editor' => 0,
			'max'         => 0,
			'std'         => '',
		),
	),
);

add_action( 'admin_menu', 'ecpt_add_websiteurls_6_meta_box' );
function ecpt_add_websiteurls_6_meta_box() {

	global $websiteurls_6_metabox;

	foreach ( $websiteurls_6_metabox['page'] as $page ) {
		add_meta_box( $websiteurls_6_metabox['id'], $websiteurls_6_metabox['title'], 'ecpt_show_websiteurls_6_box', $page, 'normal', 'default', $websiteurls_6_metabox );
	}
}

// function to show meta boxes
function ecpt_show_websiteurls_6_box() {
	global $post;
	global $websiteurls_6_metabox;
	global $ecpt_prefix;
	global $wp_version;

	// Use nonce for verification
	echo '<input type="hidden" name="ecpt_websiteurls_6_meta_box_nonce" value="', wp_create_nonce( basename( __FILE__ ) ), '" />';

	echo '<table class="form-table">';

	foreach ( $websiteurls_6_metabox['fields'] as $field ) {
		// get current post meta data

		$meta = get_post_meta( $post->ID, $field['id'], true );

		echo '<tr>',
				'<th style="width:20%"><label for="', $field['id'], '">', stripslashes( $field['name'] ), '</label></th>',
				'<td class="ecpt_field_type_' . str_replace( ' ', '_', $field['type'] ) . '">';
		switch ( $field['type'] ) {
			case 'text':
				echo '<input type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : $field['std'], '" size="30" style="width:97%" /><br/>', '', stripslashes( $field['desc'] );
				break;
		}
		echo '<td>',
			'</tr>';
	}

	echo '</table>';
}

// Save data from meta box
add_action( 'save_post', 'ecpt_websiteurls_6_save' );
function ecpt_websiteurls_6_save( $post_id ) {
	global $post;
	global $websiteurls_6_metabox;

	// verify nonce
	if ( ! isset( $_POST['ecpt_websiteurls_6_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['ecpt_websiteurls_6_meta_box_nonce'], basename( __FILE__ ) ) ) {
		return $post_id;
	}

	// check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}

	// check permissions
	if ( 'page' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		}
	} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
		return $post_id;
	}

	foreach ( $websiteurls_6_metabox['fields'] as $field ) {

		$old = get_post_meta( $post_id, $field['id'], true );
		$new = $_POST[ $field['id'] ];

		if ( $new && $new != $old ) {
			if ( $field['type'] == 'date' ) {
				$new = ecpt_format_date( $new );
				update_post_meta( $post_id, $field['id'], $new );
			} else {
				if ( is_string( $new ) ) {
					$new = $new;
				}
				update_post_meta( $post_id, $field['id'], $new );

			}
		} elseif ( '' == $new && $old ) {
			delete_post_meta( $post_id, $field['id'], $old );
		}
	}
}

$maincontent_8_metabox = array(
	'id'       => 'maincontent',
	'title'    => 'Main Content',
	'page'     => array( 'studyfields' ),
	'context'  => 'normal',
	'priority' => 'high',
	'fields'   => array(


		array(
			'name'        => 'Section 1 Content',
			'desc'        => 'This is the main description',
			'id'          => 'ecpt_section1',
			'class'       => 'ecpt_section1',
			'type'        => 'textarea',
			'rich_editor' => 1,
			'max'         => 0,
			'std'         => '',
		),
	),
);

add_action( 'admin_menu', 'ecpt_add_maincontent_8_meta_box' );
function ecpt_add_maincontent_8_meta_box() {

	global $maincontent_8_metabox;

	foreach ( $maincontent_8_metabox['page'] as $page ) {
		add_meta_box( $maincontent_8_metabox['id'], $maincontent_8_metabox['title'], 'ecpt_show_maincontent_8_box', $page, 'normal', 'high', $maincontent_8_metabox );
	}
}

// function to show meta boxes
function ecpt_show_maincontent_8_box() {
	global $post;
	global $maincontent_8_metabox;
	global $ecpt_prefix;
	global $wp_version;

	// Use nonce for verification
	echo '<input type="hidden" name="ecpt_maincontent_8_meta_box_nonce" value="', wp_create_nonce( basename( __FILE__ ) ), '" />';

	echo '<table class="form-table">';

	foreach ( $maincontent_8_metabox['fields'] as $field ) {
		// get current post meta data

		$meta = get_post_meta( $post->ID, $field['id'], true );

		echo '<tr>',
				'<th style="width:20%"><label for="', $field['id'], '">', stripslashes( $field['name'] ), '</label></th>',
				'<td class="ecpt_field_type_' . str_replace( ' ', '_', $field['type'] ) . '">';
		switch ( $field['type'] ) {
			case 'text':
				echo '<input type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : $field['std'], '" size="30" style="width:97%" /><br/>', '', stripslashes( $field['desc'] );
				break;
			case 'textarea':
				if ( $field['rich_editor'] == 1 ) {
						echo wp_editor(
							$meta,
							$field['id'],
							array(
								'textarea_name' => $field['id'],
								'wpautop'       => false,
							)
						); } else {
					echo '<div style="width: 100%;"><textarea name="', $field['id'], '" class="', $field['class'], '" id="', $field['id'], '" cols="60" rows="8" style="width:97%">', $meta ? $meta : $field['std'], '</textarea></div>', '', stripslashes( $field['desc'] );
						}

				break;
		}
		echo '<td>',
			'</tr>';
	}

	echo '</table>';
}

// Save data from meta box
add_action( 'save_post', 'ecpt_maincontent_8_save' );
function ecpt_maincontent_8_save( $post_id ) {
	global $post;
	global $maincontent_8_metabox;

	// verify nonce
	if ( ! isset( $_POST['ecpt_maincontent_8_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['ecpt_maincontent_8_meta_box_nonce'], basename( __FILE__ ) ) ) {
		return $post_id;
	}

	// check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}

	// check permissions
	if ( 'page' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		}
	} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
		return $post_id;
	}

	foreach ( $maincontent_8_metabox['fields'] as $field ) {

		$old = get_post_meta( $post_id, $field['id'], true );
		$new = $_POST[ $field['id'] ];

		if ( $new && $new != $old ) {
			if ( $field['type'] == 'date' ) {
				$new = ecpt_format_date( $new );
				update_post_meta( $post_id, $field['id'], $new );
			} else {
				if ( is_string( $new ) ) {
					$new = $new;
				}
				update_post_meta( $post_id, $field['id'], $new );

			}
		} elseif ( '' == $new && $old ) {
			delete_post_meta( $post_id, $field['id'], $old );
		}
	}
}

$topsection_10_metabox = array(
	'id'       => 'topsection',
	'title'    => 'Top Section',
	'page'     => array( 'studyfields' ),
	'context'  => 'normal',
	'priority' => 'high',
	'fields'   => array(


		array(
			'name'        => 'Headline',
			'desc'        => '',
			'id'          => 'ecpt_headline',
			'class'       => 'ecpt_headline',
			'type'        => 'text',
			'rich_editor' => 0,
			'max'         => 0,
			'std'         => '',
		),

		array(
			'name'        => 'Subhead',
			'desc'        => '',
			'id'          => 'ecpt_subhead',
			'class'       => 'ecpt_subhead',
			'type'        => 'text',
			'rich_editor' => 0,
			'max'         => 0,
			'std'         => '',
		),

		array(
			'name'        => 'Image',
			'desc'        => 'This is the image that displays on the landing page',
			'id'          => 'ecpt_image',
			'class'       => 'ecpt_image',
			'type'        => 'upload',
			'rich_editor' => 0,
			'max'         => 0,
			'std'         => '',
		),
	),
);

add_action( 'admin_menu', 'ecpt_add_topsection_10_meta_box' );
function ecpt_add_topsection_10_meta_box() {

	global $topsection_10_metabox;

	foreach ( $topsection_10_metabox['page'] as $page ) {
		add_meta_box( $topsection_10_metabox['id'], $topsection_10_metabox['title'], 'ecpt_show_topsection_10_box', $page, 'normal', 'high', $topsection_10_metabox );
	}
}

// function to show meta boxes
function ecpt_show_topsection_10_box() {
	global $post;
	global $topsection_10_metabox;
	global $ecpt_prefix;
	global $wp_version;

	// Use nonce for verification
	echo '<input type="hidden" name="ecpt_topsection_10_meta_box_nonce" value="', wp_create_nonce( basename( __FILE__ ) ), '" />';

	echo '<table class="form-table">';

	foreach ( $topsection_10_metabox['fields'] as $field ) {
		// get current post meta data

		$meta = get_post_meta( $post->ID, $field['id'], true );

		echo '<tr>',
				'<th style="width:20%"><label for="', $field['id'], '">', stripslashes( $field['name'] ), '</label></th>',
				'<td class="ecpt_field_type_' . str_replace( ' ', '_', $field['type'] ) . '">';
		switch ( $field['type'] ) {
			case 'text':
				echo '<input type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : $field['std'], '" size="30" style="width:97%" /><br/>', '', stripslashes( $field['desc'] );
				break;
			case 'upload':
				echo '<input type="text" class="ecpt_upload_field" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : $field['std'], '" size="30" style="width:80%" /><br/>', '', stripslashes( $field['desc'] );
				break;
		}
		echo '<td>',
			'</tr>';
	}

	echo '</table>';
}

// Save data from meta box
add_action( 'save_post', 'ecpt_topsection_10_save' );
function ecpt_topsection_10_save( $post_id ) {
	global $post;
	global $topsection_10_metabox;

	// verify nonce
	if ( ! isset( $_POST['ecpt_topsection_10_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['ecpt_topsection_10_meta_box_nonce'], basename( __FILE__ ) ) ) {
		return $post_id;
	}

	// check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}

	// check permissions
	if ( 'page' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		}
	} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
		return $post_id;
	}

	foreach ( $topsection_10_metabox['fields'] as $field ) {

		$old = get_post_meta( $post_id, $field['id'], true );
		$new = $_POST[ $field['id'] ];

		if ( $new && $new != $old ) {
			if ( $field['type'] == 'date' ) {
				$new = ecpt_format_date( $new );
				update_post_meta( $post_id, $field['id'], $new );
			} else {
				if ( is_string( $new ) ) {
					$new = $new;
				}
				update_post_meta( $post_id, $field['id'], $new );

			}
		} elseif ( '' == $new && $old ) {
			delete_post_meta( $post_id, $field['id'], $old );
		}
	}
}

$indexing_11_metabox = array(
	'id'       => 'indexing',
	'title'    => 'Indexing',
	'page'     => array( 'studyfields' ),
	'context'  => 'side',
	'priority' => 'default',
	'fields'   => array(

		array(
			'name'        => 'Keywords',
			'desc'        => 'These will not be displayed.  These are for search terms only. No commas necessary',
			'id'          => 'ecpt_keywords',
			'class'       => 'ecpt_keywords',
			'type'        => 'textarea',
			'rich_editor' => 0,
			'max'         => 0,
			'std'         => '',
		),
	),
);

add_action( 'admin_menu', 'ecpt_add_indexing_11_meta_box' );
function ecpt_add_indexing_11_meta_box() {

	global $indexing_11_metabox;

	foreach ( $indexing_11_metabox['page'] as $page ) {
		add_meta_box( $indexing_11_metabox['id'], $indexing_11_metabox['title'], 'ecpt_show_indexing_11_box', $page, 'side', 'default', $indexing_11_metabox );
	}
}

// function to show meta boxes
function ecpt_show_indexing_11_box() {
	global $post;
	global $indexing_11_metabox;
	global $ecpt_prefix;
	global $wp_version;

	// Use nonce for verification
	echo '<input type="hidden" name="ecpt_indexing_11_meta_box_nonce" value="', wp_create_nonce( basename( __FILE__ ) ), '" />';

	echo '<table class="form-table">';

	foreach ( $indexing_11_metabox['fields'] as $field ) {
		// get current post meta data

		$meta = get_post_meta( $post->ID, $field['id'], true );

		echo '<tr>',
				'<th style="width:20%"><label for="', $field['id'], '">', stripslashes( $field['name'] ), '</label></th>',
				'<td class="ecpt_field_type_' . str_replace( ' ', '_', $field['type'] ) . '">';
		switch ( $field['type'] ) {
			case 'textarea':
				if ( $field['rich_editor'] == 1 ) {
						echo wp_editor(
							$meta,
							$field['id'],
							array(
								'textarea_name' => $field['id'],
								'wpautop'       => false,
							)
						); } else {
					echo '<div style="width: 100%;"><textarea name="', $field['id'], '" class="', $field['class'], '" id="', $field['id'], '" cols="60" rows="8" style="width:97%">', $meta ? $meta : $field['std'], '</textarea></div>', '', stripslashes( $field['desc'] );
						}

				break;
		}
		echo '<td>',
			'</tr>';
	}

	echo '</table>';
}

// Save data from meta box
add_action( 'save_post', 'ecpt_indexing_11_save' );
function ecpt_indexing_11_save( $post_id ) {
	global $post;
	global $indexing_11_metabox;

	// verify nonce
	if ( ! isset( $_POST['ecpt_indexing_11_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['ecpt_indexing_11_meta_box_nonce'], basename( __FILE__ ) ) ) {
		return $post_id;
	}

	// check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}

	// check permissions
	if ( 'page' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		}
	} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
		return $post_id;
	}

	foreach ( $indexing_11_metabox['fields'] as $field ) {

		$old = get_post_meta( $post_id, $field['id'], true );
		$new = $_POST[ $field['id'] ];

		if ( $new && $new != $old ) {
			if ( $field['type'] == 'date' ) {
				$new = ecpt_format_date( $new );
				update_post_meta( $post_id, $field['id'], $new );
			} else {
				if ( is_string( $new ) ) {
					$new = $new;
				}
				update_post_meta( $post_id, $field['id'], $new );

			}
		} elseif ( '' == $new && $old ) {
			delete_post_meta( $post_id, $field['id'], $old );
		}
	}
}

function register_program_type_tax() {
		$labels = array(
			'name'               => _x( 'Program Types', 'taxonomy general name' ),
			'singular_name'      => _x( 'Program Type', 'taxonomy singular name' ),
			'add_new'            => _x( 'Add New Program Type', 'Program Type' ),
			'add_new_item'       => __( 'Add New Program Type' ),
			'edit_item'          => __( 'Edit Program Type' ),
			'new_item'           => __( 'New Program Type' ),
			'view_item'          => __( 'View Program Type' ),
			'search_items'       => __( 'Search Program Types' ),
			'not_found'          => __( 'No Program Type found' ),
			'not_found_in_trash' => __( 'No Program Type found in Trash' ),
		);

		$pages = array( 'studyfields' );

		$args = array(
			'labels'                => $labels,
			'singular_label'        => __( 'Program Type' ),
			'public'                => true,
			'show_ui'               => true,
			'hierarchical'          => true,
			'show_tagcloud'         => false,
			'show_in_nav_menus'     => false,
			'rewrite'               => array(
				'slug'       => 'department',
				'with_front' => false,
			),
			'rest_base'             => 'department',
			'rest_controller_class' => 'WP_REST_Terms_Controller',
		);
		register_taxonomy( 'program_type', $pages, $args );
}
	add_action( 'init', 'register_program_type_tax' );

function check_program_type_terms() {

		// see if we already have populated any terms
	$term = get_terms( 'program_type', array( 'hide_empty' => false ) );

	// if no terms then lets add our terms
	if ( empty( $term ) ) {
		$terms = define_program_type_terms();
		foreach ( $terms as $term ) {
			if ( ! term_exists( $term['name'], 'program_type' ) ) {
				wp_insert_term( $term['name'], 'program_type', array( 'slug' => $term['slug'] ) );
			}
		}
	}
}

add_action( 'init', 'check_program_type_terms' );

function define_program_type_terms() {

	$terms = array(
		'0' => array(
			'name' => 'Undergraduate',
			'slug' => 'undergrad_program',
		),
		'1' => array(
			'name' => 'Part-time Graduate',
			'slug' => 'part_time_program',
		),
		'2' => array(
			'name' => 'Full-time Graduate',
			'slug' => 'full_time_program',
		),
	);

	return $terms;
}
add_filter( 'manage_edit-studyfields_columns', 'my_studyfields_columns' );

function my_studyfields_columns( $columns ) {

	$columns = array(
		'cb'           => '<input type="checkbox" />',
		'title'        => __( 'Name' ),
		'program_type' => __( 'Program Type' ),
		'date'         => __( 'Date' ),
	);

	return $columns;
}

add_action( 'manage_studyfields_posts_custom_column', 'my_manage_studyfields_columns', 10, 2 );

function my_manage_studyfields_columns( $column, $post_id ) {
	global $post;

	switch ( $column ) {

		/* If displaying the 'program_type' column. */
		case 'program_type':
			/* Get the program_types for the post. */
			$terms = get_the_terms( $post_id, 'program_type' );

			/* If terms were found. */
			if ( ! empty( $terms ) ) {

				$out = array();

				/* Loop through each term, linking to the 'edit posts' page for the specific term. */
				foreach ( $terms as $term ) {
					$out[] = sprintf(
						'<a href="%s">%s</a>',
						esc_url(
							add_query_arg(
								array(
									'post_type'    => $post->post_type,
									'program_type' => $term->slug,
								),
								'edit.php'
							)
						),
						esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, 'program_type', 'display' ) )
					);
				}

				/* Join the terms, separating them with a comma. */
				echo join( ', ', $out );
			}

			/* If no terms were found, output a default message. */
			else {
				_e( 'No Program Assigned' );
			}

			break;
		case 'thumbnail':
			/* Get the thumbnail */
			$thumbnail = get_post_meta( $post->ID, 'ecpt_indeximage', true );

			if ( get_post_meta( $post->ID, 'ecpt_indeximage', true ) ) {
				echo '<img src="' . $thumbnail . '" />';
			}
			/* If there is a duration, append 'minutes' to the text string. */
			else {

				echo __( 'No Photo' );
			}

			break;
		/* Just break out of the switch statement for everything else. */
		default:
			break;
	}
}

// CREATE FILTERS WITH CUSTOM TAXONOMIES
add_action( 'restrict_manage_posts', 'studyfields_add_taxonomy_filters' );

function studyfields_add_taxonomy_filters() {
	global $typenow;

	// An array of all the taxonomyies you want to display. Use the taxonomy name or slug
	$taxonomies = array( 'program_type' );

	// must set this to the post type you want the filter(s) displayed on
	if ( $typenow == 'studyfields' ) {

		foreach ( $taxonomies as $tax_slug ) {
			$current_tax_slug = isset( $_GET[ $tax_slug ] ) ? $_GET[ $tax_slug ] : false;
			$tax_obj          = get_taxonomy( $tax_slug );
			$tax_name         = $tax_obj->labels->name;
			$terms            = get_terms( $tax_slug );
			if ( count( $terms ) > 0 ) {
				echo "<select name='$tax_slug' id='$tax_slug' class='postform'>";
				echo "<option value=''>$tax_name</option>";
				foreach ( $terms as $term ) {
					echo '<option value=' . $term->slug, $current_tax_slug == $term->slug ? ' selected="selected"' : '','>' . $term->name . ' (' . $term->count . ')</option>';
				}
				echo '</select>';
			}
		}
	}
}

add_action( 'rest_api_init', 'create_api_posts_meta_field' );
function create_api_posts_meta_field() {

	// register_rest_field ( 'name-of-post-type', 'name-of-field-to-return', array-of-callbacks-and-schema() )
	register_rest_field(
		'studyfields',
		'post_meta_fields',
		array(
			'get_callback' => 'get_post_meta_for_api',
			'schema'       => null,
		)
	);
}

function get_post_meta_for_api( $object ) {
	// get the id of the post object array
	$post_id = $object['id'];

	// return the post meta
	return get_post_meta( $post_id );
}
