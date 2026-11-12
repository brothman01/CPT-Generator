<?php
/**
 * Plugin Name: CPT Generator
 * Plugin URI: https://benrothman.org
 * Description: Create and manage custom post types from the WordPress admin.
 * Author: Ben Rothman
 * Version: 2.0.0
 * Author URI: https://benrothman.org
 * Text Domain: cpt-generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CPT_GENERATOR_POST_TYPE', 'cpt_generator' );
define( 'CPT_GENERATOR_META_KEY', '_cpt_generator_settings' );
define( 'CPT_GENERATOR_FLUSH_OPTION', '_cpt_generator_flush_rewrite_rules' );
$GLOBALS['cpt_generator_registered_types'] = array();

add_action( 'init', 'cpt_generator_register_types', 0 );
add_action( 'add_meta_boxes_' . CPT_GENERATOR_POST_TYPE, 'cpt_generator_add_meta_box' );
add_action( 'save_post_' . CPT_GENERATOR_POST_TYPE, 'cpt_generator_save_settings' );
add_action( 'transition_post_status', 'cpt_generator_status_changed', 10, 3 );
add_filter( 'manage_' . CPT_GENERATOR_POST_TYPE . '_posts_columns', 'cpt_generator_admin_columns' );
add_action( 'manage_' . CPT_GENERATOR_POST_TYPE . '_posts_custom_column', 'cpt_generator_admin_column', 10, 2 );
add_action( 'admin_notices', 'cpt_generator_admin_notices' );
register_activation_hook( __FILE__, 'cpt_generator_activate' );
register_deactivation_hook( __FILE__, 'cpt_generator_deactivate' );

/**
 * Register the private management post type and all saved post types.
 */
