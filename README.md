# CPT Generator

A WordPress plugin for creating, editing, and deleting custom post types from the admin area.

## Install

1. Copy this repository into `wp-content/plugins/cpt-generator/`.
2. Activate **CPT Generator** in the WordPress Plugins screen.
3. Open **CPTs** in the admin menu and choose **Add New Custom Post Type**.

## Manage custom post types

Enter a singular name in the title field, then set the post type key, plural label, supports, taxonomies, and registration options. Publish or save the record to register the type; its content menu will then appear in the WordPress admin. The CPTs screen lists the managed types, where each can be edited, trashed, or permanently deleted. Changes are used for registration on the next request.

The editor includes a copyable PHP registration preview based on the saved settings. The plugin registers types directly from the saved settings; it does not evaluate generated PHP.

Deleting a managed type removes its registration from this plugin, but does not delete posts already stored under that type. Keep a backup before changing a post type key: content remains in the database under its original type key.
