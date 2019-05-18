<?php

namespace Drupal\revive_adserver\Plugin\ReviveAdserver\InvocationMethod;

use Drupal\Component\Utility\Crypt;
use Drupal\revive_adserver\Annotation\InvocationMethodService;
use Drupal\revive_adserver\InvocationMethodServiceBase;
use Drupal\revive_adserver\InvocationMethodServiceInterface;

/**
 * Provides the 'iFrame' invocation method service.
 *
 * @InvocationMethodService(
 *   id = "iframe",
 *   label = @Translation("iframe Tag"),
 *   weight = 5,
 * )
 */
class Iframe extends InvocationMethodServiceBase implements InvocationMethodServiceInterface {

  /**
   * @inheritdoc
   */
  public function render() {
    $build['element'] = [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'frameborder' => 0,
        'scrolling' => 'no',
        'src' => $this->getIframeSrc(),
        'id' => $this->getUniqueId(),
        'name' => $this->getUniqueId(),
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#attributes' => [
          'href' => $this->getLinkHref(),
          'target' => '_blank',
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'img',
          '#attributes' => [
            'href' => $this->getImageSrc(),
            'border' => 0,
            'alt' => '',
          ],
        ],
      ],
    ];

    // Add dimension attributes.
    $width = $this->getWidth();
    $height = $this->getHeight();

    // Revive stores omitted dimension values as -1. Provide attributes only,
    // if they are more than zero.
    if ($width > 0) {
      $build['element']['#attributes']['width'] = $width;
    }
    if ($height > 0) {
      $build['element']['#attributes']['height'] = $height;
    }

    // Disable the cache, because we render randomized data.
    $build['#cache'] = [
      'max-age' => 0,
    ];

    return $build;
  }

  /**
   * Returns the iFrame src url.
   *
   * @return string
   *   Iframe src url.
   */
  protected function getIframeSrc() {
    $randomNumber = Crypt::randomBytesBase64();
    $url = $this->getReviveDeliveryPath() . '/afr.php?zoneid=' . $this->getZoneId() . '&amp;cb=' . $randomNumber;
    return $url;
  }

}
