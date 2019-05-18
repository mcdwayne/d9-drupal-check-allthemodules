<?php

namespace Drupal\content_synchronizer\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Import edit forms.
 *
 * @ingroup content_synchronizer
 */
class ImportEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /* @var $entity \Drupal\content_synchronizer\Entity\ImportEntity */
    $entity = $form_state->getFormObject()->getEntity();

    $defaultName = $entity ? $entity->label() : t('Import - %date', [
      '%date' => \Drupal::service('date.formatter')
        ->format(time())
    ]);
    $form['name']['widget'][0]['value']['#default_value'] = $defaultName;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\content_synchronizer\Entity\ImportEntity $entity */
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()
          ->addMessage($this->t('Created the %label Import.', [
            '%label' => $entity->label(),
          ]));
        break;

      default:
        \Drupal::messenger()->addMessage($this->t('Saved the %label Import.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.import_entity.canonical', ['import_entity' => $entity->id()]);
  }

}
