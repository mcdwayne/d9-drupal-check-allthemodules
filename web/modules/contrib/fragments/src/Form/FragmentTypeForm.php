<?php

namespace Drupal\fragments\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FragmentTypeForm.
 */
class FragmentTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\fragments\Entity\FragmentType $fragmentType */
    $fragmentType = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $fragmentType->label(),
      '#description' => $this->t('The human-readable name of this fragment type.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $fragmentType->id(),
      '#machine_name' => [
        'exists' => '\Drupal\fragments\Entity\FragmentType::load',
      ],
      '#disabled' => !$fragmentType->isNew(),
      '#description' => $this->t('A unique machine-readable name for this fragment type. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#maxlength' => 255,
      '#default_value' => $fragmentType->getDescription(),
      '#description' => $this->t('Describe this fragment type. The text will be displayed on the <em>Add new fragment</em> page.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $fragmentType = $this->entity;
    $replacements = ['%label' => $fragmentType->label()];

    try {
      $status = $fragmentType->save();

      switch ($status) {
        case SAVED_NEW:
          $this->messenger()->addMessage($this->t('Created the %label fragment type.', $replacements));
          break;

        default:
          $this->messenger()->addMessage($this->t('Saved the %label fragment type.', $replacements));
      }
    }
    catch (EntityStorageException $e) {
      $this->messenger()->addMessage($this->t('A problem occurred trying to create or update the %label fragment type.', $replacements));
      watchdog_exception('fragments', $e, 'A problem occurred trying to create or update the %label fragment type.', $replacements);
    }

    try {
      $form_state->setRedirectUrl($fragmentType->toUrl('collection'));
    }
    catch (EntityMalformedException $e) {
      watchdog_exception('fragments', $e, 'Could not get collection URL for fragment types.');
    }
  }

}
