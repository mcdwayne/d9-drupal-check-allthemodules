<?php

/**
 * @file
 * Contains \Drupal\sms_ui\Form\GroupComposeForm
 */

namespace Drupal\sms_ui\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\sms_ui\Message\GroupSmsMessage;

/**
 * Form for composing group SMS.
 */
class GroupComposeForm extends FormBase {

  use ComposeFormTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sms_ui_group_compose_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = $this->getBaseComposeForm($form, $form_state);
    $group_lists = \Drupal::service('sms_ui.group_list.manager')->getGroupList($this->currentUser()->id());
    $form['#attached']['library'][] = 'sms_ui/group-compose-form';
    $form['left_pane']['recipients_container']['group_name'] = [
      '#type' => 'select',
      '#options' => $group_lists,
      '#title' => t('Recipients list'),
      '#required' => TRUE,
      '#description' => t('Select a recipients list to which you want to send SMS.'),
      '#weight' => -1.5,
      '#attributes' => ['class' => ['text-common']],
    ];
    $form['left_pane']['recipients_container']['group_list_upload'] = [
      '#type' => 'link',
      '#title' => $this->t('Click to upload new group list'),
      '#url' => Url::fromRoute('sms_ui.group_list_upload', ['js' => 'nojs']),
      '#weight' => -1.4,
      '#attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'height' => 350,
          'width' => 700
        ]),
      ],
    ];

//    $form['left_pane']['recipients_container']['group_list_preview'] = [
//      '#type' => 'textarea',
//      '#title' => t('To'),
//      '#rows' => 3,
//      '#resizable' => FALSE,
//      '#required' => FALSE,
//      '#description' => t('Comma-, or space-separated list of numbers complete <br/>'
//        . 'with international code (no + or 00 prefix required)'),
//      '#attributes' => array('class' => 'text-common'),
//    ];

    // Templates can be stored and loaded.
//    $templates = array('0' => '-- Select a template --'); // + _sms_ui_get_templates();
//    $form['left_pane']['template'] = [
//      '#type' => 'select',
//      '#options' => $templates,
//      '#title' => t('Use template'),
//      '#required' => FALSE,
//      '#description' => t('Select a template to use for this sms'),
//      '#ajax' => array(
//        'path' => Url::fromRoute('sms_ui/templates/load_js'),
//        'wrapper' => 'message_container',
//      ),
//      '#attributes' => array('class' => array('text-common')),
//      '#weight' => -1,
//    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Update message with prefix and suffix.
    // @todo Remove this to a separate (customization) module.
    $form_state->setValue('message', $form_state->getValue('prefix') . $form_state->getValue('message') . $form_state->getValue('suffix'));

    if ($this->currentUser()->hasPermission('send sms')) {
      // Create options object from form values by removing unnecessary form
      // variables.
      $options = $form_state->cleanValues()->getValues();
      // For group compose forms, the recipients would be from a named group,
      // @todo Implement this as a content entity...??
      $sms = new GroupSmsMessage($options['sender'], NULL, $options['message'], [], $this->currentUser()->id());
      $sms->setGroupName($options['group_name']);

      // Unset form variables that have been utilized already.
      unset($options['recipients'], $options['recipients_array'], $options['sender'], $options['message'], $options['group_name']);
      // Successful message should redirect to the done form.
      if ($result = $this->getSmsProvider()->send($sms, $options)) {
        // Redirect to done form. Store the message result in key-value cache so
        // it is available for the done form.
        \Drupal::keyValueExpirable('sms_ui')->setWithExpire($sms->getUuid(), $result, 1000);
        $form_state->setRedirect('sms_ui.send_status', ['uuid' => $sms->getUuid()], ['query' => $this->getDestinationArray()]);
      }
    }
    else {
      drupal_set_message($this->t('You are not permitted to send sms messages. Contact the administrator'), 'error');
    }
  }

}
