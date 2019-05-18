<?php

namespace Drupal\media_feeds\Feeds\Target;


use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\feeds\Annotation\FeedsTarget;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Target\EntityReference;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\FeedsPluginManager;
use Drupal\feeds\Plugin\Type\Processor\ProcessorInterface;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;
use Drupal\field\FieldConfigInterface;
use Drupal\media\Entity\Media;
use Drupal\Core\Entity\Query\QueryFactory;

/**
 * Defines a wrapper target around a paragraph bundle's target field.
 *
 * @FeedsTarget(
 *   id = "entity_reference",
 *   field_types = {
 *     "entity_reference",
 *   },
 *   arguments = {
 *     "@messenger",
 *     "@plugin.manager.feeds.target",
 *     "@entity_field.manager",
 *     "@entity_type.manager",
 *     "@entity.repository",
 *     "@entity.query",
 *   }
 * )
 */
class MediaTarget extends EntityReference implements ConfigurableTargetInterface
{
  /**
   * @var MessengerInterface
   */
  protected $messenger;
  /**
   * @var FeedsPluginManager
   */
  protected $plugin_manager;

  /**
   * @var EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The paragraph storage.
   *
   * @var EntityStorageInterface
   */
  protected $media_storage;

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var FieldConfigInterface
   */
  protected $field;

  /**
   * @var boolean
   */
  protected $isMedia;

  /**
   * @var FieldTargetBase
   */
  protected $targetInstance;

  public static $temp_type = "entity_reference";

  public function __construct(array $configuration,
                              $plugin_id,
                              array $plugin_definition,
                              MessengerInterface $messenger,
                              FeedsPluginManager $plugin_manager,
                              EntityFieldManagerInterface $entityFieldManager,
                              EntityTypeManagerInterface $entity_type_manager,
                              EntityRepositoryInterface $entity_repository,
                              QueryFactory $query_factory)
  {
    $this->messenger = $messenger;
    $this->plugin_manager = $plugin_manager;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entity_type_manager;
    $this->media_storage = $entity_type_manager->getStorage('media');
    $this->configuration = $configuration;
    $this->targetDefinition = $configuration['target_definition'];
    $this->field = $this->targetDefinition->getFieldDefinition();
    if(self::isMedia($this->field)){
      $this->isMedia = true;
      $this->targetInstance = $this->createTargetInstance();
      FieldTargetBase::__construct($configuration,$plugin_id,$plugin_definition);
    }else{
      $this->isMedia = false;
      parent::__construct(
        $configuration,
        $plugin_id,
        $plugin_definition,
        $entity_type_manager,
        $query_factory,
        $entityFieldManager,
        $entity_repository);
    }
  }


  /**
   * {@inheritdoc}
   */
  public static function targets(array &$targets, FeedTypeInterface $feed_type, array $definition)
  {
    $processor = $feed_type->getProcessor();
    $entity_type = $processor->entityType();
    $bundle = $processor->bundle();
    $fields = self::getFields($entity_type, $bundle);
    foreach ($fields as $field) {
      self::prepareTarget($field);
      if(isset($field->sub_fields)){
        $sub_fields = $field->sub_fields;
        foreach ($sub_fields as $sub_field) {
          $subFInstance = $sub_field->getFieldDefinition();
          $info = $subFInstance->media_feeds_info;
          $id = $info['host']['field'] . "_" . $subFInstance->getName();
          $targets[$id] = $sub_field;
        }
      }
    }
    parent::targets($targets,$feed_type,$definition);
  }

  /**
   *
   * @return FieldConfigInterface[]
   */
  public static function getFields($entity_type, $bundle){
    $fieldManager = \Drupal::service('entity_field.manager');
    $entityFields = $fieldManager->getFieldDefinitions($entity_type,$bundle);
    $fields = array();
    foreach ($entityFields as $entityField) {
      if($entityField instanceof FieldConfigInterface && $entityField->getType() === 'entity_reference'){
        $fields[] = $entityField;
      }
    }
    return $fields;
  }

