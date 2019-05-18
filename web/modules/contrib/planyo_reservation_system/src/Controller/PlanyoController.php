<?php

namespace Drupal\planyo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\planyo\Common\PlanyoUtils;

class PlanyoController extends ControllerBase {
  public function content() {
    $content = PlanyoUtils::planyo_display_block_content();
    $content['#type'] = 'markup';
    $content['#title'] = PlanyoUtils::variable_get('planyo_page_title', $this->t('Reservation'));
    return $content;
  }
}

?>