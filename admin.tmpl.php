<div class="wrap">
    <h1><?= $title ?></h1>

    <form method="post" action="options.php">
        <?php settings_fields($group); ?>
        <?php do_settings_sections($group); ?>

        <table class="form-table">
            <?php foreach ($settings as $setting): extract($setting)?>
            <tr valign="top">
                <th scope="row"><?= $title ?></th>
                <td>
                    <?php if ($form == 'textarea'): ?>
                    <textarea name="<?= $option ?>"><?= esc_attr(get_option($option));?></textarea>
                    <?php else: ?>
                    <input type="<?= $form ?>" 
                        name="<?= $option ?>" 
                        value="<?= esc_attr(get_option($option));?>"/>
                    <?php endif ?>
                    <br>
                    <i><?= $description ?></i>
                </td>
            </tr>
            <?php endforeach ?>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