  /**
   * @param FieldDefinitionInterface $mediaField
   * @return array
   */
  public static function getSubFields(FieldDefinitionInterface $mediaField){
    $settings = $mediaField->getSetting('handler_settings');
    $bundles = $settings['target_bundles'];
    $fields = array();
    if(!is_array($bundles)){
      return $fields;
    }
    $fieldManager = \Drupal::service('entity_field.manager');
    $addInfo = function($field, $host, $bundle,$plugin){
      $info = array(
        'plugin' => $plugin,
        'type' => $field->getType(),
        'host' => array(
          'field' => $host,
          'bundle' => $bundle,
        ),
      );
      if($field instanceof BaseFieldDefinition){
        $field->media_feeds_info = $info;
      }else{
        $field->set('media_feeds_info', $info);
      }
    };
    foreach ($bundles as $def_bundle) {
      $sub_fields = $fieldManager->getFieldDefinitions('media', $def_bundle);
      foreach ($sub_fields as $fieldId => $sub_field){
        $plugin = self::getPlugin($sub_field);
        if($sub_field instanceof FieldConfigInterface){
          if(isset($plugin)){
            $type = $sub_field->getType();
            $correctPlugin = $plugin;
            if(isset($sub_field->media_feeds_info) && $type === 'entity_reference'){
              $old_info = $sub_field->media_feeds_info;
              $correctPlugin = $old_info['plugin'];
              $sub_field = clone $sub_field;
            }
            $addInfo($sub_field,$mediaField->getName(),$def_bundle,$correctPlugin);
            $id = $def_bundle . ':' . $sub_field->getName();
            $fields[$id] = $sub_field;
          }
        }
      }
    }
    return $fields;
  }

  /**
   * @param FieldDefinitionInterface $field
   * @return bool
   */
  protected static function isMedia(FieldDefinitionInterface $field){
    $info = null;
    $handler = $field->getSetting('handler');
    if(!isset($media)){
      $entityType = $field->getTargetEntityTypeId();
      if($entityType === 'media'){
        return true;
      }
    }
    return $handler === 'default:media';
  }

  /**
   * @param FieldDefinitionInterface $field
   * @param ProcessorInterface|null $processor
   * @return null
   */
  protected static function getPlugin(FieldDefinitionInterface $field, ProcessorInterface $processor = null){
    $targetsManager = \Drupal::service('plugin.manager.feeds.target');
    $plugins = $targetsManager->getDefinitions();
    $id = null;
    if($field instanceof FieldConfigInterface){
      $id = $field->id();
    }else if($field instanceof BaseFieldDefinition){
      $id = $field->getUniqueIdentifier();
    }
    if ($field->isReadOnly() || (isset($processor) && $id === $processor->bundleKey())) {
      return null;
    }
    foreach ($plugins as $plugin) {
      if(in_array($field->getType(), $plugin['field_types'])){
        return $plugin;
      }
    }
    return null;
  }

  /**
   * @inheritdoc
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition)
  {
    $type = $field_definition->getType();
    $name = $field_definition->getName();
    if($name === 'field_teaser_media'){
      $stop = null;
    }
    if(isset($field_definition->media_feeds_info)){
      $info = $field_definition->media_feeds_info;
      $type = $info['type'];
    }
    if($type === 'entity_reference' && !self::isMedia($field_definition)){
      // the field is normal entity reference
      $def = parent::prepareTarget($field_definition);
      $def->setPluginId('entity_reference');
      return $def;
    }else if(self::isMedia($field_definition)){
      // the field is media:
      $subFields = self::getSubFields($field_definition);
      $fields = array();
      foreach ($subFields as $id => $subField) {
        $target = self::callPrepareTarget($subField);
        if(!isset($target)){
          continue;
        }
        $target->setPluginId("entity_reference");
        // we set our important settings name like [plugin_id]_handler_settings,
        // so plugins like feeds_para_mapper can find these settings:
        $info = $info = $subField->media_feeds_info;
        $handler_settings = [
          'type' => $info['type'],
          'host_bundle' => $info['host']['bundle'],
          'host_field' => $info['host']['field']
        ];
        $subField->entity_reference_handler_settings = $handler_settings;
        $fields[] = $target;
      }
      if(count($fields)){
        $field_definition->sub_fields = $fields;
      }
      return FieldTargetDefinition::createFromFieldDefinition($field_definition);
    }else{
      if($field_definition instanceof BaseFieldDefinition){
        $def = self::callPrepareTarget($field_definition);
        $def->setPluginId("entity_reference");
        return $def;
      }
      return FieldTargetDefinition::createFromFieldDefinition($field_definition);
    }
  }

  /**
   * @param FieldDefinitionInterface $field
   * @return null
   */
  protected static function callPrepareTarget(FieldDefinitionInterface $field){
    $info = $field->media_feeds_info;
    $stop = null;
    $field_type = $info['type'];
    $plugin = $info['plugin'];
    if(!isset($field_type) || !isset($plugin)){
      return null;
    }
    $class = $plugin['class'];
    if($field instanceof FieldConfigInterface){
      $field->set('field_type', $info['type']);
    }
    $targetDef = $class::prepareTarget($field);
    $field_name = $field->getName();
    $label = $field->getLabel();
    $fracs = explode('(', $label);
    $old_label = $fracs[0];
    $target_bundle = $info['host']['bundle'];
    if(isset($info['host']['field'])){
      $host_field = $info['host']['field'];
      $old_label .= ' (' . $host_field . ':' .$target_bundle .':' . $field_name .')';
    }else{
      $old_label .= ' (' . $target_bundle .':' . $field_name .')';
    }
    $field->setLabel($old_label);
    if($field instanceof FieldConfigInterface){
      $field->set('field_type', self::$temp_type);
    }
    return $targetDef;
  }

