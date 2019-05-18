<?php
/**
 * Created by PhpStorm.
 * User: laboratory.mike
 */

namespace Drupal\kb\Plugin\views\area;

use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Utility\Html;

/**
 * Defines a views area plugin.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("kb_content_add_button_area")
 */

class KbContentAddButtonArea extends AreaPluginBase {
  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE)
  {
    if (isset($this->view->args[0])) {
      $arg = strval(intval(Html::escape($this->view->args[0])));
      $dest = \Drupal::service('path.current')->getPath();
      $params = ['group' => $arg];
      $params['plugin_id'] = 'group_node:kb_content';
      $params['destination'] = $dest;
      $url = new Url('entity.group_content.create_form', $params);
      $link = Link::fromTextAndUrl(t('Add KB Content'), $url);
      $link = $link->toRenderable();
      $link['#attributes'] = array('class' => array('btn', 'btn-success'));
      return array(
        '#markup' => render($link),
      );
    }
    else {
      return array(
        '#markup' => '',
      );
    }
  }
}