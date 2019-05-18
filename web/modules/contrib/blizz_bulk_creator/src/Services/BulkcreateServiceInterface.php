<?php

namespace Drupal\blizz_bulk_creator\Services;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Interface BulkcreateServiceInterface.
 *
 * Defines the API for the Bulkcreate service.
 *
 * @package Drupal\blizz_bulk_creator\Services
 */
interface BulkcreateServiceInterface {

  /**
   * Injects the form widget for the bulkcreate interface.
   *
   * @param array $form
   *   The form definition of the form to modify.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormStateinterface object of that form.
   */
  public static function initializeBulkcreations(array $form, FormStateInterface $form_state);

  /**
   * Processes active bulkcreations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The host entity for the bulkcreate items.
   * @param array $bulkcreations
   *   An array containing the bulkcreations along with their data.
   * @param array $form
   *   The form definition of the form the bulkcreation is located in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormStateInterface of the form given.
   */
  public function process(EntityInterface $entity, array $bulkcreations, array $form, FormStateInterface $form_state);

}
