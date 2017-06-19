<?php
/*
Plugin Name: Typeform response Plugin
Plugin URI:
Description: Connect a typeform response to wordpress
Version: 1.0.0
Author: Next-Generation
Author URI:
*/

// Resources provided by this plugin:
add_action('wp_enqueue_scripts', 'typeform_resources');
add_action('admin_menu', 'typeform_create_menu');
add_shortcode('typeform', 'typeform_fetch_data');

function typeform_tmpl($file, $data) {
    extract($data);
    include($file.".tmpl.php");
}

function typeform_fetch_data() {
    $api_key = get_option('api_key');
    $form_id = get_option('form_id');
    $uri = "https://api.typeform.com/v1/form/%s?key=%s";
    $rest = sprintf($uri, $form_id, $api_key);

    $content = @file_get_contents($rest);
    if ($content === false) {
        error_log("Failed to fetch form data from '$rest'");
        echo 'There was an error when fetching form data from typeform.com';
        return;
    }

    $response = json_decode($content, true);

    if ($response['http_status'] != '200') {
        error_log(json_encode($response['stats']));
        return;
    }

    $fields = json_decode(get_option('field_map'));
    $anon_filter = json_decode(get_option('anon_filter'));

    $view_data;
    foreach ($response['responses'] as $value) {
        $answers = $value['answers'];
        $tmpl_map = [];

        if (!isset($anon_filter))
            goto NO_ANON_FILTER;

        foreach ($anon_filter as $key=>$value) {
            if (!isset($answers[$key]))
                continue;

            if ($answers[$key] == $value)
                continue 2;
        }

        NO_ANON_FILTER:

        foreach ($fields as $key=>$value) {
            if (isset($answers[$key]))
                $tmpl_map[$value] = $answers[$key];
        }
        $view_data[] = typeform_parse_view_template($tmpl_map);
    }
    typeform_tmpl('view', ['responses'=>$view_data]);
}

function typeform_resources() {
    wp_enqueue_style('bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css');
}

function typeform_create_menu() {
    add_menu_page('Typeform settings',
        'Typeform settings',
        'administrator',
        'typeform-settings',
        'typeform_plugin_settings_page');

    add_action('admin_init', 'typeform_plugin_settings');
}

function typeform_plugin_settings() {
    $settings = typeform_get_settings();
    $group = $settings['group'];
    foreach ($settings['settings'] as $setting) {
        register_setting($group, $setting['option']);
    }
}

function typeform_parse_view_template($data) {
    $view = get_option('view_template');
    $map_func = function($value) {
        return sprintf("{{%s}}", $value);
    };
    $tokens = array_map($map_func, array_keys($data));
    $values = array_values($data);
    $cols = explode("|", $view);
    $columns;
    foreach ($cols as $col) {
        $columns[] = str_replace($tokens, $values, trim($col));
    }

    return $columns;
}

function typeform_get_settings() {
    return [
        'title' => 'Typeform plugin settings',
        'group' => 'typeform_settings_group',
        'settings' => [
            ['option'=>'api_key',
                'title' => 'API key',
                'description' => 'Your typeform account API key',
                'form' => 'text'],
            ['option'=>'form_id',
                'title' => 'Form ID',
                'description' => 'The unique form ID',
                'form' => 'text'],
            ['option'=>'anon_filter',
                'title' => 'Make entry anonymous',
                'description' => 'Json of field id\'s and value to ban',
                'form' => 'textarea'],
            ['option'=>'field_map',
                'title' => 'Field map',
                'description' => 'Json of fields and headlines',
                'form' => 'textarea'],
            ['option'=>'view_template',
                'title' => 'View template',
                'description' => 'Template of how to present the data',
                'form' => 'textarea'],
        ]
    ];
}

function typeform_plugin_settings_page() {
    $tmpl = typeform_get_settings();
    typeform_tmpl('admin', $tmpl);
}
