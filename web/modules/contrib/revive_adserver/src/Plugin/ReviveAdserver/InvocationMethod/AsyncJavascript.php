<?php

namespace Drupal\revive_adserver\Plugin\ReviveAdserver\InvocationMethod;

use Drupal\revive_adserver\Annotation\InvocationMethodService;
use Drupal\revive_adserver\InvocationMethodServiceBase;
use Drupal\revive_adserver\InvocationMethodServiceInterface;

/**
 * Provides the 'Async Javascript' invocation method service.
 *
 * @InvocationMethodService(
 *   id = "async_javascript",
 *   label = @Translation("Asynchronous JS Tag"),
 *   weight = 0,
 * )
 */
class AsyncJavascript extends InvocationMethodServiceBase implements InvocationMethodServiceInterface {

  /**
   * @inheritdoc
   */
  public function render() {
    $build['element'] = [
      '#type' => 'html_tag',
      '#tag' => 'ins',
      '#value' => '',
      '#attributes' => [
        'data-revive-zoneid' => $this->getZoneId(),
        'data-revive-id' => $this->getReviveId(),
      ],
    ];
    $build['script'] = [
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#value' => '',
      '#attributes' => [
        'src' => $this->getReviveDeliveryPath() . '/asyncjs.php',
        'async' => 'async',
      ],
    ];

    $build['#cache'] = ['tags' => ['config:revive_adserver.settings']];

    return $build;
  }

}
