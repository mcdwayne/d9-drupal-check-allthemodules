<?php

/**
 * @file
 * Contains \Drupal\offline_app\Plugin\DsField\OfflineNodeTitle.
 */

namespace Drupal\offline_app\Plugin\DsField;

use Drupal\Component\Utility\Html;
use Drupal\Core\Link;
use Drupal\ds\Plugin\DsField\Title;
use Drupal\offline_app\OfflineApp;

/**
 * Plugin that renders the title of a node for offline use.
 *
 * @DsField(
 *   id = "offline_node_title",
 *   title = @Translation("Offline title"),
 *   entity_type = "node",
 *   provider = "offline_app"
 * )
 */
class OfflineNodeTitle extends Title {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    // Initialize output
    $output = '';

    // Basic string.
    $entity_render_key = $this->entityRenderKey();

    if (isset($config['link text'])) {
      $output = t($config['link text']);
    }
    elseif (!empty($entity_render_key) && isset($this->entity()->{$entity_render_key})) {
      if ($this->getEntityTypeId() == 'user' && $entity_render_key == 'name') {
        $output = $this->entity()->getUsername();
      }
      else {
        $output = $this->entity()->{$entity_render_key}->value;
      }
    }

    if (empty($output)) {
      return array();
    }

    // Link.
    if (!empty($config['link'])) {
      /** @var $entity EntityInterface */
      $entity = $this->entity();

      if (OfflineApp::isOfflineRequest()) {
        $url_info = OfflineApp::getOfflineNodeAlias($entity->id(), FALSE);
      }
      else {
        $url_info = $entity->urlInfo();
      }

      if (!empty($config['link class'])) {
        $url_info->setOption('attributes', array('class' => explode(' ', $config['link class'])));
      }

      $link = Link::fromTextAndUrl($output, $url_info);
      $output = $link->toString();
    }
    else {
      $output = Html::escape($output);
    }

    // Wrapper and class.
    if (!empty($config['wrapper'])) {
      $wrapper = Html::escape($config['wrapper']);
      $class = (!empty($config['class'])) ? ' class="' . Html::escape($config['class']) . '"' : '';
      $output = '<' . $wrapper . $class . '>' . $output . '</' . $wrapper . '>';
    }

    return array(
      '#markup' => $output,
    );
  }

}
