<?php

namespace Drupal\ptalk;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Render\Element;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the ptalk_message entity create forms.
 */
class MessageForm extends ContentEntityForm {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The message storage.
   *
   * @var \Drupal\ptalk\MessageStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('current_user')
    );
  }

  /**
   * Constructs a new MessageForm.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(EntityManagerInterface $entity_manager, AccountInterface $current_user) {
    parent::__construct($entity_manager);
    $this->currentUser = $current_user;
    $this->storage = $entity_manager->getStorage('ptalk_message');
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $message = $this->entity;

    $message_preview = $form_state->get('ptalk_message_preview');
    if (isset($message_preview)) {
      $form += $message_preview;
    }

    // If this is the new message, show recipients form.
    if (is_null($message->thread_id)) {
      $form['subject'] = [
        '#size' => 40,
        '#title' => t('Send to'),
      ];

      $form['recipients'] = [
        '#type' => 'textfield',
        '#autocomplete_route_name' => 'ptalk.autocomplete',
        // Allows for multiple selections, separated by commas.
        '#required' => TRUE,
        '#weight' => -100,
        '#size' => 60,
        '#title' => t('Send to'),
        '#description' => t('Enter the recipient, separate recipients with commas.'),
      ];

      if (isset($message->participants)) {
        $participants = ptalk_generate_user_array($message->participants);
        $recipients_name = [];
        foreach ($participants as $participant) {
          array_push($recipients_name, $participant->name->value);
        }

        $form['recipients']['#value'] = implode(', ', $recipients_name);
      }
    }

    return parent::form($form, $form_state, $message);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $message = $this->entity;

    if ($thread = $message->getThread()) {
      // Unset subject form if this is the reply to the thread.
      unset($form['subject']);

      $recipients = ptalk_load_message_recipients($thread->getParticipantsIds());
      $author = $recipients[$message->getOwnerId()];
      unset($recipients[$message->getOwnerId()]);
      list($blocked_ids, $blocked) = invoke_hooks_ptalk_block_message($author, $recipients, []);
      $author_blocked = FALSE;
      if ($blocked) {
        // If author for some reasons is blocked.
        if (in_array('author', array_keys($blocked))) {
          $author_blocked = TRUE;
          foreach ($blocked['author'] as $reason => $info) {
            $reasons[] = $info['message']['singular'];
          }
        }
        // If all recipients of the message are blocked for author.
        elseif (count($blocked_ids) == count($recipients)) {
          $author_blocked = TRUE;
          foreach ($blocked['recipients'] as $reason => $info) {
            $message = (count($info['ids']) > 1) ? ['#markup' => $info['message']['plural'] . ' ' . implode(', ', $info['ids']) . '.'] : ['#markup' => implode(', ', $info['ids']) . ' ' . $info['message']['singular']];
            $message = \Drupal::service('renderer')->render($message);
            $reasons[] = $message;
          }
        }
      }
      if ($author_blocked) {
        // If author is blocked to send message to recipients, hide all visible parts of the form.
        foreach (Element::children($form) as $element) {
          $form[$element]['#access'] = FALSE;
        }
        $form['blocked'] = [
          '#theme' => 'item_list',
          '#title' => t('You can not reply to this conversation because:'),
          '#items' => $reasons,
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    /* @var \Drupal\ptalk\MessageInterface $ptalk_message */
    $message = $this->entity;

    // Unset delete action on the message form.
    unset($element['delete']);

    // Mark the submit action as the primary action, when it appears.
    $element['submit']['#button_type'] = 'primary';

    // Show the preview button if message previews are optional.
    $element['submit']['#access'] = $message->thread_id ? ($this->currentUser->hasPermission('reply private conversation')) : ($this->currentUser->hasPermission('start private conversation'));
    $element['submit']['#value'] = $message->thread_id ? $this->t('Send') : $this->t('Start conversation');

    $element['preview'] = [
      '#type' => 'submit',
      '#value' => $this->t('Preview'),
      '#access' => $this->config('ptalk.settings')->get('ptalk_display_preview_button') ?: FALSE,
      '#submit' => ['::submitForm', '::preview'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $message = parent::buildEntity($form, $form_state);
    // Validate the message's subject. If not specified, extract from message
    // body.
    if (trim($message->getSubject()) == '') {
      if ($message->hasField('body')) {
        // The body may be in any format, so:
        // 1) Filter it into HTML
        // 2) Strip out all HTML tags
        // 3) Convert entities back to plain-text.
        $message_text = $message->body->processed;
        $message->setSubject(Unicode::truncate(trim(Html::decodeEntities(strip_tags($message_text))), 29, TRUE, TRUE));
      }
      // Edge cases where the message body is populated only by HTML tags will
      // require a default subject.
      if ($message->getSubject() == '') {
        $message->setSubject($this->t('(No subject)'));
      }
    }

    $send_to = [];
    $invalid = [];

    if ($thread = $message->getThread()) {
      foreach ($thread->getParticipantsIds() as $pid) {
        if ($participant = user_load($pid)) {
          array_push($send_to, $pid);
        }
        else {
          array_push($invalid, $pid);
        }
      }
    }
    // If this is the new message, not a reply to the thread,
    // then get recipients from the form_state.
    else {
      $names = $form_state->getValue('recipients');
      foreach (explode(',', $names) as $name) {
        $recipient = user_load_by_name(trim($name));
        if ($recipient) {
          array_push($send_to, $recipient->id());
        }
        else {
          array_push($invalid, $name);
        }
      }
      // Add the author of the message if was not added.
      if (!in_array($message->getOwnerId(), $send_to)) {
        array_push($send_to, $message->getOwnerId());
      }
    }
    $message->recipients = ptalk_load_message_recipients($send_to);

    return $message;
  }

  /**
   * Form submission handler for the 'preview' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function preview(array &$form, FormStateInterface $form_state) {
    $message_preview = ptalk_message_preview($this->entity, $form_state);
    $message_preview['#title'] = $this->t('Preview message');
    $form_state->set('ptalk_message_preview', $message_preview);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $message = $this->entity;
    $recipients = $message->recipients;
    $author = $recipients[$message->getOwnerId()];
    unset($recipients[$message->getOwnerId()]);
    list($blocked_ids, $blocked) = invoke_hooks_ptalk_block_message($author, $recipients, []);
    $author_blocked = FALSE;
    if ($blocked) {
      // If author for some reasons is blocked.
      if (in_array('author', array_keys($blocked))) {
        $author_blocked = TRUE;
        foreach ($blocked['author'] as $reason => $info) {
          drupal_set_message($info['message']['singular'], $info['message']['type']);
        }
      }
      else {
        if (count($blocked_ids) == count($recipients)) {
         $author_blocked = TRUE;
        }
        foreach ($blocked['recipients'] as $reason => $info) {
          $error_message = (count($info['ids']) > 1) ? ['#markup' => $info['message']['plural'] . ' ' . implode(', ', $info['ids']) . '.'] : ['#markup' => implode(', ', $info['ids']) . ' ' . $info['message']['singular']];
          $error_message = \Drupal::service('renderer')->render($error_message);
          if (!$author_blocked) {
            foreach (array_keys($info['ids']) as $id) {
              unset($message->recipients[$id]);
            }
          }
          drupal_set_message($error_message, $info['message']['type']);
        }
      }
    }
    $uri = '';
    if (!$author_blocked) {
      $send_message = $message->save();
      if ($send_message) {
        $per_page = $this->config('ptalk.settings')->get('ptalk_messages_per_page');
        $count_deleted = $message->getOwner()->hasPermission('read all private conversation') ? TRUE : FALSE;
        $uri = $message->getThread()->urlInfo();
        $page = $this->storage->getNumPage($message, $per_page, '', $count_deleted, $message->getOwner());
        $query = [];
        if ($page > 0) {
          $query['page'] = $page;
        }
        // Redirect to the message.
        $uri->setOption('query', $query);
        $uri->setOption('fragment', 'message-' . $message->id());
        drupal_set_message($this->t('Your message was created.'));
      }
      else {
        $uri = 'ptalk.ptalk_thread.collection';
        drupal_set_message(t('Failed to send a message to !recipients. Contact your site administrator.', array('!recipients' => implode(', ', $recipients))), 'error');
      }
    }

    $form_state->setRedirectUrl($uri);
  }

}
