<?php
/*
Plugin Name: Typeform response Plugin
Plugin URI:
Description: Connect a typeform response to wordpress
Version: 1.0.0
Author: Next-Generation
Author URI:
*/

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

    $table_td = "<td>%s</td>";
    $table_th = "<th>%s</th>";

    echo "<table>";
    foreach ($fields as $key=>$value) {
        echo sprintf($table_th, $value);
    }
    foreach ($response['responses'] as $value) {
        $answers = $value['answers'];
        echo "<tr>";
        foreach ($fields as $key=>$value) {
            if (isset($answers[$key]))
                echo sprintf($table_td, $answers[$key]);
        }
        echo "</tr>";
    }
    echo "</table>";
}

add_shortcode('typeform', 'typeform_fetch_data');

add_action('admin_menu', 'typeform_create_menu');

function typeform_create_menu() {
    add_menu_page('Typeform settings',
        'Typeform settings',
        'administrator',
        'typeform-settings',
        'typeform_plugin_settings_page');

    add_action('admin_init', 'typeform_plugin_settings');
}

function typeform_plugin_settings() {
    register_setting('typeform_settings_group', 'api_key');
    register_setting('typeform_settings_group', 'form_id');
    register_setting('typeform_settings_group', 'field_map');
}

function typeform_plugin_settings_page() {
?>
<div class="wrap">
<h1>Typeform plugin settings</h1>

<form method="post" action="options.php">
    <?php settings_fields('typeform_settings_group'); ?>
    <?php do_settings_sections('typeform_settings_group'); ?>

    <table class="form-table">

        <tr valign="top">
            <th scope="row">API key</th>
            <td>
                <input type="text" name="api_key"
                    value="<?php echo esc_attr(get_option('api_key')); ?>" />
                <i>Your typeform accounts API key</i>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">Form ID</th>
            <td>
                <input type="text" name="form_id"
                    value="<?php echo esc_attr(get_option('form_id')); ?>" />
                <i>The unique form ID</i>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row">Field map</th>
            <td>
                <textarea name="field_map"><?php echo
                esc_attr(get_option('field_map'));
                ?></textarea>
                <i>Json of fields and headlines</i>
            </td>
        </tr>

    </table>

    <?php submit_button(); ?>
</form>
</div>
<?php
}
