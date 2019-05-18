<?php

namespace Drupal\jmol\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'jmol' formatter.
 *
 * @FieldFormatter(
 *   id = "jmol",
 *   label = @Translation("Jmol object"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class JmolFileFormatter extends JmolFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $uri = $file->getFileUri();
      $id = $file->uuid();
      $path = '/sites/default/files/' . substr($uri, 9);
      $alignment = $this->getSetting('alignment');
      $class = [];
      if ($alignment == 'left') $class[] = 'jmol-left';
      if ($alignment == 'right') $class[] = 'jmol-right';
      if ($alignment == 'center') $class[] = 'jmol-center';
      $info = $this->baseInfo();
      $info['src'] = $path;
      $elements = [
        '#attributes' => [
          'id' => $id,
          'class' => $class,
        ],
        '#type' => 'jmol',
        '#theme' => 'jmol_formatter_template',
        '#info' => $info,
      ];
    }

    return $elements;
  }

}
