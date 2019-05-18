<?php

namespace Drupal\activity\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Delete activities form.
 */
class DeleteActivityForm extends DeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'delete_activities_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $eventId = '') {

    $form = parent::buildForm($form, $form_state);
    $form['delete_activities'] = [
      '#type' => 'label',
      '#title' => t('Delete this event? This cannot be undone.'),
    ];
    $form['cancel_delete'] = [
      '#title' => $this->t('Cancel'),
      '#type' => 'link',
      '#url' => Url::fromUri('internal:/admin/activity'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Delete activity event.
    $query = $this->database->delete('activity_events');
    $query->condition('event_id', $this->pathArgs[4]);
    $query->execute();
    $url = Url::fromUri('internal:/admin/activity/');
    $form_state->setRedirectUrl($url);
    drupal_set_message(t('Activity @action deleted.', ['@action' => $this->pathArgs[4]]));
  }

}
