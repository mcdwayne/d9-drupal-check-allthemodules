<?php

/**
 * @file
 * Contains \Drupal\redirect_deleted_entities\Plugin\redirect_deleted_entities\RedirectType\UserRedirectType.
 */

namespace Drupal\redirect_deleted_entities\Plugin\redirect_deleted_entities\RedirectType;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * A redirect type plugin for user entities.
 *
 * @RedirectType(
 *   id = "user",
 *   label = @Translation("User"),
 *   types = {"user"},
 *   provider = "user",
 * )
 */
class UserRedirectType extends EntityRedirectTypeBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getPatternDescription() {
    return $this->t('Pattern for user account page redirects');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('default' => array('/')) + parent::defaultConfiguration();
  }

}
