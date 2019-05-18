<?php

namespace Drupal\drd\Crypt;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an interface for encryption.
 *
 * @ingroup drd
 */
interface BaseInterface {

  /**
   * Create instance of a crypt object of given method with provided settings.
   *
   * @param string $method
   *   ID of the crypt method.
   * @param array $settings
   *   Settings of the crypt instance.
   *
   * @return BaseMethodInterface
   *   The crypt object.
   */
  public static function getInstance($method, array $settings);

  /**
   * Get a list of crypt methods, either just their ids or instances of each.
   *
   * @var bool $instances
   *   Whether to receive ids (FALSE) or instances (TRUE).
   *
   * @return array
   *   List of crypt methods.
   */
  public static function getMethods($instances = FALSE);

  /**
   * Count the number of methods that both end support.
   *
   * @param array|null $remote
   *   List of supported remote crypt methods.
   *
   * @return int
   *   Number of common crapt methods.
   */
  public static function countAvailableMethods($remote = NULL);

  /**
   * A form API container with all the settings for DRD authentication.
   *
   * @param array $form
   *   The form into which the crypt form will be embedded.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return array
   *   Form element for the crypt form portion.
   */
  public static function cryptForm(array $form, FormStateInterface $form_state);

  /**
   * Callback to extract all crypt settings from the submitted form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return array
   *   Array of crypt settings.
   */
  public static function cryptFormValues(FormStateInterface $form_state);

}
