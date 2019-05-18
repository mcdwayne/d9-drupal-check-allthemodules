<?php

namespace Drupal\activity\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Delete action form.
 */
class DeleteActionForm extends DeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'delete_activities_action_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $label = '') {

    $form = parent::buildForm($form, $form_state);
    $form['delete_activities'] = [
      '#type' => 'label',
      '#title' => t('Delete this action? This cannot be undone.'),
    ];

    $form['cancel_delete'] = [
      '#title' => $this->t('Cancel'),
      '#type' => 'link',
      '#url' => Url::fromUri('internal:/activities/all'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Delete action.
    $query = $this->database->delete('activity');
    $query->condition('action_id', $this->pathArgs[5]);
    $query->execute();
    $url = Url::fromUri('internal:/activities/all');
    $form_state->setRedirectUrl($url);
    drupal_set_message(t('Action @action deleted.', ['@action' => $this->pathArgs[5]]));
  }

}