  /**
   * @return null|object
   */
  public function createTargetInstance(){
    $info = $this->field->media_feeds_info;
    $plugin = $info['plugin'];
    $class = $plugin['class'];
    $target = $class::prepareTarget($this->field);
    $target->setPluginId($plugin['id']);
    $instance = null;
    try {
      $instance = $this->plugin_manager->createInstance($plugin['id'], $this->configuration);
    } catch (PluginException $e) {
    }
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration()
  {
    if($this->isMedia){
      $config = $this->targetInstance->defaultConfiguration();
      return $config;
    }else{
      return parent::defaultConfiguration();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state)
  {
    if($this->isMedia){
      $config = $this->targetInstance->buildConfigurationForm($form,$form_state);
      return $config;
    }else{
      return parent::buildConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
  {
    if($this->isMedia){
      $delta = $form_state->getTriggeringElement()['#delta'];
      $configuration = $form_state->getValue(['mappings', $delta, 'settings']);
      $this->targetInstance->submitConfigurationForm($form,$form_state);
      $this->setConfiguration($configuration);
    }
    parent::submitConfigurationForm($form,$form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary()
  {
    if($this->isMedia){
      $sum = null;
      if ($this->targetInstance instanceof ConfigurableTargetInterface) {
        $sum = $this->targetInstance->getSummary();
      }
      return $sum;
    }else{
      return parent::getSummary();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setTarget(FeedInterface $feed, EntityInterface $entity, $field_name, array $values)
  {
    if($this->isEmpty($values)){
      return null;
    }
    $target = $this->targetDefinition;
    $target = $target->getFieldDefinition();
    $field_name = $target->getName();
    if(!$this->isMedia){
      parent::setTarget($feed,$entity,$field_name,$values);
    }else{
      $info = $target->media_feeds_info;
      $mediaEntity = $this->createMediaEntity($entity, $info['host']['bundle'], $info['host']['field']);
      $mediaEntity->{$field_name} = NULL;
      if($mediaEntity->hasField($field_name)){
        //
      }else{
        $stop = null;
      }
      $this->targetInstance->setTarget($feed, $mediaEntity, $target->getName(), $values);
    }
  }
  public function isEmpty($values){
    $properties = $this->targetDefinition->getProperties();
    $emptyValues = 0;
    foreach ($values as $value) {
      $currentProperties = array_keys($value);
      $emptyProps = [];
      foreach ($properties as $property) {
        foreach ($currentProperties as $currentProperty) {
          if($currentProperty === $property){
            if(!strlen($value[$currentProperty])){
              $emptyProps[] = $currentProperty;
            }
          }
        }
      }
      if(count($emptyProps) === count($properties)){
        $emptyValues++;
      }
    }
    return $emptyValues === count($values);
  }
  /**
   * @param $host_entity
   * @param $bundle
   * @param $host_field
   * @return Media
   */
  public function createMediaEntity($host_entity, $bundle, $host_field){
    $value = $host_entity->get($host_field)->getValue();
    if(count($value)){
      $value = end($value);
      if(isset($value['entity'])){
        return $value['entity'];
      }
    }
    $media = $this->media_storage->create(["bundle" => $bundle, 'name' => 'Default name']);
    $host_entity->get($host_field)->setValue($media);
    return $media;

  }

}