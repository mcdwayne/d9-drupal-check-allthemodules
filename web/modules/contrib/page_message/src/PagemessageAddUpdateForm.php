<?php

/**
 * @file
 * Contains \Drupal\page_message\PagemessageAddUpdateForm
 */

namespace Drupal\page_message;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Simple form to add an entry, with all the interesting fields.
 */
class PagemessageAddUpdateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'page_message_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // If the request contains a valid pmid, prepare for update.
    $message_data = FALSE;
    $request = Request::createFromGlobals();
    $pmid = $request->get('pmid', false);

    $page_url = '';
    $message  = '';
    $add_or_update = t('Add');
    if($pmid) {
      $message_data = db_query("SELECT pmid, page, message FROM page_message WHERE pmid = :pmid", array(':pmid' => $pmid))->fetchObject();
      if((gettype($message_data) != 'object')
         || ($message_data->pmid != $pmid)
         ) {
          // Something is messed up. Display a message and fall pack to 'add' form.
          drupal_set_message("No page message with pmid = $pmid");
          $message_data = FALSE;
          $pmid = FALSE;
      }
      else {
        $page_url = $message_data->page;
        $message = $message_data->message;
        $add_or_update = t('Update');
      }
    }

    $form = array();

    $form['message'] = array(
      '#markup' => $this->t('Add an page/message entry to the page_message table.'),
    );

    $form['add'] = array(
      '#type' => 'fieldset',
      '#title' => t('Add a page message'),
    );
    $form['add']['pmid'] = array(
      '#type' => 'hidden',
      '#value' => $pmid,
    );
    $form['add']['page'] = array(
      '#type' => 'textfield',
      '#title' => t('Page'),
      '#size' => 45,
      '#default_value' => $page_url,
      '#required' => TRUE,
    );
    $form['add']['message'] = array(
      '#type' => 'textfield',
      '#title' => t('Message'),
      '#default_value' => $message,
      '#maxlength' => 256,
      '#required' => TRUE,
    );
    $form['add']['submit_add'] = array(
      '#name' => 'add',
      '#type' => 'submit',
      '#value' => $add_or_update,
    );
    if($pmid) {
      $form['add']['submit_delete'] = array(
        '#name' => 'delete',
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
    $entry = array(
      'page'    => check_markup($form_state->getValue('page'),    'basic_html'),
      'message' => check_markup($form_state->getValue('message'), 'basic_html'),
    );

    // $pmid might = FALSE;
    $pmid = $form_state->getValue('pmid');

    $trigger = $form_state->getTriggeringElement();
    $name = $trigger['#name'];

    if($name == 'add') {

      if($pmid) {
        // This updates an existing message.
        $entry['pmid'] = $pmid;
        $entry['updated'] = time();
        $return = PagemessageStorage::update($entry);
        if ($return) {
          $notify = t('Page message updated: Page: @page     Message: @message', array('@page' => $entry['page'], '@message' => $entry['message']));
          \Drupal::logger('page_message')->notice($notify);
          drupal_set_message($notify);
        }
      }
      else {
        // This adds a new message.
        $entry['created'] = time();
        $return = PagemessageStorage::insert($entry);
        if ($return) {
          $notify = t('Page message created: Page: @page     Message: @message', array('@page' => $entry['page'], '@message' => $entry['message']));
          \Drupal::logger('page_message')->notice($notify);
          drupal_set_message($notify);
        }
      }
    }
    else {
      $return = PagemessageStorage::delete($pmid);
      if ($return) {
          $notify = t('Page message deleted: Page: @page     Message: @message', array('@page' => $entry['page'], '@message' => $entry['message']));
        \Drupal::logger('page_message')->notice($notify);
        drupal_set_message($notify);
        $form_state->setRedirect('page_message.messages' );
      }
    }
  }


}
