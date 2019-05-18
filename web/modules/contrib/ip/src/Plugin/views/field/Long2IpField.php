<?php

/**
 * @file
 * Contains \Drupal\ip\Plugin\views\field\Long2IpField.
 */

namespace Drupal\ip\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Url;

/**
 * Render a long field as a ip value
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("long2ip")
 */
class Long2IpField extends FieldPluginBase {

  // @TODO option to link or not to address manage page
  function render(ResultRow $values) {

    $value = $this->getValue($values);
    $long2ip = !empty($value) ? long2ip($value) : NULL;

    // @TODO: fix path
    //$url = new Url('admin/people/ip', array('query' => array('ip' => array('value' => $long2ip))));
    //$external_link = \Drupal::l(t('External link'), $url);
    $external_link = $long2ip;

    // @TODO: link!
    return !empty($long2ip) ? $external_link : NULL;
  }
}
