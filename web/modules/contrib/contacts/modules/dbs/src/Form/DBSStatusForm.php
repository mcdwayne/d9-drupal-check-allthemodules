<?php

namespace Drupal\contacts_dbs\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the dbs_status edit forms.
 */
class DBSStatusForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Hide revision information.
    $form['revision']['#access'] = FALSE;
    $form['revision_log_message']['#access'] = FALSE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function init(FormStateInterface $form_state) {
    parent::init($form_state);

    // Set the status owner from the route parameter.
    if ($this->entity->isNew()) {
      $user = $this->getRequest()->get('user');
      if ($user) {
        $this->entity->setOwnerId($user);
      }

      $workforce = $this->getRequest()->get('dbs_workforce');
      if ($workforce) {
        $this->entity->set('workforce', $workforce);
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * Always create a new revision.
   */
  protected function getNewRevisionDefault() {
    return TRUE;
  }

}
