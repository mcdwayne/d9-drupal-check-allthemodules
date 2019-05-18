<?php

/**
 * @file
 * Contains \Drupal\hierarchical_config\Form\HierarchicalConfigurationTypeForm.
 */

namespace Drupal\hierarchical_config\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class HierarchicalConfigurationTypeForm.
 *
 * @package Drupal\hierarchical_config\Form
 */
class HierarchicalConfigurationTypeForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $hierarchical_configuration_type = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $hierarchical_configuration_type->label(),
      '#description' => $this->t("Label for the Hierarchical configuration type."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $hierarchical_configuration_type->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\hierarchical_config\Entity\HierarchicalConfigurationType::load',
      ),
      '#disabled' => !$hierarchical_configuration_type->isNew(),
    );

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $hierarchical_configuration_type = $this->entity;
    $status = $hierarchical_configuration_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Hierarchical configuration type.', [
          '%label' => $hierarchical_configuration_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Hierarchical configuration type.', [
          '%label' => $hierarchical_configuration_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($hierarchical_configuration_type->urlInfo('collection'));
  }

}
