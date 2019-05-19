<?php

namespace Drupal\ads_system\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block plugin definitions for ads_system blocks.
 *
 * @see \Drupal\ads_system\Plugin\Block\AdDerivativesBlock
 */
class AdDerivativesBlock extends DeriverBase implements ContainerDeriverInterface {


  /**
   * The Ad Types defined.
   *
   * @var array
   *   Getting the Ad Types by Ad bundles.
   */
  protected $adTypes;

  /**
   * Creates a new AdTypeBlock.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $ad_types
   *   Definition EntityTypeBundleInfo of Ad entity.
   */
  public function __construct(EntityTypeBundleInfoInterface $ad_types) {
    $this->adTypes = $ad_types->getBundleInfo('ad');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
        $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Implements DeriverInterface::getDerivativeDefinitions().
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    // dsm($this->adTypes);.
    foreach ($this->adTypes as $block_id => $block_info) {
      $this->derivatives[$block_id] = $base_plugin_definition;
      $this->derivatives[$block_id]['admin_label'] = 'Ad block ' . $block_info['label'];
      // $this->derivatives[$block_id]['cache'] = DRUPAL_NO_CACHE;.
    }

    return $this->derivatives;
  }

}
