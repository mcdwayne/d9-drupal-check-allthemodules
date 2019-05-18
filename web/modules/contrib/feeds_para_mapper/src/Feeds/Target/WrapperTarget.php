<?php

namespace Drupal\feeds_para_mapper\Feeds\Target;


use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\feeds\Annotation\FeedsTarget;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Plugin\Type\FeedsPluginManager;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;
use Drupal\feeds_para_mapper\Mapper;
use Drupal\field\FieldConfigInterface;

/**
 * Defines a wrapper target around a paragraph bundle's target field.
 *
 * @FeedsTarget(
 *   id = "wrapper_target",
 *   field_types = {
 *     "entity_reference_revisions",
 *   },
 *   arguments = {
 *     "@messenger",
 *     "@plugin.manager.feeds.target",
 *     "@feeds_para_mapper.mapper",
 *   }
 * )
 */
class WrapperTarget extends FieldTargetBase implements ConfigurableTargetInterface
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
   * @var Mapper
   */
  protected $mapper;

  /**
   * @var FieldConfigInterface
   */
  protected $field;

  /**
   * @var FieldTargetBase
   */
  protected $targetInstance;

  public function __construct(array $configuration,
                              $plugin_id,
                              array $plugin_definition,
                              MessengerInterface $messenger,
                              FeedsPluginManager $plugin_manager,
                              Mapper $mapper)
  {
    $this->messenger = $messenger;
    $this->plugin_manager = $plugin_manager;
    $this->mapper = $mapper;
    $this->configuration = $configuration;
    $this->targetDefinition = $configuration['target_definition'];
    $this->field = $this->targetDefinition->getFieldDefinition();
    $this->targetInstance = $this->createTargetInstance();
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }


  /**
   * {@inheritdoc}
   */
  public static function targets(array &$targets, FeedTypeInterface $feed_type, array $definition)
  {
    $processor = $feed_type->getProcessor();
    $entity_type = $processor->entityType();
    $bundle = $processor->bundle();
    $mapper = \Drupal::service('feeds_para_mapper.mapper');
    $sub_fields = $mapper->getTargets($entity_type,$bundle);
    foreach ($sub_fields as $field) {
      $field->set('field_type', 'entity_reference_revisions');
      $wrapper_target = self::prepareTarget($field);
      if(!isset($wrapper_target)){
        continue;
      }
      $wrapper_target->setPluginId("wrapper_target");
      $path = $mapper->getInfo($field,'path');
      $last_host = end($path);
      $wrapper_target->setPluginId("wrapper_target");
      $id = $last_host['bundle'] ."_". $field->getName();
      $targets[$id] = $wrapper_target;
    }
  }
  public function createTargetInstance(){
    $mapper = $this->getMapper();
    $plugin = $mapper->getInfo($this->field,'plugin');
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
  public function setTarget(FeedInterface $feed, EntityInterface $entity, $field_name, array $values)
  {
    $empty = $this->isEmpty($values);
    if ($empty) {
      return;
    }
    $target = $this->targetDefinition;
    $target = $target->getFieldDefinition();
    $type = $this->mapper->getInfo($target,'type');
    $target->set('field_type', $type);
    try{
      $importer = \Drupal::service('feeds_para_mapper.importer');
      $importer->import($this->feedType, $feed, $entity, $target, $this->configuration, $values, $this->targetInstance);
    }catch (\Exception $exception){
      $this->messenger->addError($exception);
    }
  }

  /**
   * Checks whether the values are empty.
   *
   * @param array $values
   *   The values
   *
   * @return bool
   *   True if the values are empty.
   */
  public function isEmpty(array $values){
    $properties = $this->targetDefinition->getProperties();
    $emptyValues = 0;
    foreach ($values as $value) {
      $currentProperties = array_keys($value);
      $emptyProps = [];
      foreach ($properties as $property) {
        foreach ($currentProperties as $currentProperty) {
          if ($currentProperty === $property) {
            if (!strlen($value[$currentProperty])) {
              $emptyProps[] = $currentProperty;
            }
          }
        }
      }
      if (count($emptyProps) === count($properties)) {
        $emptyValues++;
      }
    }
    return $emptyValues === count($values);
  }

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    $mapper = \Drupal::service('feeds_para_mapper.mapper');
    $field_type = $mapper->getInfo($field_definition,'type');
    $plugin = $mapper->getInfo($field_definition,'plugin');
    $path = $mapper->getInfo($field_definition,'path');
    if(!isset($field_type) || !isset($plugin)){
      return null;
    }
    $class = $plugin['class'];
    $field_definition->set('field_type', $field_type);
    $targetDef = $class::prepareTarget($field_definition);
    $last_host = end($path);
    $label = $field_definition->getLabel();
    $label .= ' (' . $last_host['host_field'] .':' . $last_host['bundle'] . ":" . $field_definition->getName() . ')';
    $field_definition->set('label', $label);
    $field_definition->set('field_type','entity_reference_revisions');
    return $targetDef;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration()
  {
    $mapper = $this->getMapper();
    $config = $this->targetInstance->defaultConfiguration();
    $has_settings = $mapper->getInfo($this->field, 'has_settings');
    if($has_settings){
      $config['max_values'] = $mapper->getMaxValues($this->field, $this->configuration);
    }
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->targetInstance->buildConfigurationForm($form,$form_state);
    $has_settings = $this->mapper->getInfo($this->field,'has_settings');
    if ($has_settings) {
      $escaped = array('@field' => $this->field->getName());
      $des = $this->t('When @field field exceeds this number of values,
     a new paragraph entity will be created to hold the remaining values.', $escaped);
      $element = array(
        '#type' => 'textfield',
        '#title' => $this->t("Maximum Values"),
        '#default_value' => $this->configuration['max_values'],
        '#description' => $des,
      );
      $config['max_values'] = $element;
    }
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
  {
    $delta = $form_state->getTriggeringElement()['#delta'];
    $configuration = $form_state->getValue(['mappings', $delta, 'settings']);
    $this->targetInstance->submitConfigurationForm($form,$form_state);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary()
  {
    $mapper = $this->getMapper();
    $sum = null;
    if ($this->targetInstance instanceof ConfigurableTargetInterface) {
      $sum = $this->targetInstance->getSummary();
    }
    $has_settings = $mapper->getInfo($this->field, 'has_settings');
    $final_str = $sum;
    if ($has_settings) {
      $temp_sum = "Maximum values: " . $this->configuration['max_values'];
      if(isset($sum) && $sum instanceof TranslatableMarkup) {
        $final_str = $sum->getUntranslatedString();
        $final_str .= "<br>" . $temp_sum;
        $args = $sum->getArguments();
        if (isset($args)) {
          $final_str = $this->t($final_str, $args);
        }
        else {
          $final_str = $this->t($final_str);
        }
      }
      else {
        $final_str = $sum . "<br>" . $this->t($temp_sum);
      }
    }
    return $final_str;
  }
  /**
   * Gets the mapper object.
   *
   * @return Mapper
   */
  private function getMapper(){
    if(isset($this->mapper)){
      return $this->mapper;
    }
    return \Drupal::service('feeds_para_mapper.mapper');
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies()
  {
    $this->dependencies = parent::calculateDependencies();
    // Add the configured field as a dependency.
    $field_definition = $this->targetDefinition
      ->getFieldDefinition();
    // We need to add all parent fields as dependencies
    $fields = $this->mapper->loadParentFields($field_definition);
    $fields[] = $field_definition;
    foreach ($fields as $field) {
      if ($field && $field instanceof EntityInterface) {
        $this->dependencies['config'][] = $field->getConfigDependencyName();
      }
    }
    return $this->dependencies;
  }


  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    return parent::onDependencyRemoval($dependencies);
  }

}