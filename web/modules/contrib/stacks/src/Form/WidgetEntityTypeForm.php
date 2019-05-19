<?php

namespace Drupal\stacks\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class WidgetEntityTypeForm.
 *
 * @package Drupal\stacks\Form
 */
class WidgetEntityTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $widget_entity_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#maxlength' => 255,
      '#default_value' => $widget_entity_type->label(),
      '#description' => t("Label for the Widget Entity type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $widget_entity_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\stacks\Entity\WidgetEntityType::load',
      ],
      '#disabled' => !$widget_entity_type->isNew(),
    ];

    $widget_type_manager = \Drupal::service('plugin.manager.stacks_widget_type');
    $options = $widget_type_manager->getDefinitionsOptions();

    $form['advanced_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced Configuration'),
      '#open' => FALSE,
    ];

    $default_plugin = $widget_entity_type->getPlugin();

    if (!$default_plugin) {
      $default_plugin = 'default_widget';
    }

    $form['advanced_options']['plugin'] = [
      '#title' => t('Widget behavior'),
      '#description' => t("List of possible widget behavior processors."),
      '#type' => 'select',
      '#default_value' => $default_plugin,
      '#options' => $options,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $widget_entity_type = $this->entity;
    $status = $widget_entity_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message(t('Created the %label Widget Entity type.', [
          '%label' => $widget_entity_type->label(),
        ]));
        break;

      default:
        drupal_set_message(t('Saved the %label Widget Entity type.', [
          '%label' => $widget_entity_type->label(),
        ]));
    }

    $form_state->setRedirectUrl($widget_entity_type->urlInfo('collection'));
  }

}
