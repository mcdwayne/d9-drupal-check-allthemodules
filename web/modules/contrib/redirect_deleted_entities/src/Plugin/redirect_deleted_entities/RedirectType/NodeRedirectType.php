<?php

/**
 * @file
 * Contains \Drupal\redirect_deleted_entities\Plugin\redirect_deleted_entities\RedirectType\NodeRedirectType.
 */

namespace Drupal\redirect_deleted_entities\Plugin\redirect_deleted_entities\RedirectType;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * A redirect type plugin for content entities.
 *
 * @RedirectType(
 *   id = "node",
 *   label = @Translation("Content"),
 *   types = {"node"},
 *   provider = "node",
 * )
 */
class NodeRedirectType extends EntityRedirectTypeBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getPatternDescription() {
    return $this->t('Default redirect pattern (applies to all content types with blank patterns below)');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('default' => array('/node')) + parent::defaultConfiguration();
  }

}
