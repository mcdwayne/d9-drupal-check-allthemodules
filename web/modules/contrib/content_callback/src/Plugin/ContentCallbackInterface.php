<?php

/**
 * @file
 * Contains \Drupal\content_callback\Plugin\ContentCallbackInterface.
 */

namespace Drupal\content_callback\Plugin;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for content callbacks
 */
interface ContentCallbackInterface {

  /**
   * Renders the content callback
   *
   * @param array $options
   *   An array of options for this callback
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity this callback is called from.
   *
   * @return array
   *   a render array
   */
  public function render(array $options, EntityInterface $entity = NULL);

  /**
   * Checks access for a content callback
   */
  public function access(\Drupal\Core\Session\AccountInterface $account);

  /**
   * Builds the render array for the content callback
   *
   * @return array
   *   A render array.
   */
  public function build();

  /**
   * Adds optional options for the content callback
   *
   * @return array
   *   Array of additional options.
   */
  public function optionsForm(array &$form, array $saved_options);

}
