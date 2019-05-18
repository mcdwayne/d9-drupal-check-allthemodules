<?php

namespace Drupal\posse;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Form\FormStateInterface;

interface PosseInterface {

  /**
  * Returns the configuration form for this plugin.
  */
  public function configurationForm(&$form, FormStateInterface $form_state);

  /**
  * Validates the configurationForm for this plugin.
  */
  public function validateConfiguration($form, FormStateInterface &$form_state);

  /**
  * Handles the submit for this plugin.
  */
  public function submitConfiguration($form, FormStateInterface &$form_state);

  /**
  * The syndicate function will signal to the
  * Plugin that it should post the content of the entity
  * to the source is meant to interact with.
  */
  public function syndicate(ContentEntityBase $entity, $insert = FALSE);

  /**
  * The syndicateForm will allow plugins to provide addtional
  * configuration options on the ContentEntityBase's form
  *
  * Returns the plugin's configuration form for Entity displays.
  *
  * @return array
  */
  public function syndicateForm($form, FormStateInterface $form_state);

  /**
  * Performs validation on the syndicateForm.
  */
  public function syndicateFormValidate($form, FormStateInterface &$form_state);

  /**
  * Performs submit handling on the syndicateForm.
  */
  public function syndicateFormSubmit($form, FormStateInterface &$form_state);

  /**
  * The aggregate function will signal to the
  * plugin to pull comments from the source in response to content published to it.
  */
  public function aggregateComments(ContentEntityBase $entity);

}
