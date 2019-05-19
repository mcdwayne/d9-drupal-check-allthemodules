<?php

namespace Drupal\stacks\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Widget Instance entity edit forms.
 *
 * @ingroup stacks
 */
class WidgetInstanceEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\stacks\Entity\WidgetInstanceEntity */
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message(t('Created the %label Widget Instance entity.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message(t('Saved the %label Widget Instance entity.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.widget_instance_entity.canonical', ['widget_instance_entity' => $entity->id()]);
  }

}
