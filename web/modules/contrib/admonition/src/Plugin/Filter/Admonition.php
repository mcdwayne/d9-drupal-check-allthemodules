<?php
/**
 * Created by PhpStorm.
 * User: kieran
 * Date: 8/14/16
 * Time: 4:54 PM
 */

namespace Drupal\admonition\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;

/**
 * @Filter(
 *   id = "filter_admonition",
 *   title = @Translation("Admonition Filter"),
 *   description = @Translation("Show admonitions. Notes, warnings..."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class Admonition extends FilterBase {

  const DEFAULT_ADMONITION_TYPE = 'note';
  const DEFAULT_ADMONITION_ALIGNMENT = 'center';
  const DEFAULT_ADMONITION_WIDTH = 'half';

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {

//    $admon_service = \Drupal::service('admonition.changeadmonitionrepresentation');
//    return new FilterProcessResult($admon_service->storageToDisplay($text));

    $qp = html5qp($text);
    $admonitions = $qp->find('[data-chunk-type="admonition"]');
    foreach ($admonitions as $admonition) {
      //Should these be namespaced with the word admonition? Or just data-type?
      $type = $admonition->attr('data-admonition-type')
        ? $admonition->attr('data-admonition-type')
        : self::DEFAULT_ADMONITION_TYPE;
      $alignment = $admonition->attr('data-admonition-alignment')
        ? $admonition->attr('data-admonition-alignment')
        : self::DEFAULT_ADMONITION_ALIGNMENT;
      $width = $admonition->attr('data-admonition-width')
        ? $admonition->attr('data-admonition-width')
        : self::DEFAULT_ADMONITION_WIDTH;
      $content = $admonition->innerHTML5();
      $html =
          '<div class="admonition'
        .     ' admonition-' . $type
        .     ' admonition-' . $alignment
        .     ' admonition-' . $width . '">'
        .   '<img class="admonition-icon" '
        .     'src="/'
        .       drupal_get_path('module', 'admonition')
        .         '/js/plugins/admonition/icons/' . $type . '.png" '
        .     'alt="' . ucfirst($type) . '" '
        .     'title="' . ucfirst($type) . '" '
        .   '>'
        .   '<div class="admonition-content">' . $content . '</div>'
        . '</div>';
      $admonition->html5($html);
    }
    return new FilterProcessResult($qp->html5());
  }
}