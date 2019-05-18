<?php

namespace Drupal\ert;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class ReadTimeManager.
 *
 * @package Drupal\ert
 */
class ReadTimeManager implements ReadTimeManagerInterface {
  
  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  
  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;
  
  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;
  
  /**
   * Constructor for ReadTimeManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $tagPluginManager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->config = \Drupal::config('ert.settings');
  }
  
  /**
   * {@inheritDoc}
   * @see \Drupal\ert\ReadTimeManagerInterface::getReadTime()
   */
  public function getReadTime(EntityInterface $entity){
    $defaults = $this->getDefaultSettings();
    
    // Get entity info.
    $entity_type_id = $entity->getEntityTypeId();
    $bundle_id = $entity->bundle();

    // Get read time bundle settings.
    $wpm = ($this->config->get($entity_type_id. '__' . $bundle_id . '_wpm')) ? $this->config->get($entity_type_id. '__' . $bundle_id . '_wpm'): $defaults['wpm'];
    $format = ($this->config->get($entity_type_id. '__' . $bundle_id . '_format')) ? $this->config->get($entity_type_id. '__' . $bundle_id . '_format'): $defaults['format'];
    $display = ($this->config->get($entity_type_id. '__' . $bundle_id . '_display')) ? $this->config->get($entity_type_id. '__' . $bundle_id . '_display'): $defaults['display'];
    
    // Get fields to calculate read time of.
    $field_words = $this->concatenateFields($entity);
    
    // Calculate read time.
    $words = str_word_count($field_words);
    $time = $words / $wpm;
    
    // Format read time.
    if (in_array($format, array('hour_short', 'hour_long'))) {
      $hours = floor($time / 60);
      $minutes = ceil(fmod($time, 60));
    } else {
      $minutes = ceil($time);
    }
    if (in_array($format, array('hour_long', 'min_long'))) {
      $hour_suffix = 'hour';
      $min_suffix = 'minute';
    } else {
      $hour_suffix = 'hr';
      $min_suffix = 'min';
    }
    $minute_format = t('@m @suffix', array('@m' => $minutes, '@suffix' => $min_suffix));
    if (!empty($hours)) {
      $hour_format = t('@m @suffix', array('@m' => $hours, '@suffix' => $hour_suffix));
      $read_time = t('@h, @m', array('@h' => $hour_format, '@m' => $minute_format));
    } else {
      $read_time = $minute_format;
    }
    
    return str_replace('%read_time', $read_time, $display);
  }
  
  /**
   * Returns default settings
   * 
   * @return array
   *    Default read time settings
   */
  private function getDefaultSettings(){
    return [
        'fields' => [],
        'wpm' => '225',
        'format' => 'hour_short',
        'display' => t('Read time: %read_time'),
    ];
  }
  
  /**
   * Returns concatenated read time enabled field values.
   * 
   * @param EntityInterface $entity
   * @return string
   */
  private function concatenateFields(EntityInterface $entity){
    $concatenated_words = '';
    
    // Get read time entity enabled fields.
    $enabled_fields = $this->config->get($entity->getEntityTypeId(). '__' . $entity->bundle(). '_enabled_entity_fields');
    
    // Entity view builder
    $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
    $display = entity_get_display($entity->getEntityTypeId(), $entity->bundle(), 'default');
    
    // Loop through read time enabled fields 
    if($enabled_fields){
      foreach ($enabled_fields as $field_name){
        
        foreach ($entity->{$field_name} as $item){
          
          $field_type = $item->getFieldDefinition()->getType();
          
          if($field_type == 'entity_reference_revisions'){
            // Recursive call to concatenateFields()           
            $concatenated_words .= $this->concatenateFields($item->entity);
          } elseif (in_array($field_type, ['string', 'text', 'text_long'])){
            // Render entity field
            $entity_field_renderable_array = $view_builder->viewFieldItem($item, $display->getComponent($field_name));
            // Clean rendered field. Removes html tags
            $concatenated_words .= ' ' . trim(strip_tags(render($entity_field_renderable_array)));
          }
        }
      }
    }
    
    return $concatenated_words;
  }
}