<?php


namespace Drupal\chatroom\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form with IRC buttons.
 */
class ChatroomIrcButtonsForm extends FormBase {

  /**
   * Chatroom object.
   *
   * @var \Drupal\chatroom\Chatroom
   */
  protected $chatroom;

  /**
   * Constructs a new ChatroomIrcButtonsForm.
   *
   * @param \Drupal\chatroom\Chatroom $chatroom
   *   Chatroom object.
   */
  public function __construct($chatroom) {
    $this->chatroom = $chatroom;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'chatroom_irc_buttons_form_' . $this->chatroom->cid->value;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   *
   * IRC buttons form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $chatroom = $this->chatroom;

    if (!$user->id() && (\Drupal::config('chatroom.settings')->get('allow_anon_name'))) {
      $form['chatroom_anon_name_' . $chatroom->cid->value] = array(
        '#type' => 'textfield',
        '#title' => t('Enter your name'),
        '#size' => 20,
        '#maxlength' => 256,
        '#attributes' => [
          'class' => ['chatroom-anon-name'],
        ],
      );
    }

    $form['chatroom_message_entry_box_' . $chatroom->cid->value] = array(
      '#type' => 'textarea',
      '#title' => t('Enter your message text here'),
      '#size' => 50,
      '#rows' => 1,
      '#maxlength' => \Drupal::config('chatroom.settings')->get('max_message_size'),
      '#attributes' => [
        'class' => ['chatroom-message-entry'],
      ],
    );

    $form['chatroom_message_entry_submit_' . $chatroom->cid->value] = array(
      '#type' => 'submit',
      '#value' => t('Chat'),
    );

    $form['#attached']['library'][] = 'chatroom/chatroom';

    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   *
   * Submit handler for IRC buttons form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Form submission is handled by javascript.
  }

}
