<?php

namespace Drupal\search_api_synonym\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Synonym edit forms.
 *
 * @ingroup search_api_synonym
 */
class SynonymForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Trim whitespaces from synonyms and save back into the form state.
    /* @var \Drupal\search_api_synonym\SynonymInterface $entity */
    $entity = $this->entity;
    $synonyms = $entity->getSynonymsFormatted();
    if (!empty($synonyms)) {
      $entity->setSynonyms($synonyms);
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Trim whitespaces from synonyms and save back into the form state.
    /* @var \Drupal\search_api_synonym\SynonymInterface $entity */
    $entity = $this->entity;
    $trimmed = array_map('trim', explode(',', $entity->getSynonyms()));
    $entity->setSynonyms(implode(',', $trimmed));

    // Save synonym.
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Synonym.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Synonym.', [
          '%label' => $entity->label(),
        ]));
    }

    $form_state->setRedirect($entity->toUrl('collection')->getRouteName());
  }

}
