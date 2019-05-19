<?php

/**
 * @file
 * Contains \Drupal\sms_actions\Plugin\Action\SendSmsAction
 */

namespace Drupal\sms_actions\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\sms_ui\Form\ComposeFormUtility;
use Drupal\sms_ui\Utility\PhoneNumberFormatHelper;
use Drupal\user\Entity\User;
use Drupal\views\ViewExecutable;

/**
 * Sends an sms message to selected numbers on the site.
 *
 * @Action(
 *   id = "sms_ui_send_sms_to_user_action",
 *   label = @Translation("Send SMS to selected numbers."),
 *   type = "sms"
 * )
 */

class SendSmsAction extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = false) {
    $access = AccessResult::allowed();
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $contact = $context['sms_contact'];
    $number = sms_ui_format_number($contact->mobile);
    if ($number === FALSE) {
      drupal_set_message(t('skipping invalid number (@number)', array('@number' => $contact->mobile)));
    }
    else {
      $message = \Drupal::token()->replace($this->configuration['message']);
      $ret = sms_advanced_send($number, $message, $context['gateway_options']);
      if ($ret === FALSE) {
        drupal_set_message(t('error from smsframework/gateway'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
//    $form = sms_send_form(NULL, NULL, FALSE);
//    $form['number']['#default_value'] = $this->configuration['number'];
//
//    $form['author'] = array(
//      '#type' => 'checkbox',
//      '#title' => t('Send to author of original post'),
//      '#description' => t('If checked, the message will be sent to author of the orginal post and the number field will be ignored.'),
//      '#default_value' => $this->configuration['author'],
//    );
//
//    $form['message'] = array(
//      '#type' => 'textarea',
//      '#title' => t('Message'),
//      '#default_value' => $this->configuration['message'],
//      '#cols' => '80',
//      '#rows' => '20',
//      // @todo Verify below tokens.
//      '#description' => t('The message that should be sent. You may include the following variables: [site:name], [user:name], [user:uid], [node:url], [node:alias], [node:type], [node:title], [node:teaser], [node:body]. Not all variables will be available in all contexts.'),
//    );
//    return $form;

    $form = ComposeFormUtility::addGroupFormAddOns(ComposeFormUtility::getBaseComposeForm($form, $form_state));

    $numbers = '';
    $rows = $this->getRows($context['view'], $context['selection']);
    foreach($rows as $row) {
      $field = $context['view']->base_table . '_mobile';
      $numbers .= $row->$field . ',';
    }
    if ($numbers) $numbers = substr($numbers, 0, -1);

    $form['left_pane']['number'] = array (
      '#type' => 'textfield',
      '#title' => t('To'),
      '#maxlength' => 255,
      '#value' => $numbers,
      '#required' => TRUE,
      '#weight' => -2,
      '#attributes' => array('class' => 'text-common', 'disabled' => 'TRUE'),
    );

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['number'] = $form_state->getValue('number');
    $this->configuration['sender'] = $form_state->getValue('sender');
    $this->configuration['message'] = $form_state->getValue('message');
    $this->configuration['gateway_options'] = $form_state->getValue('gateway');
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $account = User::load(\Drupal::currentUser()->id());

    // Allow only text or the one registered number to be used for sender
    if (is_numeric($form_state->getValue('sender'))) {
      $sender_number = sms_ui_format_number($form_state->getValue('sender'), $form_state->getValue('countrycode'));
      $user_number = sms_ui_format_number($account->sms_user['number'], $form_state->getValue('countrycode'));

      if ($sender_number !== $user_number) {
        $form_state->setErrorByName('sender', $this->t('Only text or your registered number is allowed for sender field. !link',
          array('!link' => \Drupal::l('Register your phone number.', Url::fromRoute('sms_user.user_edit', ['user' => $account])))));

      }
    }
    else if (!preg_match('/[a-zA-Z]/', $form_state['values']['sender'])) {
      $form_state->setErrorByName('sender', t('Alphabet character is required in sender field.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'number' => '',
      'author' => FALSE,
      'message' => '',
    );
  }

  /**
   * Gets ALL results from the view (if you have a decent memory limit).
   *
   * This is a must since views_bulk_operations doesn't pass the actual result
   * to the action hook.
   */
  protected function getRows(ViewExecutable $view, $selection = NULL) {
    static $results = NULL;
    if ($results == NULL) {
      $results = array();
      $myview = $view->copy();
      if (is_object($myview)) {
        $display = (empty($view->current_display) ? 'default' : $view->current_display);
        $myview->set_display($display);
        $myview->set_items_per_page(0);
        $myview->is_cacheable = TRUE;
        $myview->execute();
        foreach ($myview->result as $result) {
          if ($selection == NULL || in_array($result->{$view->base_field}, $selection)) {
            $results[$result->{$view->base_field}] = $result;
          }
        }
      }
    }
    return $results;
  }

}
