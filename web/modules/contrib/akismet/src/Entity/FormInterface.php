<?php

namespace Drupal\akismet\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for index entities.
 */
interface FormInterface extends ConfigEntityInterface {



  /**
   * Form protection mode: No protection.
   */
  const AKISMET_MODE_DISABLED = 0;

  /**
   * Form protection mode: Text analysis.
   */
  const AKISMET_MODE_ANALYSIS = 2;

  /**
   * Server communication failure fallback mode: Block all submissions of protected forms.
   */
  const AKISMET_FALLBACK_BLOCK = 0;

  /**
   * Server communication failure fallback mode: Accept all submissions of protected forms.
   */
  const AKISMET_FALLBACK_ACCEPT = 1;

  /**
   * Set defaults based on a form definition if the entity is new, otherwise
   * gets the form information for the existing form configuration.
   *
   * This somewhat corresponds to akismet_form_new in previous versions.
   *
   * @param string $form_id
   *   The id of the form that will be protected.
   * @return array
   *   An array of default protected form information.
   */
  public function initialize($form_id = NULL);

  /**
   * An array of fields to be checked within the form.
   * @return array
   */
  public function getEnabledFields();

  /**
   * Sets the array of fields that can be checked within the form as body.
   * @param array $fields
   * @return \Drupal\akismet\Entity\FormInterface
   */
  public function setEnabledFields(array $fields);

  /**
   * What to do when Akismet returns an unsure for content submitted through this
   * form.
   * @return string
   */
  public function getUnsure();

  /**
   * Sets how the module will handle content submitted through this form that
   * Akismet is unsure about.
   *
   * @param string $handling
   *   One of moderate, captcha, binary.
   * @return \Drupal\akismet\Entity\FormInterface
   */
  public function setUnsure($handling);

  /**
   * Indicates if the module should discard spam from this form or keep for
   * moderation.
   * @return boolean
   */
  public function getDiscard();

  /**
   * Sets whether the module should discard spam (TRUE) or keep for moderation
   * (FALSE).
   * @param boolean $discard
   * @return \Drupal\akismet\Entity\FormInterface
   */
  public function setDiscard($discard);

  /**
   * Gets the mapping of field values for Akismet submissions.
   * @return array
   */
  public function getMapping();

  /**
   * Sets the mapping of field values for Akismet submissions.
   * @param array $mapping
   * @return \Drupal\akismet\Entity\FormInterface
   */
  public function setMapping(array $mapping);

  /**
   * Gets the name of the module that owns the form being protected.
   * @return string
   */
  public function getModule();

  /**
   * Sets the name of the module that owns the forms being protected.
   * @param $module
   * @return \Drupal\akismet\Entity\FormInterface
   */
  public function setModule($module);

  /**
   * Get the entity id of the entity form being protected.
   * @return string
   */
  public function getEntity();

  /**
   * Set the entity id of the entity form being protected.
   * @param string $entity
   * @return \Drupal\akismet\Entity\FormInterface
   */
  public function setEntity($entity);

  /**
   * Get the bundle id of the entity bundle being protected.
   * @return string
   */
  public function getBundle();

  /**
   * Sets the entity bundle id of the entity bundle being protected.
   * @param string $bundle
   * @return \Drupal\akismet\Entity\FormInterface
   */
  public function setBundle($bundle);
}
