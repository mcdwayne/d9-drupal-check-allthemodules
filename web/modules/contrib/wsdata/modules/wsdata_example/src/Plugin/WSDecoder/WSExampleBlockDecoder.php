<?php

namespace Drupal\wsdata_example\Plugin\WSDecoder;

use Drupal\wsdata\Plugin\WSDecoderBase;
use Drupal\Component\Serialization\Json;

/**
 * JSON Decoder.
 *
 * @WSDecoder(
 *   id = "ExampleBlockDecoder",
 *   label = @Translation("Example block decoder", context = "WSDecoder"),
 * )
 */
class WSExampleBlockDecoder extends WSDecoderBase {
  /**
   * {@inheritdoc}
   */
  public function decode($data) {
    $items = [];
    if (!isset($data) || empty($data)) {
      return;
    }
    // Remove UTF-8 BOM if present, json_decode() does not like it.
    if (substr($data, 0, 3) == pack("CCC", 0xEF, 0xBB, 0xBF)) {
      $data = substr($data, 3);
    }
    $data = trim($data);
    $json_data = Json::decode($data);
    foreach ($json_data as $element) {
      $items[] = array(
        '#markup' => '<h2>' . $element['title'] . '</h2><p>' . $element['body'] . '</p>',
      );
    }
    $content = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#title' => 'My List',
      '#items' => $items,
      '#attributes' => ['class' => 'mylist'],
      '#wrapper_attributes' => ['class' => 'container'],
    ];
    return \Drupal::service('renderer')->render($content);
  }
  /**
   * {@inheritdoc}
   */
  public function accepts() {
    return ['text/json'];
  }
}
