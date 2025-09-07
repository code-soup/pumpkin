<?php
/**
 * Website Logo Component Template
 *
 * @var array $args Component arguments
 * @var array $data Component data
 */

// Don't allow direct access to file
defined("ABSPATH") || die(); ?>

<a href="/">
    <?php echo $component->get_template_asset('logo.svg'); ?>
</a>