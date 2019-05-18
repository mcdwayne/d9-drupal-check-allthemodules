<?php

namespace Drupal\chatbot\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\chatbot\Plugin\ChatbotPluginCollection;

/**
 * Defines the Chatbot entity.
 *
 * @ingroup chatbot
 *
 * @ContentEntityType(
 *   id = "chatbot",
 *   label = @Translation("Chatbot"),
 *   handlers = {
 *     "list_builder" = "Drupal\chatbot\ChatbotListBuilder",
 *     "views_data" = "Drupal\chatbot\Entity\ChatbotViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\chatbot\Form\ChatbotForm",
 *       "add" = "Drupal\chatbot\Form\ChatbotForm",
 *       "edit" = "Drupal\chatbot\Form\ChatbotForm",
 *       "delete" = "Drupal\chatbot\Form\ChatbotDeleteForm",
 *     },
 *     "access" = "Drupal\chatbot\ChatbotAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\chatbot\ChatbotHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "chatbot",
 *   admin_permission = "administer chatbot entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/chatbots/chatbot/{chatbot}",
 *     "add-form" = "/admin/structure/chatbots/chatbot/add",
 *     "edit-form" = "/admin/structure/chatbots/chatbot/{chatbot}/edit",
 *     "delete-form" = "/admin/structure/chatbots/chatbot/{chatbot}/delete",
 *     "collection" = "/admin/structure/chatbots/chatbot",
 *   },
 *   field_ui_base_route = "chatbot.settings"
 * )
 */
class Chatbot extends ContentEntityBase implements ChatbotInterface, EntityWithPluginCollectionInterface {

  use EntityChangedTrait;

  /**
   * The plugin collection that stores chatbot plugins.
   *
   * @var \Drupal\chatbot\Plugin\ChatbotPluginCollection
   */
  protected $pluginCollection;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
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
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the Chatbot entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setRequired(TRUE)
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['plugin'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Plugin'))
      ->setDescription(t('Choose chatbot plugin.'))
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [],
        'allowed_values_function' => 'chatbot_plugins_allowed_values',
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['webhook_path'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Webhook Path'))
      ->setDescription(t('Specify a webhook path for chatbot plugin.'))
      ->setSettings([
        'max_length' => 128,
        'text_processing' => 0,
      ])
      ->setRequired(TRUE)
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->addConstraint('ValidPath', []);

    $fields['workflow'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Workflow'))
      ->setDescription(t('Select a workflow.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'chatbot_workflow')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    $fields['configuration'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Configuration'))
      ->setDescription(t('Specify chatbot plugin configuration here in key|value format with each item on a new line.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 0,
        'settings' => [
          'rows' => 12,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
        'label' => 'above',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Chatbot is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

    /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    $this->getPluginCollection()->addInstanceID($this->get('plugin')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->getPluginCollection()->get($this->get('plugin')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function setPlugin($plugin_id) {
    $this->set('plugin', $plugin_id);
    $this->getPluginCollection()->addInstanceID($plugin_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflow() {
    return $this->get('workflow')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWorkfow($workfow) {
    $this->set('workfow', $workfow);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWebhookPath() {
    return $this->get('webhook_path')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWebhookPath($webhook_path) {
    $this->set('webhook_path', $webhook_path);
    return $this;
  }

  /**
   * Encapsulates the creation of the chatbot LazyPluginCollection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The chatbot plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->pluginCollection) {
      $this->pluginCollection = new ChatbotPluginCollection($this->chatbotPluginManager(), $this->get('plugin')->value, $this->getConfiguration(), $this->id());
    }
    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return array('configuration' => $this->getPluginCollection());
  }

  /**
   * Wraps the chatbot plugin manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   A chatbot plugin manager object.
   */
  protected function chatbotPluginManager() {
    return \Drupal::service('plugin.manager.chatbot');
  }

  /**
   *
   * @return array
   *  Plugin options to be used in plugin field.
   */
  public static function getAllPluginOptions() {
    $plugin_manager = \Drupal::service('plugin.manager.chatbot');
    $plugin_definitions = $plugin_manager->getDefinitions();
    $options = array();

    foreach ($plugin_definitions as $id => $plugin) {
      $options[$id] = $plugin['title']->render();
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = [];
    $conf_arr = explode("\n", $this->get('configuration')->value);
    if (!empty($conf_arr)) {
      foreach ($conf_arr as $item) {
        $arr = explode("|", trim($item));
        if (!empty($arr) && count($arr) == 2) {
          $key = $arr[0];
          $configuration[$key] = $arr[1];
        }
      }
    }
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration_string = '';
    if (!empty($configuration)) {
      $array_items = [];
      foreach ($configuration as $key => $value) {
        $array_items[] = implode("|", [$key, $value]);
      }

      if (!empty($array_items)) {
        $configuration_string = implode("\n", $array_items);
      }
    }

    $this->set('configuration', $configuration_string);
    return $this;
  }

}
