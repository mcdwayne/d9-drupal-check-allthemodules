<?php

namespace Drupal\sender\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sender\Entity\Message;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A form to create and edit messages.
 */
class MessageForm extends EntityForm {

  /**
   * Plugin manager for message groups.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $groupPluginManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface
   *   The plugin manager for message groups.
   */
  public function __construct(PluginManagerInterface $group_plugin_manager) {
    $this->groupPluginManager = $group_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.sender_message_groups'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Sets the message's group with submitted value to get updated token 
    // types.
    if ($group = $form_state->getValue('group')) {
      $this->entity->setGroupId($group);
    }

    // Administrative label.
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Label used for administrative pages only.'),
      '#default_value' => $this->entity->getLabel(),
      '#required' => TRUE,
    ];

    // The message's ID (machine name).
    $form['id'] = [
      '#type' => 'machine_name',
      '#maxlength' => Message::ID_MAX_LENGTH,
      '#default_value' => $this->entity->id(),
      '#disabled' => !$this->entity->isNew(),
      '#machine_name' => [
        'source' => ['label'],
        'exists' => [$this, 'messageExists'],
      ],
    ];

    // The message's group. It uses Ajax to update the list of available tokens
    // according to selected group.
    $form['group'] = [
      '#type' => 'select',
      '#title' => $this->t('Group'),
      '#description' => $this->t('Message groups are defined by modules and determine the available tokens.'),
      '#options' => ['' => $this->t('- None -')] + $this->getGroupOptions(),
      '#default_value' => $this->entity->getGroupId(),
      '#ajax' => [
        'callback' => [$this, 'onGroupChange'],
        'wrapper' => 'token-tree-wrapper',
      ],
    ];

    // The message's subject.
    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#description' => $this->t("The message's subject."),
      '#maxlength' => 255,
      '#default_value' => $this->entity->getSubject(),
      '#element_validate' => ['token_element_validate'],
      '#token_types' => $this->entity->getTokenTypes(),
      '#required' => TRUE,
    ];

    // The message's body.
    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#format' => $this->entity->getBodyFormat(),
      '#default_value' => $this->entity->getBodyValue(),
      '#element_validate' => ['token_element_validate'],
      '#token_types' => $this->entity->getTokenTypes(),
    ];

    // Shows a list of available tokens.
    $form['token_tree_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'token-tree-wrapper',
      ],
    ];
    $form['token_tree_wrapper']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => $this->entity->getTokenTypes(),
      '#show_restricted' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Shows success or error message.
    if (parent::save($form, $form_state)) {
      drupal_set_message(t('The message %entity has been saved.', ['%entity' => $this->entity->label()]));
    }
    else {
      drupal_set_message(t('An error occured.', 'error'));
    }

    // Redirects to the messages listing.
    $form_state->setRedirect('entity.sender_message.collection');
  }

  /**
   * Checks if a message with the given ID already exists.
   *
   * This method is used to check if a machine name was already used.
   */
  public function messageExists($id) {
    return (bool) Message::load($id);
  }

  /**
   * Ajax callback called when the selected group changes.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   An updated portion of the form for replacement.
   */
  public function onGroupChange(array &$form, FormStateInterface $form_state) {
    return $form['token_tree_wrapper'];
  }

  /**
   * Gets an array of message group options to provide.
   *
   * @return array
   *   An array whose keys are group IDs and whose values are group labels.
   */
  protected function getGroupOptions() {
    $options = [];

    // Fills the options with available plugins (message groups).
    foreach ($this->groupPluginManager->getDefinitions() as $plugin_id => $definition) {
      $options[$plugin_id] = $definition['label'] ? $definition['label'] : $plugin_id;
    }

    // Sorts the options alphabetically.
    asort($options);
    return $options;
  }

}
