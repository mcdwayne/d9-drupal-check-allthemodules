<?php

namespace Drupal\trending_images;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\trending_images\Plugin\Field\FieldType\TrendingImages;

class TrendingImagesService {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager|\Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * TrendingImagesService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManager $entity_field_manager, EntityTypeBundleInfo $entityTypeBundleInfo) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  public function schemaUpdate() {
    // fetch all entites with field type: trending images
    $trendingImagesFields = $this->getEntitiesByFieldType('trending_images');
    foreach ($trendingImagesFields as $field){
      $node_table = 'node__'.$field['field_machine_name'];
      $node_revision = 'node_revision__'.$field['field_machine_name'];
      $schema = Database::getConnection()->schema();
      $columns = TrendingImages::getColumns();

      foreach ($columns as $key => $spec) {
        $node_exists = $schema->fieldExists($node_table, $field['field_machine_name'].'_'.$key);
        if(!$node_exists) {
          $schema->addField($node_table, $field['field_machine_name'].'_'.$key, $spec);
        }
        $revision_exists = $schema->fieldExists($node_revision, $field['field_machine_name'].'_'.$key);
        if(!$revision_exists) {
          $schema->addField($node_revision, $field['field_machine_name'].'_'.$key, $spec);
        }
      }
    }
  }

  public function processTrendingFields(){
    $trendingImagesFields = $this->getEntitiesByFieldType('trending_images');
    foreach ($trendingImagesFields as $field){
      $entityTypeManager = $this->entityTypeManager->getStorage($field['entity_type']);
      // COMPARE WITH LAST FIELD CRON RUN
      $lastCronRun = \Drupal::state()->get('last_trending_images_cron_run');
      if($field['config_settings']['interval'] < \Drupal::state()->get('last_trending_images_cron_run') || !isset($lastCronRun)){
      $entityFieldQuery = \Drupal::entityQuery($field['entity_type'])
        ->condition('type', $field['bundle_name']);
      $entityFieldResult = $entityFieldQuery->execute();

      $fieldSettings = [];
      foreach ($field['config_settings']['providers'] as $socialNetworkKey => $socialNetworkState){
        if($socialNetworkState != null){
          $fieldSettings[] = array(
            'source' => $socialNetworkKey,
            'field_machine_name' => $field['field_machine_name'],
            'file_directory' => $field['config_settings']['file_directory'],
            'upload_location' => $field['config_settings']['upload_radios']
          );
        }
      }

      // Fetching and saving into entities
      $images = TrendingImages::fetchTrendingImages($fieldSettings, $field['cardinality']);
      foreach ($entityFieldResult as $entityWithTrendingImagesField){
        $loadedEntity = $entityTypeManager->load($entityWithTrendingImagesField);
        $loadedEntities[] = $loadedEntity;
        $fieldValues = $loadedEntity->get($field['field_machine_name'])->getValue();

        foreach($fieldValues as $valueKey => $value){
          if($value['permanent'] == 0){
            $singleImage = array_shift($images);
            $savedImage = $singleImage['image'];
            if($savedImage != null){
              $fieldValues[$valueKey]['target_id'] = $savedImage->id();
              $fieldValues[$valueKey]['source_link'] = $singleImage['link'];
              $fieldValues[$valueKey]['likes'] = $singleImage['likes'];
              $fieldValues[$valueKey]['comments'] = $singleImage['comments'];
              $fieldValues[$valueKey]['width'] = $singleImage['width'];
              $fieldValues[$valueKey]['height'] = $singleImage['height'];
              $fieldValues[$valueKey]['permanent'] = $singleImage['permanent'];
              $fieldValues[$valueKey]['value'] = $singleImage['channel'];

              $loadedEntity->set($field['field_machine_name'],$fieldValues);
              $loadedEntity->save();
            }else{
              drupal_set_message('Trending images fetch could not provide sufficient feed. (Attempted fetch for more images then it exists on social channel)', 'warning');
              continue;
            }
          }
        }
      }

        \Drupal::state()->set('last_trending_images_cron_run', \Drupal::time()->getCurrentTime());
      }
    }
  }

  public function getEntitiesByFieldType($fieldType){
    $all_bundles_info = $this->entityTypeBundleInfo->getAllBundleInfo();

    /* @var \Drupal\Core\Config\Entity\ConfigEntityType $configEntityType */
    foreach($all_bundles_info as $entity => $bundle_array) {
      $entity_info = $this->entityTypeManager->getDefinition($entity);
      if ($entity_info->isSubclassOf(FieldableEntityInterface::class)) {
        foreach ($bundle_array as $bundle_name => $bundle) {
          $base_fields = $this->entityFieldManager->getFieldDefinitions($entity, $bundle_name);
          foreach ($base_fields as $field) {
            if($field->getType() == $fieldType){
              $collectedFieldData[] = array(
                'entity_type' => $entity,
                'bundle_name' => $bundle_name,
                'field_machine_name' => $field->getName(),
                'cardinality' => $field->getFieldStorageDefinition()->getCardinality(),
                'config_settings' => $field->getSettings()
              );
            }
          }
        }
      }
    }
    return $collectedFieldData;
  }

  public function getEnabledTrendingImagesPlugins(){
    $trendingImagesFields = $this->getEntitiesByFieldType('trending_images');
    $enabledProviders = [];
    foreach ($trendingImagesFields as $field){
      foreach($field['config_settings']['providers'] as $providerIdentification => $provider){
        if($provider['enable'] == 1 && !in_array($providerIdentification, $enabledProviders, true)){
          $enabledProviders[] = $providerIdentification;
        }
      }
    }
    return $enabledProviders;
  }

  /**
   * Fetched images from URL provided by API.
   * Saves them and returns them.
   *
   * @param \stdClass $imageDataArray
   * @return \Drupal\file\FileInterface|false
   */
  // TODO: NOT FUNCTIONING CORRECTLY IN CASE OF PRIVATE
  public function fetchImageFromUlr(\stdClass $imageDataArray, $settings){
    $pathToSave = $settings[0]['upload_location'].'://'.$settings[0]['file_directory'];
    file_prepare_directory($pathToSave, FILE_CREATE_DIRECTORY);
    $imageCode = file_get_contents($imageDataArray->url);
    if($imageCode != FALSE){
      $imageName = $settings[0]['source'].'_img_'. time();
      $image = file_save_data($imageCode,$pathToSave .'/'. $imageName  . '.jpg');
    }
    return $image;
  }
}
