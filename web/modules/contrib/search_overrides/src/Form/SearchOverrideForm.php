<?php

namespace Drupal\search_overrides\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Search elevate edit forms.
 *
 * @ingroup search_api_solr_elevate_exclude
 */
class SearchOverrideForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $elevate = $form_state->getValue(['elnid', 0]);
    $exclude = $form_state->getValue(['exnid', 0]);

    if (!$elevate['target_id'] && !$exclude['target_id']) {
      $form_state->setErrorByName('elnid', $this->t('Please specify at least one promoted or excluded node.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Search override.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Search override.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.search_override.collection');
  }

}
