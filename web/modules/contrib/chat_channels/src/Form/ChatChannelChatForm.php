<?php

/**
 * @file
 * Contains \Drupal\chat_channels\Form\ChatChannelChatForm.
 */

namespace Drupal\chat_channels\Form;

use Drupal\chat_channels\Ajax\RefreshMessageCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for chat functionality.
 */
class ChatChannelChatForm extends FormBase {

  /**
   * Chat channel object.
   *
   * @var \Drupal\chat_channels\Entity\ChatChannel
   */
  protected $channel;

  public function getChannel() {
    return $this->channel;
  }

  /**
   * Constructs a new ChatChannelChatForm.
   *
   * @param \Drupal\chat_channels\Entity\ChatChannelInterface $channel
   *   Chat channel object.
   */
  public function __construct($channel) {
    $this->channel = $channel;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'chat_channel_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   *
   * Chat form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $channel = $this->channel;

    $form['#attributes']['data-channel-id'] = $channel->id();
    $form['#attributes']['class'][] = 'js-chatChannelForm';

    $form['chat_channel_id'] = [
      '#type' => 'value',
      '#value' => $channel->id(),
    ];

    $form['chat_channel_message_input'] = [
      '#type' => 'textarea',
      '#size' => 50,
      '#rows' => 1,
      '#attributes' => [
        'placeholder' => [
          $this->t('Type your message here')
        ],
        'class' => [
          'chat-channel-message',
          'js-chatChannelFormMessage',
        ],
      ],
    ];

    $form['chat_channel_message_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
      '#attributes' => [
        'class' => [
          'js-chatChannelFormSubmit',
          'chat-channel-submit',
        ],
      ],
      '#ajax' => [
        'callback' => '\Drupal\chat_channels\Form\ChatChannelChatForm::refreshMessages',
        'wrapper' => 'message-container',
        'effect' => 'slide',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];

    $form['#attached']['library'][] = 'chat_channels/chat';

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Validate if we have input.
    $message = static::processMessageContent($values['chat_channel_message_input']);
    if (empty($message)) {
      // TODO: Correct ajax return of error message
      $form_state->setErrorByName('chat_channel_message_input', $this->t('No valid input.'));
    }
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   *
   * Submit handler for Chat form.
   *
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    /** @var \Drupal\user\UserInterface $user */
    $user = \Drupal::currentUser();

    /** @var \Drupal\chat_channels\Entity\ChatChannelInterface $channel */
    $channel = $this->channel;

    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $message_storage = \Drupal::entityTypeManager()
      ->getStorage('chat_channel_message');

    $message = $message_storage->create([
      'uid' => $user->id(),
      'channel' => $channel->id(),
      'message' => static::processMessageContent($values['chat_channel_message_input']),
      'status' => TRUE,
      'created' => REQUEST_TIME,
      'changed' => REQUEST_TIME,
    ]);

    $message->save();
  }

  /**
   *
   * Ajax callback to submit the form.
   *
   * @param \Drupal\chat_channels\Form\ChatChannelChatForm $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public static function refreshMessages(&$form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo();
    /** @var \Drupal\chat_channels\Form\ChatChannelChatForm $callback_object */
    $callback_object = $build_info['callback_object'];

    // Instantiate an AjaxResponse Object to return.
    $ajax_response = new AjaxResponse();

    $ajax_response->addCommand(new RefreshMessageCommand($callback_object->getChannel()
      ->id()));

    // Return the AjaxResponse Object.
    return $ajax_response;
  }

  /**
   * All message processes can be bundled here.
   * This is function may be used for tagging, emoticons, etc.
   *
   * @param string $message
   *
   * @return string
   */
  public static function processMessageContent($message) {
    $message = static::cleanupMessageContent($message);

    return $message;
  }

  public static function cleanupMessageContent($message) {
    $r = html_entity_decode($message);
    $t = trim($r);
    return $t;
  }
}
