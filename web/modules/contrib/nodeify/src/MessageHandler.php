<?php

namespace Drupal\nodeify;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Utility\Token;

class MessageHandler extends NotificationHandlerBase {

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, Token $token_utility, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger) {
    parent::__construct($config_factory, $token_utility);

    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
  }

    /**
     * Adds message submit handlers to node forms including node delete confirm from.
     *
     * @see nodeify_form_node_form_altero()
     * @see nodeify_form_node_confirm_form_alter()
     */
  public function addSubmitHandlers(&$form, FormStateInterface $form_state) {
    $node = $form_state->getFormObject()->getEntity();
    $node_type = $this->entityTypeManager->getStorage('node_type')->load($node->bundle());
    if (!$config = $node_type->getThirdPartySetting('nodeify', 'text', FALSE)) {
      return;
    }
    if (!empty($config['create']) && $node->isNew() && $form_state->getFormObject()->getFormId() == "node_{$node->bundle()}_form") {
      $form['actions']['submit']['#submit'][] = [get_class($this), 'nodeFormCreateCallback'];
    }
    if (!empty($config['update']) && $form_state->getFormObject()->getFormId() == "node_{$node->bundle()}_edit_form") {
      $form['actions']['submit']['#submit'][] = [get_class($this), 'nodeFormUpdateCallback'];
    }
    if (!empty($config['delete']) && $form_state->getFormObject()->getFormId() == "node_{$node->bundle()}_delete_form") {
      $form['actions']['submit']['#submit'][] = [get_class($this), 'nodeFormDeleteCallback'];
    }
  }

    /**
     * Perform the override to drupal_set_message current messages.
     *
     * @param $pattern
     *   The current message that should be removed.
     * @param null $replacement
     *   The new message, null if we just want to suppress a message.
     */
  public function replace($pattern, $replacement = NULL) {
    $statuses = $this->messenger->deleteByType('status');

    $filtered = array_filter($statuses, function ($message) use ($pattern) {
      return preg_match($pattern, $message) !== 1;
    });

    foreach ($filtered as $message) {
      drupal_set_message($message, 'status');
    }

    if ($replacement) {
      drupal_set_message($replacement, 'status');
    }
  }

    /**
     * Perform submit callback on submitted forms.
     *
     * @param $form
     * @param FormStateInterface $form_state
     * @param $action
     *   The action which matches the config definition.
     * @param $action_message_text
     *   The action which matches the actual text replacement in the core message.
     */
  public function processForm(&$form, FormStateInterface $form_state, $action, $action_message_text) {
    $node = $form_state->getFormObject()->getEntity();
    $node_type = $this->entityTypeManager->getStorage('node_type')->load($node->bundle());
    $node_type_label = $node_type->label();

    $pattern = "/^(The\ )?$node_type_label.*has been $action_message_text/";
    $replacement = $node_type->getThirdPartySetting('nodeify', 'text', [])[$action];
    $data = [
      'node' => $node,
    ];
    foreach ($node->getFields() as $field_name => $field) {
      if (!empty($field->entity)) {
        $entity_type = $field->entity->getEntityTypeId();
        $bundle = $field->entity->bundle();
        // We'll figure out a more efficient way to do this later, it's not needed right now.
        $key = $bundle == $entity_type ? "node_{$entity_type}" : "{$entity_type}_{$bundle}";
        $data[$key] = $field->entity;
      }
    }
    $this->replace($pattern, $this->process($replacement, $data));
  }

    /**
     * Custom form #submit handler.
     *
     * @param $form
     * @param FormStateInterface $form_state
     */
  public static function nodeFormDeleteCallback(&$form, FormStateInterface $form_state) {
    \Drupal::service('nodeify.message_handler')->processForm($form, $form_state, 'delete', 'deleted');
  }

    /**
     * Custom form #submit handler.
     *
     * @param $form
     * @param FormStateInterface $form_state
     */
  public static function nodeFormUpdateCallback(&$form, FormStateInterface $form_state) {
    \Drupal::service('nodeify.message_handler')->processForm($form, $form_state, 'update', 'updated');
  }

    /**
     * Custom form #submit handler.
     *
     * @param $form
     * @param FormStateInterface $form_state
     */
  public static function nodeFormCreateCallback(&$form, FormStateInterface $form_state) {
    \Drupal::service('nodeify.message_handler')->processForm($form, $form_state, 'create', 'created');
  }
}
