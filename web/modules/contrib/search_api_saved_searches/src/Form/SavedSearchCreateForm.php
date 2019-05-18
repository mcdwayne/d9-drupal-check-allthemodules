<?php

namespace Drupal\search_api_saved_searches\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for saving a search.
 */
class SavedSearchCreateForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $bundle = $this->entity->bundle();
    $id = "search-api-saved-searches-save-$bundle-form-wrapper";
    $form['#prefix'] = "<div id=\"$id\">";
    $form['#suffix'] = '</div>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    // Change label.
    $actions['submit']['#value'] = $this->t('Save search');

    // Add AJAX handling.
    $bundle = $this->entity->bundle();
    $id = "search-api-saved-searches-save-$bundle-form-wrapper";
    $actions['submit']['#ajax'] = [
      'callback' => '::saveFormAjax',
      'wrapper' => $id,
      'method' => 'replace',
      'effect' => 'fade',
    ];
    $actions['submit']['#executes_submit_callback'] = TRUE;

    return $actions;
  }

  /**
   * Handles an AJAX submit of the form.
   *
   * @param array $form
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The part of the form to return as AJAX.
   */
  public function saveFormAjax(array $form, FormStateInterface $form_state) {
    return $form_state->getErrors() ? $form : ['#type' => 'status_messages'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $return = parent::save($form, $form_state);

    if ($return === SAVED_NEW) {
      /** @var \Drupal\search_api_saved_searches\SavedSearchInterface $search */
      $search = $this->entity;
      $enabled = $this->entity->get('status')->value;
      if ($enabled) {
        if ($search->get('notify_interval')->value < 0) {
          $this->messenger()->addStatus($this->t('Your saved search was successfully created.'));
        }
        else {
          $this->messenger()->addStatus($this->t('Your saved search was successfully created. You will receive notifications for new results in the future.'));
        }
      }
      else {
        // @todo Move the second part of this message to the "E-mail" plugin.
        $this->messenger()->addStatus($this->t('Your saved search was successfully created. You will soon receive an e-mail with a confirmation link to activate it.'));
      }
    }

    return $return;
  }

}
