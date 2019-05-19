<?php

namespace Drupal\toolshed;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for creating third party configuration settings form snippets.
 */
interface ThirdPartyFormElementsInterface {

  /**
   * Determine if a specific instance of a ConfigEntity applies to this plugin.
   *
   * The isApplicable() method allows for finer grained settings that may or
   * may not be relevant to a configuration entity type.
   *
   * @param Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The configuration entity to test if settings plugin settings apply.
   * @param string $op
   *   The name of the operation being performed on the entity.
   *
   * @return bool
   *   Returns TRUE if this third party form settings apply to the provided
   *   $entity parameter. FALSE if the settings do not.
   */
  public function isApplicable(ConfigEntityInterface $entity, $op);

  /**
   * Generate the form elements to implement the third party form settings.
   *
   * @param Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The configuration entity that we want to build the settings form for.
   * @param array $parents
   *   The array of parents for the parent form element. This provides the
   *   scope of where values are stored relative to entire form.
   * @param Drupal\Core\Form\FormStateInterface $state
   *   The current state of the form being built, that these form elements are
   *   included as part of.
   *
   * @return array
   *   Valid form API array for building form elements.
   */
  public function settingsForm(ConfigEntityInterface $entity, array $parents, FormStateInterface $state);

}
