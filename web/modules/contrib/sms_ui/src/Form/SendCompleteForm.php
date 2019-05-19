<?php

namespace Drupal\sms_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\sms_ui\Entity\SmsHistoryInterface;

/**
 * Provides a form to display options after message sending is complete.
 *
 * Form that shows summary of sent SMS and provides other options. Other modules
 * would normally alter this form to add options like saving sent SMS and
 * requesting delivery reports.
 *
 * @todo Upgrade the done page to look better.
 */
class SendCompleteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sms_ui_send_complete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SmsHistoryInterface $sms_history = NULL) {
    // If the SMS history has expired, then throw 404.
    if (empty($sms_history)) {
      // @todo: Is this the right exception to use?
//      throw new NotFoundHttpException($this->t('Message result was not found or has expired'));
    }
    else {
      if ($sms_history->getStatus() === 'sent') {
        // Message has been sent, so publish results.
        $form['summary'] = [
          '#theme'    => 'sms_result',
          '#messages' => $sms_history->getSmsMessages(),
        ];
      }
      else {
        // Most likely, message was queued, so direct the person to the queue.
        drupal_set_message($this->t('You message has been queued for sending later. <a href=":message_queue">Check your queued messages.</a>',
          [':message_queue' => Url::fromRoute('sms_ui.history_queued', [], ['fragment' => 'history-' . $sms_history->id()])->toString()]));
      }
    }

    $form['options'] = [
      '#type' => 'fieldset',
      '#title' => t('Options'),
      '#description' => t('Additional options for sent message'),
      '#collapsible' => true,
      '#collapsed' => false,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Done'),
      '#weight' => 50,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('save_template') && \Drupal::moduleHandler()->moduleExists('sms_ui_templates')) {
      // Create new template node type and add the message as content and generate title.
      $node = Node::create([
        'uid' => $this->currentUser()->id(),
        'name' => $this->currentUser()->getUsername(),
        'type' => TEMPLATE_TYPE_NAME,
        'language' => '',
        'body' => $form_state->getValue(['sms', 'message']),
        'title' => substr($form_state->getValue(['sms','message']), 0, 20),
        'format' => NULL,
      ]);
      $node->save();
      drupal_set_message(t('The template has been saved.'));
      $this->logger('sms_ui')->notice('@type: added %title.', ['@type' => $node->getEntityType()->getLabel(), '%title' => $node->getTitle()]);
    }
  }

}
