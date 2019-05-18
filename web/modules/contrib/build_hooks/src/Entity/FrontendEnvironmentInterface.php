<?php

namespace Drupal\build_hooks\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Frontend environment entities.
 */
interface FrontendEnvironmentInterface extends ConfigEntityInterface {

  /**
   * {@inheritdoc}
   */
  public function getUrl();

  /**
   * {@inheritdoc}
   */
  public function getWeight();

  /**
   * {@inheritdoc}
   */
  public function getDeploymentStrategy();

  /**
   * {@inheritdoc}
   */
  public function setDeploymentStrategy($deploymentStrategy);

  /**
   * {@inheritdoc}
   */
  public function getPlugin();

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections();

}