function cpt_generator_register_types() {
	register_post_type(
		CPT_GENERATOR_POST_TYPE,
		array(
			'labels'              => array(
				'name'               => __( 'CPTs', 'cpt-generator' ),
				'singular_name'      => __( 'Custom Post Type', 'cpt-generator' ),
				'menu_name'          => __( 'CPTs', 'cpt-generator' ),
				'add_new_item'       => __( 'Add New Custom Post Type', 'cpt-generator' ),
				'edit_item'          => __( 'Edit Custom Post Type', 'cpt-generator' ),
				'new_item'           => __( 'New Custom Post Type', 'cpt-generator' ),
				'view_item'          => __( 'View Custom Post Type', 'cpt-generator' ),
				'search_items'       => __( 'Search CPTs', 'cpt-generator' ),
				'not_found'          => __( 'No custom post types found.', 'cpt-generator' ),
				'not_found_in_trash' => __( 'No custom post types found in Trash.', 'cpt-generator' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'supports'            => array( 'title' ),
			'menu_icon'           => 'dashicons-screenoptions',
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
		)
	);

	$managed_types = get_posts(
		array(
			'post_type'        => CPT_GENERATOR_POST_TYPE,
			'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page'   => -1,
			'orderby'          => 'ID',
			'order'            => 'ASC',
			'suppress_filters' => true,
		)
	);

	foreach ( $managed_types as $managed_type ) {
		$settings = get_post_meta( $managed_type->ID, CPT_GENERATOR_META_KEY, true );
		if ( ! is_array( $settings ) || empty( $settings['slug'] ) || empty( $settings['plural_name'] ) ) {
			continue;
		}

		$slug = sanitize_key( $settings['slug'] );
		if ( ! cpt_generator_valid_slug( $slug ) || post_type_exists( $slug ) ) {
			continue;
		}

		$registered_type = register_post_type( $slug, cpt_generator_get_args( $managed_type->post_title, $settings ) );
		if ( ! is_wp_error( $registered_type ) ) {
			$GLOBALS['cpt_generator_registered_types'][] = $slug;
		}
	}

	if ( get_option( CPT_GENERATOR_FLUSH_OPTION ) ) {
		flush_rewrite_rules( false );
		delete_option( CPT_GENERATOR_FLUSH_OPTION );
	}
}

/**
 * Return the standard registration arguments from saved settings.
 *
 * @param string $singular_name Singular post type label.
 * @param array  $settings      Saved post type settings.
 * @return array
 */
function cpt_generator_get_args( $singular_name, $settings ) {
	$plural_name = sanitize_text_field( $settings['plural_name'] );
	$labels      = array(
		'name'                  => $plural_name,
		'singular_name'         => $singular_name,
		'menu_name'             => $plural_name,
		'name_admin_bar'        => $singular_name,
		'archives'              => sprintf( __( '%s Archives', 'cpt-generator' ), $singular_name ),
		'attributes'            => sprintf( __( '%s Attributes', 'cpt-generator' ), $singular_name ),
		'parent_item_colon'     => sprintf( __( 'Parent %s:', 'cpt-generator' ), $singular_name ),
		'all_items'             => sprintf( __( 'All %s', 'cpt-generator' ), $plural_name ),
		'add_new_item'          => sprintf( __( 'Add New %s', 'cpt-generator' ), $singular_name ),
		'add_new'               => __( 'Add New', 'cpt-generator' ),
		'new_item'              => sprintf( __( 'New %s', 'cpt-generator' ), $singular_name ),
		'edit_item'             => sprintf( __( 'Edit %s', 'cpt-generator' ), $singular_name ),
		'update_item'           => sprintf( __( 'Update %s', 'cpt-generator' ), $singular_name ),
		'view_item'             => sprintf( __( 'View %s', 'cpt-generator' ), $singular_name ),
		'view_items'            => sprintf( __( 'View %s', 'cpt-generator' ), $plural_name ),
		'search_items'          => sprintf( __( 'Search %s', 'cpt-generator' ), $plural_name ),
		'not_found'             => __( 'Not found', 'cpt-generator' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'cpt-generator' ),
		'featured_image'        => sprintf( __( '%s Featured Image', 'cpt-generator' ), $singular_name ),
		'set_featured_image'    => __( 'Set featured image', 'cpt-generator' ),
		'remove_featured_image' => __( 'Remove featured image', 'cpt-generator' ),
		'use_featured_image'    => __( 'Use as featured image', 'cpt-generator' ),
		'insert_into_item'      => sprintf( __( 'Insert into %s', 'cpt-generator' ), strtolower( $singular_name ) ),
		'uploaded_to_this_item' => sprintf( __( 'Uploaded to this %s', 'cpt-generator' ), strtolower( $singular_name ) ),
		'items_list'            => sprintf( __( '%s list', 'cpt-generator' ), $plural_name ),
		'items_list_navigation' => sprintf( __( '%s list navigation', 'cpt-generator' ), $plural_name ),
		'filter_items_list'     => sprintf( __( 'Filter %s list', 'cpt-generator' ), strtolower( $plural_name ) ),
	);

	$support_options = array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments', 'revisions', 'custom-fields', 'page-attributes' );
	$supports        = isset( $settings['supports'] ) && is_array( $settings['supports'] )
		? array_values( array_intersect( $support_options, $settings['supports'] ) )
		: array( 'title', 'editor' );
	$taxonomies      = isset( $settings['taxonomies'] ) && is_array( $settings['taxonomies'] )
		? array_values( array_intersect( array( 'category', 'post_tag' ), $settings['taxonomies'] ) )
		: array();
	$public          = ! empty( $settings['public'] );

	return array(
		'labels'              => $labels,
		'label'               => $singular_name,
		'description'         => isset( $settings['description'] ) ? sanitize_text_field( $settings['description'] ) : '',
		'public'              => $public,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => $public,
		'publicly_queryable'  => $public,
		'exclude_from_search' => ! empty( $settings['exclude_from_search'] ),
		'hierarchical'        => ! empty( $settings['hierarchical'] ),
		'has_archive'         => ! empty( $settings['has_archive'] ),
		'show_in_rest'        => ! empty( $settings['show_in_rest'] ),
		'supports'            => $supports,
		'taxonomies'          => $taxonomies,
		'capability_type'     => isset( $settings['capability_type'] ) && 'page' === $settings['capability_type'] ? 'page' : 'post',
	);
}

/**
 * Check post type keys against WordPress's key format and length.
 *
 * @param string $slug Post type key.
 * @return bool
 */
function cpt_generator_valid_slug( $slug ) {
	return is_string( $slug ) && 1 === preg_match( '/^[a-z0-9_-]{1,20}$/', $slug );
}

/**
 * Add the custom post type settings editor.
 */
function cpt_generator_add_meta_box() {
	add_meta_box(
		'cpt-generator-settings',
		__( 'Post Type Settings', 'cpt-generator' ),
		'cpt_generator_render_meta_box',
		CPT_GENERATOR_POST_TYPE,
		'normal',
		'high'
	);
}

/**
 * Render settings fields and the generated PHP preview.
 *
 * @param WP_Post $post Current management record.
 */
function cpt_generator_render_meta_box( $post ) {
	$settings = get_post_meta( $post->ID, CPT_GENERATOR_META_KEY, true );
	$settings = is_array( $settings ) ? $settings : array();
	$slug     = isset( $settings['slug'] ) ? $settings['slug'] : '';
	$plural   = isset( $settings['plural_name'] ) ? $settings['plural_name'] : '';
	$supports = isset( $settings['supports'] ) && is_array( $settings['supports'] ) ? $settings['supports'] : array( 'title', 'editor' );
	$taxonomies = isset( $settings['taxonomies'] ) && is_array( $settings['taxonomies'] ) ? $settings['taxonomies'] : array();

	wp_nonce_field( 'cpt_generator_save_settings', 'cpt_generator_nonce' );
	?>
	<p>
		<label for="cpt-generator-slug"><strong><?php esc_html_e( 'Post type key (slug)', 'cpt-generator' ); ?></strong></label><br>
		<input id="cpt-generator-slug" name="cpt_generator_settings[slug]" type="text" class="regular-text" maxlength="20" value="<?php echo esc_attr( $slug ); ?>" required>
		<span class="description"><?php esc_html_e( 'Lowercase letters, numbers, underscores, and hyphens; 20 characters maximum.', 'cpt-generator' ); ?></span>
	</p>
	<p>
		<label for="cpt-generator-plural"><strong><?php esc_html_e( 'Plural label', 'cpt-generator' ); ?></strong></label><br>
		<input id="cpt-generator-plural" name="cpt_generator_settings[plural_name]" type="text" class="regular-text" value="<?php echo esc_attr( $plural ); ?>" required>
	</p>
	<p>
		<label for="cpt-generator-description"><strong><?php esc_html_e( 'Description', 'cpt-generator' ); ?></strong></label><br>
		<textarea id="cpt-generator-description" name="cpt_generator_settings[description]" class="large-text" rows="2"><?php echo esc_textarea( isset( $settings['description'] ) ? $settings['description'] : '' ); ?></textarea>
	</p>
	<p><strong><?php esc_html_e( 'Supports', 'cpt-generator' ); ?></strong><br>
		<?php
		$support_labels = array(
			'title'          => __( 'Title', 'cpt-generator' ),
			'editor'         => __( 'Editor', 'cpt-generator' ),
			'thumbnail'      => __( 'Featured image', 'cpt-generator' ),
			'excerpt'        => __( 'Excerpt', 'cpt-generator' ),
			'comments'       => __( 'Comments', 'cpt-generator' ),
			'revisions'      => __( 'Revisions', 'cpt-generator' ),
			'custom-fields'  => __( 'Custom fields', 'cpt-generator' ),
			'page-attributes' => __( 'Page attributes', 'cpt-generator' ),
		);
		foreach ( $support_labels as $support => $label ) :
			?>
			<label><input type="checkbox" name="cpt_generator_settings[supports][]" value="<?php echo esc_attr( $support ); ?>" <?php checked( in_array( $support, $supports, true ) ); ?>> <?php echo esc_html( $label ); ?></label>&nbsp;&nbsp;
		<?php endforeach; ?>
	</p>
	<p><strong><?php esc_html_e( 'Taxonomies', 'cpt-generator' ); ?></strong><br>
		<label><input type="checkbox" name="cpt_generator_settings[taxonomies][]" value="category" <?php checked( in_array( 'category', $taxonomies, true ) ); ?>> <?php esc_html_e( 'Categories', 'cpt-generator' ); ?></label>&nbsp;&nbsp;
		<label><input type="checkbox" name="cpt_generator_settings[taxonomies][]" value="post_tag" <?php checked( in_array( 'post_tag', $taxonomies, true ) ); ?>> <?php esc_html_e( 'Tags', 'cpt-generator' ); ?></label>
	</p>
	<p><strong><?php esc_html_e( 'Options', 'cpt-generator' ); ?></strong><br>
		<?php
		$checkbox_options = array(
			'public'              => __( 'Publicly visible', 'cpt-generator' ),
			'hierarchical'        => __( 'Hierarchical (like pages)', 'cpt-generator' ),
			'has_archive'         => __( 'Has an archive', 'cpt-generator' ),
			'exclude_from_search' => __( 'Exclude from search results', 'cpt-generator' ),
			'show_in_rest'        => __( 'Enable the REST API (including block editor support)', 'cpt-generator' ),
		);
		foreach ( $checkbox_options as $option => $label ) :
			?>
			<label><input type="checkbox" name="cpt_generator_settings[<?php echo esc_attr( $option ); ?>]" value="1" <?php checked( 'public' === $option && ! isset( $settings[ $option ] ) ? true : ! empty( $settings[ $option ] ) ); ?>> <?php echo esc_html( $label ); ?></label><br>
		<?php endforeach; ?>
	</p>
	<p>
		<label for="cpt-generator-capability"><strong><?php esc_html_e( 'Capability type', 'cpt-generator' ); ?></strong></label><br>
		<select id="cpt-generator-capability" name="cpt_generator_settings[capability_type]">
			<option value="post" <?php selected( empty( $settings['capability_type'] ) || 'post' === $settings['capability_type'] ); ?>><?php esc_html_e( 'Posts', 'cpt-generator' ); ?></option>
			<option value="page" <?php selected( isset( $settings['capability_type'] ) && 'page' === $settings['capability_type'] ); ?>><?php esc_html_e( 'Pages', 'cpt-generator' ); ?></option>
		</select>
	</p>
	<h3><?php esc_html_e( 'Generated PHP', 'cpt-generator' ); ?></h3>
	<p><?php esc_html_e( 'This preview reflects the saved settings. Save the custom post type to refresh it.', 'cpt-generator' ); ?></p>
	<textarea class="large-text code" rows="18" readonly><?php echo esc_textarea( cpt_generator_generate_code( $post->post_title, $settings ) ); ?></textarea>
	<?php
}

/**
 * Save the settings after validating the submitted slug and fields.
 *
 * @param int $post_id Management record ID.
 */
function cpt_generator_save_settings( $post_id ) {
	if ( ! isset( $_POST['cpt_generator_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cpt_generator_nonce'] ) ), 'cpt_generator_save_settings' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['cpt_generator_settings'] ) || ! is_array( $_POST['cpt_generator_settings'] ) ) {
		return;
	}

	$input = wp_unslash( $_POST['cpt_generator_settings'] );
	$slug  = isset( $input['slug'] ) && is_string( $input['slug'] ) ? sanitize_key( $input['slug'] ) : '';
	$built_in_types = get_post_types( array( '_builtin' => true ), 'names' );
	$current_settings = get_post_meta( $post_id, CPT_GENERATOR_META_KEY, true );
	$is_current_slug  = is_array( $current_settings ) && isset( $current_settings['slug'] ) && $slug === $current_settings['slug'];
	if ( ! cpt_generator_valid_slug( $slug ) || in_array( $slug, $built_in_types, true ) || CPT_GENERATOR_POST_TYPE === $slug || ( post_type_exists( $slug ) && ! $is_current_slug ) ) {
		set_transient( 'cpt_generator_error_' . get_current_user_id(), __( 'The post type key must be 1 to 20 characters, use only lowercase letters, numbers, underscores, or hyphens, and not conflict with a WordPress post type.', 'cpt-generator' ), MINUTE_IN_SECONDS );
		return;
	}

	$support_options = array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments', 'revisions', 'custom-fields', 'page-attributes' );
	$settings        = array(
		'slug'                => $slug,
		'plural_name'         => isset( $input['plural_name'] ) && is_string( $input['plural_name'] ) ? sanitize_text_field( $input['plural_name'] ) : '',
		'description'         => isset( $input['description'] ) && is_string( $input['description'] ) ? sanitize_textarea_field( $input['description'] ) : '',
		'supports'            => isset( $input['supports'] ) && is_array( $input['supports'] ) ? array_values( array_intersect( $support_options, array_map( 'sanitize_key', array_filter( $input['supports'], 'is_string' ) ) ) ) : array(),
		'taxonomies'          => isset( $input['taxonomies'] ) && is_array( $input['taxonomies'] ) ? array_values( array_intersect( array( 'category', 'post_tag' ), array_map( 'sanitize_key', array_filter( $input['taxonomies'], 'is_string' ) ) ) ) : array(),
		'public'              => ! empty( $input['public'] ),
		'hierarchical'        => ! empty( $input['hierarchical'] ),
		'has_archive'         => ! empty( $input['has_archive'] ),
		'exclude_from_search' => ! empty( $input['exclude_from_search'] ),
		'show_in_rest'        => ! empty( $input['show_in_rest'] ),
		'capability_type'     => isset( $input['capability_type'] ) && 'page' === $input['capability_type'] ? 'page' : 'post',
	);

	if ( '' === $settings['plural_name'] || '' === trim( $post_id ? get_post_field( 'post_title', $post_id ) : '' ) ) {
		set_transient( 'cpt_generator_error_' . get_current_user_id(), __( 'Enter both a singular name in the title field and a plural label.', 'cpt-generator' ), MINUTE_IN_SECONDS );
		return;
	}

	$existing = get_posts(
		array(
			'post_type'      => CPT_GENERATOR_POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'exclude'        => array( $post_id ),
		)
	);
	foreach ( $existing as $record ) {
		$existing_settings = get_post_meta( $record->ID, CPT_GENERATOR_META_KEY, true );
		if ( is_array( $existing_settings ) && isset( $existing_settings['slug'] ) && $slug === $existing_settings['slug'] ) {
			set_transient( 'cpt_generator_error_' . get_current_user_id(), __( 'Another managed custom post type already uses that key.', 'cpt-generator' ), MINUTE_IN_SECONDS );
			return;
		}
	}

	update_post_meta( $post_id, CPT_GENERATOR_META_KEY, $settings );
	update_option( CPT_GENERATOR_FLUSH_OPTION, 1 );
}

