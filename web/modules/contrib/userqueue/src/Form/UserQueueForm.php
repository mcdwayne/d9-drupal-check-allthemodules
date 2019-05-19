<?php

/**
 * @file
 * Contains \Drupal\userqueue\Form\UserQueueForm.
 */

namespace Drupal\userqueue\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Implements an UserQueueForm form.
 */
class UserQueueForm extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'userqueue';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $uqid = NULL) {
    //Get the queue data.
    if($uqid) {
      $queue = userqueue_load($uqid);
    }
    else {
      $queue = NULL;
    }

    $form['queue_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Queue Title'),
      '#default_value' => !empty($queue['title']) ? $queue['title'] : '',
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    );

    $form['size'] = array(
      '#type' => 'number',
      '#title' => t('Queue Length'),
      '#size' => 60,
      '#required' => TRUE,
      '#maxlength' => 4,
      '#default_value' => !empty($queue['size']) ? $queue['size'] : 5,
      '#description' => t('Maximum number of users allowed in the queue. Use 0 (zero) for infinite.'),
    );
  
    $form['reverse'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show items in reverse'),
      '#default_value' => !empty($queue['reverse']) ? $queue['reverse'] : '',
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    if ($queue['uqid']) {
      $form['uqid'] = array(
        '#type' => 'hidden',
        '#value' => $queue['uqid'],
      );

      $form['delete'] = array(
        '#type' => 'submit',
        '#value' => t('Delete'),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uqid = $form_state->getValue('uqid');
    $title = $form_state->getValue('queue_title');
    $size = $form_state->getValue('size');
    $reverse = $form_state->getValue('reverse');
    if ($uqid) {
      if ($form_state->getValue('op') == t('Delete')) {
        $form_state->setRedirect('userqueue.admin_userqueue.uqid.delete', array('uqid' => $uqid));
      }
      elseif ($form_state->getValue('op') == t('Save')) {
        db_update('userqueue')
          ->fields(array('title' => $title, 'size' => $size, 'reverse' => $reverse))
          ->condition('uqid', $uqid, '=')
          ->execute();
        drupal_set_message($this->t('Updated user queue %title.', array('%title' => $title)));
        $this->logger('userqueue')->notice('Updated user queue %title.', array('%title' => $title));
        $form_state->setRedirect('userqueue.admin_userqueue.list');
      }
    }
    else {
      db_insert('userqueue')->fields(array('title' => $title,'size' => $size,'reverse' => $reverse,))->execute();
      drupal_set_message($this->t('Created user queue %title.', array('%title' => $title)));
      $this->logger('userqueue')->notice('Created new user queue %title.', array('%title' => $title));
      $form_state->setRedirect('userqueue.admin_userqueue.list');
    }
  }
}