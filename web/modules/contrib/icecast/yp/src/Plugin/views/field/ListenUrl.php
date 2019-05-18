<?php

namespace Drupal\yp\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Render listen URL as a .M3U link.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("yp_listen_url")
 */
class ListenUrl extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $values->{$this->field_alias} . '.m3u';
    try {
      $url = Url::fromUri($value);
    }
    catch (\InvalidArgumentException $e) {
      return [];
    }
    $image = [
      '#theme' => 'image',
      '#uri' => drupal_get_path('module', 'yp') . '/listen.png',
      '#alt' => t('Listen'),
      '#title' => t('Listen'),
      '#attributes' => ['width' => 16, 'height' => 16],
    ];
    return [
      ['#type' => 'link', '#url' => $url, '#title' => $image],
      ['#type' => 'link', '#url' => $url, '#title' => t('Listen')],
    ];
  }

}
