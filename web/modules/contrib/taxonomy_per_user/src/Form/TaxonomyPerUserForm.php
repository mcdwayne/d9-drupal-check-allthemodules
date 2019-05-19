<?php

namespace Drupal\taxonomy_per_user\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the tpu entity edit forms.
 *
 * @ingroup taxonomy_per_user
 */
class TaxonomyPerUserForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\taxonomy_per_user\Entity\TaxonomyPerUser */
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.taxonomy_per_user.collection');
    $entity = $this->getEntity();
    $entity->save();
  }

}
