<?php

namespace Drupal\formazing\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Formazing entity edit forms.
 *
 * @ingroup formazing
 */
class FormazingEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form, FormStateInterface $form_state, $formazing = NULL
  ) {
    /* @var $entity \Drupal\formazing\Entity\FormazingEntity */
    $form = parent::buildForm($form, $form_state);
    $form['recipients']['#states'] = [
      'visible' => [
        ':input[name="has_recipients[value]"]' => ['checked' => TRUE],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\formazing\Entity\FormazingEntity $entity */
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()
          ->addStatus($this->t('Created the %label Formazing entity.', [
            '%label' => $entity->label(),
          ]));
        break;
      default:
        \Drupal::messenger()
          ->addStatus($this->t('Saved the %label Formazing entity.', [
            '%label' => $entity->label(),
          ]));
    }

    $form_state->setRedirect('entity.formazing_entity.canonical', ['formazing_entity' => $entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    // First, extract values from widgets.
    $extracted = $this->getFormDisplay($form_state)->extractFormValues($entity, $form, $form_state);

    // Some fields require usage of the specific accessor defined in FormazingEntity
    $accessors = [
      'recipients' => 'setRecipients',
    ];

    // Then extract the values of fields that are not rendered through widgets,
    // by simply copying from top-level form values. This leaves the fields
    // that are not being edited within this form untouched.
    foreach ($form_state->getValues() as $name => $values) {

      if ($entity->hasField($name)) {
        // If we have a dedicated accessor to use for this field use it
        if (array_key_exists($name, $accessors) && method_exists($entity, $accessors[$name])) {
          $value = array_pop($values)['value'];
          call_user_func_array([$entity, $accessors[$name]], [$value]);
        }
        // Otherwise use standard method
        else if (!isset($extracted[$name])) {
          $entity->set($name, $values);
        }
      }
    }
  }
}
