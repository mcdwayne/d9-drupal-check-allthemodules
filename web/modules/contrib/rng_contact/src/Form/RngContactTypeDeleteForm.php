<?php

namespace Drupal\rng_contact\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Url;

/**
 * Form controller to delete a contact type.
 */
class RngContactTypeDeleteForm extends EntityDeleteForm  {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete contact type %label?', array(
      '%label' => $this->entity->label(),
    ));
  }

  /**
   * @inheritDoc
   */
  public function getDescription() {
    return t('Deleting this contact type will also delete the associated contacts.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.rng_contact_type.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\rng_contact\Entity\RngContactTypeInterface $rng_contact_type */
    $rng_contact_type = &$this->entity;

    $count = $this->entityTypeManager
      ->getStorage('rng_contact')
      ->getQuery()
      ->condition('type', $rng_contact_type->id())
      ->count()
      ->execute();

    if ($count > 0) {
      drupal_set_message($this->t('Cannot delete contact type.'), 'warning');

      $form['#title'] = $this->getQuestion();
      $form['description'] = [
        '#markup' => $this->formatPlural(
          $count,
          'Unable to delete contact type. It is used by @count contact.',
          'Unable to delete contact type. It is used by @count contacts.'
        ),
      ];
    }
    else {
      $form = parent::buildForm($form, $form_state);
    }

    return $form;
  }

}
