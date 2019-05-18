<?php

namespace Drupal\parrafos\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * Provides a 'ParrafosLayoutBlock' block.
 *
 * @Block(
 *  id = "parrafos_layout_block",
 *  admin_label = @Translation("Parrafos layout block"),
 * )
 */
class ParrafosLayoutBlock extends BlockBase implements ContainerFactoryPluginInterface {
  const PARRAFOS_DESCRIPTION = 'parrafos_description';
  
  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * Drupal\Core\Entity\EntityFieldManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;
  
  /**
   * @var object $currentEntity
   */
  protected $currentEntity;
  
  /**
   * Constructs a new ParrafosLayoutBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager, 
	EntityFieldManagerInterface $entity_field_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->currentEntity = $this->loadEntity();
  }
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
          ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form['items'] = [
      '#type' => 'select',
      '#title' => $this->t('Items'),
      '#options' => $this->listOfFields(),
      '#size' => 5,
      '#weight' => '0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

    $this->configuration['items'] = explode('.', $form_state->getValue('items'));
    // Create this semaphoro to control when to build or not the block.
    // Drupal reload several times the
    $this->configuration['semaphoro'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    
    if(is_array($this->configuration['items']) && isset($this->configuration['semaphoro'])){
      $values = NULL;
      list ($fieldName, $fieldDelta) =  $this->configuration['items'];
      
      $paragraph = $this->getParagraph($fieldName, $fieldDelta);
      $wadus = '';
      // Build the render array.
      /** @var \Drupal\Core\Entity\EntityViewBuilder $view_builder */
      $view_builder = $this->entityTypeManager->getViewBuilder($paragraph->getEntityTypeId());
      $build = $view_builder->view($paragraph, 'default');
  
      // Add geysir contextual links.
      /*
      if (function_exists('geysir_contextual_links')) {
        $link_options = [
          'parent_entity_type' => $this->currentEntity->getEntityType()->id(),
          'parent_entity' => $this->currentEntity->id(),
          'field' => $fieldName,
          'field_wrapper_id' => Html::getUniqueId('geysir--' . $fieldName),
          'delta' => $fieldDelta,
          'js' => 'nojs',
          'paragraph' => $paragraph->id(),
        ];
        $build['#geysir_field_paragraph_links'] = geysir_contextual_links($link_options);
        $build['#theme_wrappers'][] = 'geysir_field_paragraph_wrapper';
        $build['#attributes']['data-geysir-field-paragraph-field-wrapper'] = $link_options['field_wrapper_id'];
      }
      */
  
      // Set the cache data appropriately.
      // CacheableMetadata::createFromObject($this->currentEntity)->applyTo($build);
      unset($this->configuration['semaphoro']);
      return $build;
  
      }
      
    
    
    $build = [];
    $build['parrafos_layout_block_items']['#markup'] = '<p> texto  dummie</p>';

    return $build;
  }
  
  
  /**
   * @return array
   */
  private function getArgsFromUrl() {
    $current_path = explode ('/', \Drupal::service('path.current')->getPath());
    $args = [];
    
    if(count($current_path) == 9) {
      $args =   explode('.',$current_path[5]);
      
    } elseif (count($current_path) == 4) {
      $args[0] = $current_path[1];
      $args[1] = $current_path[2];
    } elseif (count($current_path == 3)) {
      $args[0] = $current_path[1];
      $args[1] = $current_path[2];
    }
    
    return $args;
  }
  
  
  /**
   * @return \Drupal\Core\Entity\EntityInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function loadEntity(){
    $args = $this->getArgsFromUrl();
    // Get a node storage object.
    $entity_storage = $this->entityTypeManager->getStorage($args[0]);
    // Load a single node.
    $entity = $entity_storage->load($args[1]);
    // return entity object
    return $entity;
  }
  
  /**
   * This method get the list of fields that they are paragraphs in the entity
   * @param $entity
   * @param $bundle
   * @return array
   */
  private function getParagraphsFields($entity , $bundle) {
    $definitions = $this->entityFieldManager->getFieldDefinitions($entity, $bundle);
    $field_name = array_keys($definitions);

    $paragraphs_field = [];
    foreach ($field_name as $name) {
      if (isset($definitions[$name]->getSettings()['target_type']) && $definitions[$name]->getSettings()['target_type'] == 'paragraph') {
        $paragraphs_field[] = $name;
      }
    }
    return $paragraphs_field;
  }
  
  
  /**
   * This method get the list of options to be choosen in the config form block
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function listOfFields() {
    $entity = $this->currentEntity;
    $fields = $this->getParagraphsFields($entity->getEntityTypeId(),$entity->bundle());
    $listOfFields = [];
    
    foreach ($fields as $field) {
      $references = $entity->get($field)->referencedEntities();
      foreach ($references as $key => $reference) {
        $listOfFields[ $field . '.' . $key ] = $reference->get('parrafos_description')->getValue()[0]['value'];
      }
    }
    return $listOfFields;
  }
  
  /**
   * @param $fieldName
   * @param $fieldDelta
   */
  private function getParagraph($fieldName, $fieldDelta){
    $entity = $this->currentEntity;
    $referenced_entities = $entity->get($fieldName)->referencedEntities();
    if (isset($referenced_entities[$fieldDelta])) {
      $paragraph = $referenced_entities[$fieldDelta];
    }
  
    if (!$paragraph) {
      return NULL;
    }
    return $paragraph;
  }

}