/**
 * Generate a copyable PHP registration snippet for the saved settings.
 *
 * @param string $singular_name Singular post type label.
 * @param array  $settings      Saved post type settings.
 * @return string
 */
function cpt_generator_generate_code( $singular_name, $settings ) {
	if ( empty( $settings['slug'] ) || empty( $settings['plural_name'] ) ) {
		return __( 'Save a post type key and plural label to generate the PHP code.', 'cpt-generator' );
	}

	$args = cpt_generator_get_args( $singular_name, $settings );
	return "<?php\nadd_action( 'init', function () {\n    register_post_type( "
		. var_export( sanitize_key( $settings['slug'] ), true ) . ", "
		. var_export( $args, true ) . " );\n} );";
}

/**
 * Flag rewrite rules for refreshing after managed records are trashed or restored.
 *
 * @param string  $new_status New post status.
 * @param string  $old_status Previous post status.
 * @param WP_Post $post       Changed post.
 */
function cpt_generator_status_changed( $new_status, $old_status, $post ) {
	if ( CPT_GENERATOR_POST_TYPE === $post->post_type && $new_status !== $old_status ) {
		update_option( CPT_GENERATOR_FLUSH_OPTION, 1 );
	}
}

/**
 * Add a slug column to the management list.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function cpt_generator_admin_columns( $columns ) {
	$columns['cpt_generator_slug']   = __( 'Post Type Key', 'cpt-generator' );
	$columns['cpt_generator_plural'] = __( 'Plural Label', 'cpt-generator' );
	return $columns;
}

/**
 * Render custom management list columns.
 *
 * @param string $column  Column name.
 * @param int    $post_id Management record ID.
 */
