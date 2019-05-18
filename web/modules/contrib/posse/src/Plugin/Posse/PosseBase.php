<?php

namespace Drupal\posse\Plugin\Posse;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\posse\PosseInterface;

/**
* Base class for the Posse plugin functionality.
*
*/
class PosseBase implements PosseInterface {

  /**
  * {@inheritdoc}
  */
  public function configurationForm(&$form, FormStateInterface $form_state) { }

  /**
  * {@inheritdoc}
  */
  public function validateConfiguration($form, FormStateInterface &$form_state) {}

  /**
  * {@inheritdoc}
  */
  public function submitConfiguration($form, FormStateInterface &$form_state) { }

  /**
  * {@inheritdoc}
  */
  public function syndicate(ContentEntityBase $entity, $insert = TRUE) { }

  /**
  * {@inheritdoc}
  */
  public function syndicateForm($form, FormStateInterface $form_state) { }

  /**
  * {@inheritdoc}
  */
  public function syndicateFormValidate($form, FormStateInterface &$form_state) {}

  /**
  * {@inheritdoc}
  */
  public function syndicateFormSubmit($form, FormStateInterface &$form_state) { }

  /**
  * {@inheritdoc}
  */
  public function aggregateComments(ContentEntityBase $entity) { }



}
