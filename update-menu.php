<?php
require_once 'wp-load.php';

// Get the primary menu
$menu_name = 'Primary Menu';
$menu_exists = wp_get_nav_menu_object( $menu_name );

if ( ! $menu_exists ) {
    $menu_id = wp_create_nav_menu($menu_name);
    // Set theme location
    $locations = get_theme_mod('nav_menu_locations');
    $locations['primary'] = $menu_id;
    set_theme_mod( 'nav_menu_locations', $locations );
} else {
    $menu_id = $menu_exists->term_id;
    // Delete all existing items
    $items = wp_get_nav_menu_items( $menu_id );
    foreach ( $items as $item ) {
        wp_delete_post( $item->ID, true );
    }
}

// Add new items
$menu_items = [
    [ 'title' => 'Home', 'url' => home_url('/') ],
    [ 'title' => 'Courses', 'url' => home_url('/courses/') ],
    [ 'title' => 'About', 'url' => home_url('/about/') ],
    [ 'title' => 'Franchise', 'url' => home_url('/coaching/') ],
    [ 'title' => 'Counselling', 'url' => home_url('/university-counselling/') ], 
    [ 'title' => 'Admission', 'url' => home_url('/admission/') ],
    [ 'title' => 'Contact', 'url' => home_url('/contact/') ],
];

foreach ( $menu_items as $item ) {
    wp_update_nav_menu_item($menu_id, 0, array(
        'menu-item-title' => $item['title'],
        'menu-item-url' => $item['url'],
        'menu-item-status' => 'publish',
        'menu-item-type' => 'custom',
    ));
}

echo "Menu updated successfully.";
