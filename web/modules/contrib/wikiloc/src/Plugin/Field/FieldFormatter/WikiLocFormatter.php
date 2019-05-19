<?php

namespace Drupal\wikiloc\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Drupal\Core\Language\LanguageInterface;

/**
 * Plugin implementation of the 'wikiloc_map_field' formatter.
 *
 * @FieldFormatter(
 *   id = "wikiloc_map_field_default",
 *   label = @Translation("Wikiloc Field default"),
 *   field_types = {
 *     "wikiloc_map_field"
 *   }
 * )
 */
class WikilocFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      if (!empty($item->id)) {
        // Try to use localized map.
        $language_interface = \Drupal::languageManager()->getCurrentLanguage();
        $wik_url = 'https://' . $language_interface->getId() . '.wikiloc.com/wikiloc';
        $wik_url_headers = @get_headers($wik_url);
        if (empty($wik_url_headers) or $wik_url_headers[0] == 'HTTP/1.1 404 Not Found') {
          // If no localized map available, default to english.
          $wik_url = 'https://www.wikiloc.com/wikiloc';
        }
        $query = array(
          'event' => 'view',
          'id' => $item->id,
          'measures' => ($item->measures == 1) ? 'on' : '',
          'near' => ($item->near == 1) ? 'on' : '',
          'images' => ($item->images == 1) ? 'on' : '',
          'metricunits' => ($item->metricunits == 1) ? 'on' : '',
          'maptype' => $item->maptype,
        );
        $url = Url::fromUri($wik_url . '/spatialArtifacts.do', array('query' => $query));

        $element = array(
          '#theme' => 'wikiloc_map_field',
          '#url' => $url->toString(),
          '#width' => Html::escape($item->width),
          '#height' => Html::escape($item->height),
        );
        $elements[$delta] = array('#markup' => drupal_render($element));
      }
    }
    return $elements;
  }

}
