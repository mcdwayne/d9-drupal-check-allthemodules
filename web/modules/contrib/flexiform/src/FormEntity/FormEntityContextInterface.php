<?php

namespace Drupal\flexiform\FormEntity;

use Drupal\Core\Plugin\Context\ContextInterface;

/**
 * Interface for form entity contexts.
 *
 * This extends the standard ContextInterface with two methods used to store
 * the entity namespace in a way that can be accessible.
 */
interface FormEntityContextInterface extends ContextInterface {

  /**
   * Set the entity namespace.
   *
   * @param string $namespace
   *   The entity namespace.
   */
  public function setEntityNamespace($namespace);

  /**
   * Get the entity namespace.
   *
   * @return string
   *   The entity namespace.
   */
  public function getEntityNamespace();

  /**
   * Get the form entity plugin.
   *
   * @return \Drupal\flexiform\FormEntity\FlexiformFormEntityInterface
   */
  public function getFormEntity();

}
