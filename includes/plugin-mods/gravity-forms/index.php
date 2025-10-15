<?php

// Don't allow direct access to file
defined("ABSPATH") || die();

add_filter( 'gform_disable_css', '__return_true' );

/**
 * Populate Gravity Forms 'Select Form' ACF custom field
 */
add_filter("acf/load_field/name=gravity_form_id", function ($field) {
    global $wpdb;

    if (!function_exists("gravity_form")) {
        return $field;
    }

    $forms = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}gf_form");

    if (!$forms) {
        return $field;
    }

    $field["choices"] = [];

    foreach ($forms as $k => $v) {
        $field["choices"][$v->id] = apply_filters("the_title", $v->title);
    }

    return $field;
});

/**
 * Defer loading of Gravity Form Js
 */
add_filter("pumpkin_defer_scripts", function (array $scripts): array {
    return array_merge($scripts, [
        "gform_json",
        "gform_gravityforms",
        "gform_placeholder",
    ]);
});
