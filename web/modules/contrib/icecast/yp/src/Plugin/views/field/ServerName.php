<?php

namespace Drupal\yp\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\Core\Url;

/**
 * Render stream URL as a link.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("yp_server_name")
 */
class ServerName extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->additional_fields['url'] = 'url';
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    try {
      $url = Url::fromUri($this->getValue($values, 'url'));
      // Verify this is a valid URL.
      $url->toString();
    }
    catch (\Exception $e) {
      return $this->sanitizeValue($value);
    }
    return [
      '#type' => 'link',
      '#url' => $url,
      '#title' => $this->sanitizeValue($value),
    ];
  }

}
