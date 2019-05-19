<?php

namespace Drupal\sms_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\sms\Direction;
use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms_ui\Entity\SmsHistory;
use Drupal\sms_ui\SmsUi;
use Drupal\sms_ui\Utility\CountryCodes;
use Drupal\user\Entity\User;

/**
 * Contains basic form structure for composing SMS. Shared between many SMS forms.
 *
 * @see \Drupal\sms_ui\Form\BulkComposeForm
 * @see \Drupal\sms_ui\Form\GroupComposeForm
 */
trait ComposeFormTrait {

  /**
   * {@inheritdoc}
   */
  protected function getBaseComposeForm(array $form, FormStateInterface $form_state, array $options = []) {
    $form['#attached']['library'][] = 'sms_ui/compose-form-js';
    $form['#theme'][] = 'sms_ui_compose_form';

    // Add default values for items if the form is being cloned from an existing
    // 'saved' or 'sent' item.
    if ($this->getRequest()->query->has('_stored')) {
      $history = SmsHistory::load($this->getRequest()->query->get('_stored'));
      $form_state->set('history', $history);
    }
    else {
      $history = NULL;
    }

//    $numbers = \Drupal::service('sms.phone_number')->getPhoneNumbers($account);
    $numbers = [];
    $form['sender'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sender'),
      '#title_display' => 'invisible',
      '#placeholder' => $this->t('Sender'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#weight' => -3,
//      '#default_value' => ($numbers) ? reset($numbers) : '',
      '#default_value' => $history ? $history->getSender() : '',
    ];

    $form['recipients_container'] = [
      '#type' => 'markup',
      '#prefix' => '<div id="recipients_container">',
      '#suffix' => '</div>',
      '#weight' => -2,
    ];
    $form['recipients_container']['recipients'] = [];

    $form['message_container'] = [
      '#type' => 'markup',
      '#prefix' => '<div id="message_container">',
      '#suffix' => '</div>',
    ];

    $form['message_container']['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#title_display' => 'invisible',
      '#placeholder' => $this->t('Message'),
      '#rows' => 5,
      '#resizable' => FALSE,
      '#required' => TRUE,
      '#weight' => -1,
      '#default_value' => $history ? $history->getMessage() : '',
    ];

    $form['actions']['send_sms'] = [
      '#type' => 'submit',
      '#value' => t('Send'),
    ];

    $form['actions']['save_draft'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
//      '#limit_validation_errors' => [],
    ];

    // Form statistics and options.
    $form['statistics']['character_count'] = [
      '#theme' => 'sms_ui_statistic',
      '#name' => 'character_count',
      '#value' => 0,
      '#title' => $this->t('Characters'),
    ];

    $form['statistics']['recipient_count'] = [
      '#theme' => 'sms_ui_statistic',
      '#name' => 'recipient_count',
      '#value' => 0,
      '#title' => $this->t('Recipients'),
    ];

    $form['options']['isflash'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Flash message'),
      '#default_value' => FALSE,
    ];

    $form['options']['send_direct'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not queue message (send direct)'),
      '#default_value' => FALSE,
    ];

    if ($this->currentUser()->hasPermission('create sms ui history')) {
      $form['options']['save_after'] = [
        '#type' => 'checkbox',
        '#title' => t('Do not save message after sending.'),
        '#default_value' => FALSE,
      ];
    }

    $form['options']['country_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => CountryCodes::getCountryCodes('normal'),
      '#default_value' => SmsUi::defaultCountryCode(),
      '#description' => $this->t('Select the country for local numbers'),
    ];

    return $form;
  }

  /**
   * Validates that the sender is not blocked.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return string
   *   The sender ID rule that was matched if validation failed.
   */
  protected function validateSenderId(FormStateInterface $form_state) {
    // Validate blocked sender IDs.
    $match = '';
    $sender_id = $form_state->getValue('sender');
    if (!$this->getSenderIdFilter()->isAllowed($sender_id, $this->currentUser(), $match)) {
      // Log this event and set error if the specified sender ID is not allowed.
      $args = [
        '@sender_id' => $sender_id,
        '@email' => \Drupal::config('system.site')->get('mail'),
      ];
      $this->logger('sms_ui')->notice('Attempt to use blocked sender ID <b>@sender_id</b> denied.', $args);
      $form_state->setErrorByName('sender', t('The sender ID <b>@sender_id</b> is not allowed. If you are the genuine owner of the sender ID, you can request access by mailing @email', $args));
    }
    return $match;
  }

  protected function traitSubmitHandler(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#id'] === 'edit-save-draft') {
      $history = $form_state->get('history') ? : SmsHistory::create();
      if ($history->getStatus() === 'sent') {
        // Don't overwrite history for sent items.
        $history = $history->createDuplicate();
        $message = SmsMessage::create();
      }
      else if ($history->getStatus() === 'draft') {
        $message = $history->getSmsMessages()[0];
      }
      else {
        $message = SmsMessage::create();
      }

      $message
        ->setMessage($form_state->getValue('message'))
        ->setSender($form_state->getValue('sender'))
        ->removeRecipients($message->getRecipients())
        ->addRecipients($form_state->getValue('recipients_array'))
        ->setSenderEntity(User::load($this->currentUser()->id()))
        // Set send time 10 years in the future.
        ->setSendTime(REQUEST_TIME + 10 * 365 * 86400)
        ->save();

      $history
        ->setSmsMessages([$message])
        ->setStatus('draft')
        ->save();

      if ($history->save()) {
        drupal_set_message($this->t('Your message was saved to draft. <a href=":href">Open</a>',
          [':href' => Url::fromRoute('sms_ui.send_bulk', [], ['query' => ['_stored' => $history->id()]])->toString()]));
      }
      else {
        drupal_set_message($this->t('Your message could not be saved to draft.'), 'warning');
      }
      $form_state->setRedirect('<current>');
    }
  }

  /**
   * Provides addons for the group compose form.
   *
   * @param array $form
   *   The form to enhance with addons.
   *
   * @return array
   *   The enhanced form.
   */
  public function addGroupFormAddOns(array $form) {
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['token_help'] = [
        '#title' => $this->t('Token replacement patterns'),
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#weight' => 100,
      ];

      $form['token_help']['help'] = [
        '#theme' => 'token_help',
        '#token_type' => 'sms_ui_bulk',
      ];
    }
    return $form;
  }

  /**
   * Dispatches the SMS messages either by queuing or sending directly.
   * 
   * @param \Drupal\sms\Message\SmsMessageInterface $sms
   *   The message to be sent.
   * 
   * @return \Drupal\sms\Message\SmsMessageInterface[]
   *   Messages that have been dispatched.
   */
  protected function dispatchMessage(SmsMessageInterface $sms, $send_direct = FALSE) {
    if ($send_direct) {
      return $this->getSmsProvider()->send($sms);
    }
    else {
      // Ensure the direction is set.
      $sms->setDirection(Direction::OUTGOING);
      return $this->getSmsProvider()->queue($sms);
    }
  }

  /**
   * Returns the currently active SMS provider.
   *
   * @return \Drupal\sms\Provider\SmsProviderInterface
   */
  protected function getSmsProvider() {
    return \Drupal::service('sms.provider');
  }

  /**
   * Wrapper for the sender_id_filter service.
   *
   * @return \Drupal\sms_ui\Utility\SenderIdFilter
   */
  protected function getSenderIdFilter() {
    return \Drupal::service('sms_ui.sender_id_filter');
  }

}
