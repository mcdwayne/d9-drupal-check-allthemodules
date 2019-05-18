<?php

/**
 * @file
 * Contains \Drupal\redirect_deleted_entities\Plugin\redirect_deleted_entities\RedirectType\ForumRedirectType.
 */

namespace Drupal\redirect_deleted_entities\Plugin\redirect_deleted_entities\RedirectType;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * A redirect type plugin for forum terms.
 *
 * @RedirectType(
 *   id = "forum",
 *   label = @Translation("Forum"),
 *   types = {"term"},
 *   provider = "forum",
 * )
 */
class ForumRedirectType extends EntityRedirectTypeBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getPatternDescription() {
    return $this->t('Pattern for forums and forum containers');
  }

  /**
   * {@inheritdoc}
   */
  public function getPatterns() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('default' => array('/[term:vocabulary]')) + parent::defaultConfiguration();
  }

}
