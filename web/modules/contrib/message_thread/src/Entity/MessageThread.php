<?php

namespace Drupal\message_thread\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Language\Language;
use Drupal\message\MessageException;
use Drupal\user\UserInterface;

/**
 * Defines the Message Thread entity class.
 *
 * @ContentEntityType(
 *   id = "message_thread",
 *   label = @Translation("Message thread"),
 *   bundle_label = @Translation("Message thread"),
 *   module = "message_thread",
 *   base_table = "message_thread",
 *   data_table = "message_thread_field_data",
 *   bundle_entity_type = "message_thread_template",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "thread_id",
 *     "bundle" = "template",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "uid" = "uid"
 *   },
 *   bundle_keys = {
 *     "bundle" = "template"
 *   },
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\message_thread\Form\MessageThreadForm",
 *       "add" = "Drupal\message_thread\Form\MessageThreadForm",
 *       "edit" = "Drupal\message_thread\Form\MessageThreadForm",
 *       "delete" = "Drupal\message_thread\Form\MessageThreadDeleteConfirm"
 *     },
 *     "list_builder" = "Drupal\message_thread\MessageThreadListBuilder",
 *     "views_data" = "Drupal\message_thread\MessageThreadViewsData",
 *     "storage_schema" = "Drupal\message_thread\MessageThreadStorageSchema",
 *     "access" = "Drupal\message_thread\MessageThreadAccessControlHandler",
 *   },
 *   links = {
 *     "canonical" = "/message/thread/{message_thread}",
 *     "edit-form" = "/message/thread/{message_thread}/edit",
 *     "delete-form" = "/message/thread/{message_thread}/delete"
 *   },
 *   field_ui_base_route = "entity.message_thread_template.edit_form"
 * )
 */
class MessageThread extends ContentEntityBase {

  /**
   * Holds the arguments of the message instance.
   *
   * @var array
   */
  protected $arguments;

  /**
   * The language to use when fetching text from the message thread.
   *
   * @var string
   */
  protected $language = Language::LANGCODE_NOT_SPECIFIED;

  /**
   * {@inheritdoc}
   */
  public function setTemplate(MessageThreadInterface $thread) {
    $this->set('template', $thread);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplate() {
    return MessageThreadTemplate::load($this->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->getEntityKey('uid');
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid() {
    return $this->get('uuid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getArguments() {
    $arguments = $this->get('arguments')->getValue();

    // @todo: See if there is a easier way to get only the 0 key.
    return $arguments ? $arguments[0] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setArguments(array $values) {
    $this->set('arguments', $values);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['thread_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Thread ID'))
      ->setDescription(t('The thread ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The message UUID'))
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The message language code.'));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Created by'))
      ->setDescription(t('The user that created the thread.'))
      ->setSettings([
        'target_type' => 'user',
        'default_value' => 0,
        'handler' => 'default',
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
        'weight' => -3,
      ])
      ->setDefaultValueCallback('Drupal\message\Entity\Message::getCurrentUserId')
      ->setDisplayConfigurable('view', TRUE)
      ->setTranslatable(TRUE);

    $fields['template'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Template'))
      ->setDescription(t('The message thread template.'))
      ->setSetting('target_type', 'message_thread_template')
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'))
      ->setDescription(t('The time that the thread was created.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setTranslatable(TRUE);

    $fields['arguments'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Arguments'))
      ->setDescription(t('Holds the arguments of the thread in serialise format.'));

    return $fields;
  }

  /**
   * Process the message given the arguments saved with it.
   *
   * @param array $arguments
   *   Array with the arguments.
   * @param array $output
   *   Array with the threadd text saved in the message thread.
   *
   * @return array
   *   The threaded text, with the placeholders replaced with the actual value,
   *   if there are indeed arguments.
   */
  protected function processArguments(array $arguments, array $output) {
    // Check if we have arguments saved along with the message.
    if (empty($arguments)) {
      return $output;
    }

    foreach ($arguments as $key => $value) {
      if (is_array($value) && !empty($value['callback']) && is_callable($value['callback'])) {

        // A replacement via callback function.
        $value += ['pass message' => FALSE];

        if ($value['pass message']) {
          // Pass the message object as-well.
          $value['arguments']['message_thread'] = $this;
        }

        $arguments[$key] = call_user_func_array($value['callback'], $value['arguments']);
      }
    }

    foreach ($output as $key => $value) {
      $output[$key] = new FormattableMarkup($value, $arguments);
    }

    return $output;
  }

  /**
   * Replace placeholders with tokens.
   *
   * @param array $output
   *   The threadd text to be replaced.
   * @param bool $clear
   *   Determine if unused token should be cleared.
   *
   * @return array
   *   The output with placeholders replaced with the token value,
   *   if there are indeed tokens.
   */
  protected function processTokens(array $output, $clear) {
    $options = [
      'langcode' => $this->language,
      'clear' => $clear,
    ];

    foreach ($output as $key => $value) {
      $output[$key] = \Drupal::token()
        ->replace($value, ['message_thread' => $this], $options);
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $token_options = !empty($this->data['token options']) ? $this->data['token options'] : [];

    $tokens = [];

    // Require a valid template when saving.
    if (!$this->getTemplate()) {
      throw new MessageException('No valid template found.');
    }

    $arguments = $this->getArguments();
    $this->setArguments(array_merge($tokens, $arguments));

    parent::save();
  }

  /**
   * {@inheritdoc}
   */
  public static function deleteMultiple(array $ids) {
    \Drupal::entityTypeManager()->getStorage('message_thread')->delete($ids);
  }

  /**
   * {@inheritdoc}
   */
  public static function queryByTemplate($template) {
    return \Drupal::entityQuery('message_thread')
      ->condition('template', $template)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */

  /**
   * {@inheritdoc}
   */
  public function setLanguage($language) {
    $this->language = $language;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
