<?php

namespace Drupal\blizz_bulk_creator\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class BulkcreateConfigurationFormStep2.
 *
 * Provides the form portion for the second step of
 * creating a new bulkcreation configuration.
 *
 * @package Drupal\blizz_bulk_creator\Form
 */
class BulkcreateConfigurationFormStep2 extends ConfigEntityMultistepFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getStepForm(array $form, FormStateInterface $form_state) {

    return [
      'defaultValueProviders' => [
        '#type' => 'checkboxes',
        '#title' => $this->t('Default value providers'),
        '#description' => $this->t('Please select the field you wish to pre-fill with default data when using this bulkcreation configuration.'),
        '#options' => array_filter(
          $this->entityHelper->getBundleFieldOptions('media', $this->store->get('bundle')),
          function ($machine_name) {
            return $machine_name != $this->store->get('bulkcreate_field');
          },
          ARRAY_FILTER_USE_KEY
        ),
      ],
    ];

  }

  /**
   * {@inheritdoc}
   */
  protected function submitStep(array $form, FormStateInterface $form_state) {

    /* @var \Drupal\blizz_bulk_creator\Entity\BulkcreateConfigurationInterface $entity */
    $entity = $this->entity;

    // Get the configured default value provider fields.
    $defaultValueProviders = array_keys(
      array_filter($form_state->getValue('defaultValueProviders'))
    );

    // Set the values on the configuration entity.
    $entity->set('label', $this->store->get('admin_title'));
    $entity->set('custom_entity_name', (bool) $this->store->get('custom_entity_name'));
    $entity->set('target_bundle', $this->store->get('bundle'));
    $entity->set('bulkcreate_field', $this->store->get('bulkcreate_field'));
    $entity->set('default_values', $defaultValueProviders);

    // Generate an ID for this entity.
    $entity->set('id', $this->uuidGenerator->generate());

    // Save the entity.
    $status = $entity->save();

    // Was the entity just created or updated?
    $action = $status == SAVED_UPDATED ? 'modified' : 'created';

    // Set a message on the frontend.
    drupal_set_message($this->t(
      'The Bulkcreate configuration "%label" has been %action.',
      [
        '%label' => $entity->label(),
        '%action' => $action,
      ]
    ));

    // Log a notice to watchdog (or whereever).
    $this->logger->notice(
      'The Bulkcreate configuration "%label" has been %action by user %user.',
      [
        '%label' => $entity->label(),
        '%action' => $action,
        '%user' => $this->currentUser()->getAccountName(),
      ]
    );

    // Redirect back to the list view.
    $form_state->setRedirect('blizz_bulk_creator.bulkcreate_configuration.list');

  }

  /**
   * {@inheritdoc}
   */
  protected function isFinalStep() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function hasSubmitAction() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBackUrl() {
    return new Url('blizz_bulk_creator.bulkcreate_configuration.add.step1');
  }

  /**
   * {@inheritdoc}
   */
  protected function getCancelUrl() {
    return new Url('blizz_bulk_creator.bulkcreate_configuration.list');
  }

}
