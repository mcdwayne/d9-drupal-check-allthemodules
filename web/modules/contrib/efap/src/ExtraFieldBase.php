<?php

namespace Drupal\efap;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class ExtraFieldBase
 *
 * @package Drupal\efap
 */
abstract class ExtraFieldBase extends PluginBase implements ExtraFieldInterface {

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $viewMode) : array {
    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          Html::cleanCssIdentifier($this->pluginDefinition['id']),
          'extra-field',
        ],
      ],
    ];
  }

}
