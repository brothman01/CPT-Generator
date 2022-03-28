<?php
/**
 * @package CPT_Generator
 * @version 1.0.0
 */
/*
Plugin Name: CPT Generator
Plugin URI: 
Description: A simple plugin to create Custom Post Types in a way that can easily be brought over to another site.
Author: Ben Rothman
Version: 1.7.2
Author URI: https://benrothman.org
*/

require_once('cpts.php');

function cpt_generator_main() {
    // create a CPTs CPT with fields to fill out for each part of the CPT array

    // when a new CPT is saved or updated, save or update that entry in the CPT file
}

add_action( 'plugins_loaded', 'cpt_generator_main' );


// // Register Custom Post Type Template
// function test_type() {

// 	$labels = array(
// 		'name'                  => _x( 'Test Types', 'Post Type General Name', 'text_domain' ),
// 		'singular_name'         => _x( 'Test Type', 'Post Type Singular Name', 'text_domain' ),
// 		'menu_name'             => __( 'Test Types', 'text_domain' ),
// 		'name_admin_bar'        => __( 'Test Type', 'text_domain' ),
// 		'archives'              => __( 'Test Type Archives', 'text_domain' ),
// 		'attributes'            => __( 'Test Type Attributes', 'text_domain' ),
// 		'parent_item_colon'     => __( 'Parent Test Type:', 'text_domain' ),
// 		'all_items'             => __( 'All Test Types', 'text_domain' ),
// 		'add_new_item'          => __( 'Add New Test Type', 'text_domain' ),
// 		'add_new'               => __( 'Add New', 'text_domain' ),
// 		'new_item'              => __( 'New Test Type', 'text_domain' ),
// 		'edit_item'             => __( 'Edit Test Type', 'text_domain' ),
// 		'update_item'           => __( 'Update Test Type', 'text_domain' ),
// 		'view_item'             => __( 'View Test Type', 'text_domain' ),
// 		'view_items'            => __( 'View Test Types', 'text_domain' ),
// 		'search_items'          => __( 'Search Test Type', 'text_domain' ),
// 		'not_found'             => __( 'Not found', 'text_domain' ),
// 		'not_found_in_trash'    => __( 'Not found in Trash', 'text_domain' ),
// 		'featured_image'        => __( 'Featured Image', 'text_domain' ),
// 		'set_featured_image'    => __( 'Set featured image', 'text_domain' ),
// 		'remove_featured_image' => __( 'Remove featured image', 'text_domain' ),
// 		'use_featured_image'    => __( 'Use as featured image', 'text_domain' ),
// 		'insert_into_item'      => __( 'Insert into item', 'text_domain' ),
// 		'uploaded_to_this_item' => __( 'Uploaded to this item', 'text_domain' ),
// 		'items_list'            => __( 'Items list', 'text_domain' ),
// 		'items_list_navigation' => __( 'Items list navigation', 'text_domain' ),
// 		'filter_items_list'     => __( 'Filter items list', 'text_domain' ),
// 	);
// 	$args = array(
// 		'label'                 => __( 'Test Type', 'text_domain' ),
// 		'description'           => __( 'test Type Description', 'text_domain' ),
// 		'labels'                => $labels,
// 		'supports'              => array( 'title', 'editor' ),
// 		'taxonomies'            => array( 'category', 'post_tag' ),
// 		'hierarchical'          => false,
// 		'public'                => true,
// 		'show_ui'               => true,
// 		'show_in_menu'          => true,
// 		'menu_position'         => 5,
// 		'show_in_admin_bar'     => true,
// 		'show_in_nav_menus'     => true,
// 		'can_export'            => true,
// 		'has_archive'           => true,
// 		'exclude_from_search'   => false,
// 		'publicly_queryable'    => true,
// 		'capability_type'       => 'page',
// 	);
// 	register_post_type( 'test_type', $args );
//
// }
// add_action( 'init', 'test_type', 0 );