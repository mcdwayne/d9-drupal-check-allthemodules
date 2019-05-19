<?php

namespace Drupal\stats\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class StatProcessorForm.
 */
class StatProcessorForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\stats\Entity\StatProcessorInterface $stat_processor */
    $stat_processor = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $stat_processor->label(),
      '#description' => $this->t("Label for the Stat processor."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $stat_processor->id(),
      '#machine_name' => [
        'exists' => '\Drupal\stats\Entity\StatProcessor::load',
      ],
      '#disabled' => !$stat_processor->isNew(),
    ];

    $options = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface) {
        $options[$entity_type->id()] = $entity_type->getLabel();
      }
    }

    $form['triggerEntityType'] = [
      '#type' => 'select',
      '#title' => $this->t('Trigger: Entity type'),
      '#default_value' => $stat_processor->getTriggerEntityType(),
      '#required' => TRUE,
      '#options' => $options,
    ];

    $form['triggerBundle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Trigger: Bundle'),
      '#default_value' => $stat_processor->getTriggerBundle(),
    ];

    $form['weight'] = [
      '#type' => 'integer',
      '#title' => $this->t('Weight'),
      '#default_value' => $stat_processor->getTriggerBundle(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $stat_processor = $this->entity;
    $status = $stat_processor->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Stat processor.', [
          '%label' => $stat_processor->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Stat processor.', [
          '%label' => $stat_processor->label(),
        ]));
    }
    $form_state->setRedirectUrl($stat_processor->toUrl('collection'));
  }

}