function cpt_generator_admin_column( $column, $post_id ) {
	$settings = get_post_meta( $post_id, CPT_GENERATOR_META_KEY, true );
	if ( ! is_array( $settings ) ) {
		return;
	}

	if ( 'cpt_generator_slug' === $column && isset( $settings['slug'] ) ) {
		echo '<code>' . esc_html( $settings['slug'] ) . '</code>';
	} elseif ( 'cpt_generator_plural' === $column && isset( $settings['plural_name'] ) ) {
		echo esc_html( $settings['plural_name'] );
	}
}

/**
 * Show validation errors after returning to the management screen.
 */
function cpt_generator_admin_notices() {
	$key   = 'cpt_generator_error_' . get_current_user_id();
	$error = get_transient( $key );
	if ( $error ) {
		delete_transient( $key );
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error ) . '</p></div>';
	}
}

/**
 * Schedule the initial rewrite refresh.
 */
function cpt_generator_activate() {
	update_option( CPT_GENERATOR_FLUSH_OPTION, 1 );
}

/**
 * Flush rewrite rules when the plugin is deactivated.
 */
function cpt_generator_deactivate() {
	foreach ( $GLOBALS['cpt_generator_registered_types'] as $post_type ) {
		unregister_post_type( $post_type );
	}
	unregister_post_type( CPT_GENERATOR_POST_TYPE );
	flush_rewrite_rules( false );
}
