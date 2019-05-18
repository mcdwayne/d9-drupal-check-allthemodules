<?php

namespace Drupal\instagram_display\Controller;

use Drupal\instagram_display\Utility\DescriptionTemplateTrait;

/**
 * Controller routines for block example routes.
 */
class InstaBlockController {
    use DescriptionTemplateTrait;

    /**
    * {@inheritdoc}
    */
    protected function getModuleName() {
    return 'instagram_display';
    }


}
