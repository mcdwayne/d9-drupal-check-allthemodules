<?php

namespace Drupal\image_export_import;

use Drupal\node\Entity\Node;
use Drupal\Component\Utility\Html;
use Drupal\node\Entity\NodeType;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;

/**
 * Service for Deleting the entity.
 */
class EntitySaveHelper {

  /**
   * Drupal File System.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $entityTypeManager;

  /**
   * Drupal File System.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Drupal\Core\Entity\EntityFieldManager definition.
   *
   * @var Drupal\Core\Entity\Query\QueryFactory
   */
  protected $fieldManager;

  /**
   * Constructor for \Drupal\image_export_import\EntitySaveHelper class.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity type manager.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The Form Builder.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entityQuery
   *   The Query Builder.
   * @param \Drupal\Core\Entity\EntityFieldManager $fieldManager
   *   The Field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileSystem $file_system, QueryFactory $entityQuery, EntityFieldManager $fieldManager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->entityQuery = $entityQuery;
    $this->fieldManager = $fieldManager;
  }

  /**
   * Get all content types from CMS.
   */
  public function getAllContentTypes() {
    $contentTypes = NodeType::loadMultiple();

    $contentTypesList = [];
    foreach ($contentTypes as $contentType) {
      $contentTypesList[$contentType->id()] = $contentType->label();
    }
    return $contentTypesList;
  }

  /**
   * Get file handle from uri.
   *
   * @param string $uri
   *   URI of file.
   *
   * @return resource
   *   Resource handler.
   */
  public function getFileHandler($uri) {
    return fopen($this->fileSystem->realpath($uri), "r");
  }

  /**
   * Batch finish callback.
   */
  public static function importImageFromCsvFinishedCallback($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      // Here we could do something meaningful with the results.
      // We just display the number of nodes we processed.
      $messenger->addMessage(t('@count results processed.', ['@count' => $results]));
    }
    else {
      $messenger->addMessage(t('Finished with an error.'));
    }
  }

  /**
   * Return csv data.
   *
   * @param resource $handle
   *   Resource handler.
   *
   * @return array
   *   Array of csv row.
   */
  public function getCsvData($handle) {
    return fgetcsv($handle, 0, ',', '"');
  }

  /**
   * Returns image fields based on content type.
   *
   * @param mixed $bundle
   *   Content type name.
   * @param mixed $entity_type_id
   *   This will contains nid.
   */
  public function getAllImageFields($bundle, $entity_type_id) {
    $bundleFields = [];
    foreach ($this->fieldManager->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
      // Get only image type fields.
      if (!empty($field_definition->getTargetBundle()) && $field_definition->getType() == 'image') {
        // $bundleFields[$field_name]['type'] = $field_definition->getType();
        $bundleFields[$field_name] = $field_definition->getLabel();
      }
      // Media fields from content types.
      if (!empty($field_definition->getSettings()['target_type']) && $field_definition->getSettings()['target_type'] == 'media') {
        $taget_bundle = key($field_definition->getSettings()['handler_settings']['target_bundles']);
        foreach ($this->fieldManager->getFieldDefinitions('media', $taget_bundle) as $field_mname => $target_mfield) {
          if (!empty($target_mfield->getTargetBundle()) && $target_mfield->getType() == 'image') {
            $bundleFields["media-" . $target_mfield->getTargetBundle() . "|" . $field_mname . "|" . $field_name] = "Media: " . $field_definition->getLabel();
          }
        }
      }
    }
    return $bundleFields;
  }

  /**
   * Write data in csv file to export functionality.
   *
   * @param mixed $filename
   *   Name of csv file.
   * @param mixed $content_types
   *   Content type name.
   * @param mixed $image_fields_array
   *   Image field name.
   * @param mixed $export_body
   *   Check user want to export body summary or not.
   *
   * @return mixed
   *   Return csv file url.
   */
  public function createCsvFileExportData($filename, $content_types, $image_fields_array, $export_body = NULL) {
    $image_fields = (strpos($image_fields_array[0], 'media-') !== FALSE) ? $image_fields_array[2] : $image_fields_array[0];
    // Get file path in CMS.
    $path = $this->fileSystem->realpath("public://$filename");
    $file = fopen($path, 'w');
    // Send the column headers.
    if (!empty($export_body)) {
      fputcsv($file, [
        'Nid',
        "Node_" . $content_types . '_Title',
        $image_fields, 'IMG_Alt',
        'IMG_title',
        'Node_Summary',
        'Node_Description',
      ]);
    }
    else {
      fputcsv($file, [
        'Nid',
        "Node_" . $content_types . '_Title',
        $image_fields,
        'IMG_Alt',
        'IMG_title',
      ]);
    }
    // Create migrate_images if not exists.
    if (!is_dir($this->fileSystem->realpath("public://migrate_images"))) {
      mkdir($this->fileSystem->realpath("public://migrate_images"), 0777, TRUE);
      chmod($this->fileSystem->realpath("public://migrate_images"), 0777);
    }
    // Sample data. This can be fetched from mysql too.
    $nids = \Drupal::entityQuery('node')->condition('type', $content_types)->execute();
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
    foreach ($nodes as $node) {
      $row = $img_title = $alt = $basename = [];
      $row[0] = $node->get('nid')->value;
      $row[1] = $node->get('title')->value;
      // Check image exits or not.
      if (!empty($node->get($image_fields)->target_id)) {
        // Get Media image Url, Alt, Title.
        $media_info = $this->getMediaInformation($node, $image_fields_array);
        // Concate image values for multiple fields.
        $row[2] = implode("|", $media_info['basename']);
        $row[3] = implode("|", $media_info['alt']);
        $row[4] = implode("|", $media_info['title']);
      }
      if (!empty($export_body)) {
        $row[5] = ($node->get('body')->summary) ? $node->get('body')->summary : '';
        $row[6] = ($node->get('body')->value) ? $node->get('body')->value : '';
      }
      // Write each record in csv file.
      fputcsv($file, $row);
    }
    fclose($file);
    return $path;
  }

  /**
   * Get media information from fields.
   *
   * @param mixed $node
   *   Node object.
   * @param mixed $image_fields_array
   *   Image field array.
   *
   * @return mixed
   *   Return media array
   */
  public function getMediaInformation($node, $image_fields_array) {
    $image_index = 0;
    $final_array = [];
    $image_fields = (strpos($image_fields_array[0], 'media-') !== FALSE) ? $image_fields_array[2] : $image_fields_array[0];
    foreach ($node->get($image_fields)->getValue() as $image_val) {
      if (strpos($image_fields_array[0], 'media-') !== FALSE) {
        $media_obj = Media::load($image_val['target_id']);
        $img_target = $media_obj->get($image_fields_array[1])->first()->get('target_id')->getString();
        // $this->getImageAltTitle($img_target, $media_obj, $image_index);.
        $basename[$image_index] = basename($this->customGetFileUri($img_target));
        // Copy files under migrate_images directory.
        $this->copyImageFiles($img_target);
        $alt[$image_index] = !empty($img_target) ? $media_obj->get($image_fields_array[1])->first()->get('alt')->getString() : '';
        ;
        $img_title[$image_index] = !empty($img_target) ? $media_obj->get($image_fields_array[1])->first()->get('title')->getString() : '';
      }
      else {
        $basename[$image_index] = basename($this->customGetFileUri($image_val['target_id']));
        // Copy files under migrate_images directory.
        $this->copyImageFiles($image_val['target_id']);
        $alt[$image_index] = !empty($image_val['alt']) ? $image_val['alt'] : '';
        $img_title[$image_index] = !empty($image_val['title']) ? $image_val['title'] : '';
      }
      $image_index++;
    }
    $final_array['basename'] = $basename;
    $final_array['alt'] = $alt;
    $final_array['title'] = $img_title;
    return $final_array;
  }

  /**
   * Get file url from target id.
   *
   * @param mixed $file_id
   *   File id to load file.
   *
   * @return mixed
   *   Return File object.
   */
  public function customGetFileUri($file_id) {
    if (!empty($file_id)) {
      $file = File::load($file_id);
      if (!empty($file)) {
        $uri = $file->getFileUri();
      }
    }
    return $uri;
  }

  /**
   * Copy files from one to another location.
   *
   * @param mixed $target_id
   *   Target id of image to copy file from one to anther location.
   */
  public function copyImageFiles($target_id) {
    $src = $this->fileSystem->realpath($this->customGetFileUri($target_id));
    $dest = $this->fileSystem->realpath("public://migrate_images");
    shell_exec("cp -r $src $dest");
  }

  /**
   * Get orphan files from CMS.
   */
  public function deleteOrphanFiles() {
    // Create database connection.
    $query = \Drupal::database()
      ->select('file_managed', 'fm')
      ->fields('fm', ['fid'])
      ->fields('fu', ['fid']);
    $query->addJoin('left', 'file_usage', 'fu', 'fm.fid=fu.fid');
    $query->condition('fu.fid', NULL, 'IS');
    $data = $query->execute();
    // Get all images from database.
    $results = $data->fetchAll(\PDO::FETCH_OBJ);
    foreach ($results as $row) {
      // Remove file from system.
      file_delete($row->fid);
    }
  }

  /**
   * This function runs the batch processing and creates terms.
   */
  public static function importImageFromCsv($row, &$context) {
    // Public static function importImageFromCsv($row, &$context) {.
    $operation_details = ' imported successfully.';
    // Check media info.
    $media_info = explode('|', $row['image_field']);
    $body_value = !empty($row['data'][6]) ? html_entity_decode($row['data'][6]) : '';
    $body_summary = !empty($row['data'][5]) ? html_entity_decode($row['data'][5]) : '';
    // Check title and image name field not empty in csv file.
    if (!empty($row['data'][0]) && !empty($row['data'][2])) {
      // Check exiting node in CMS.
      $nid = self::checkExitingNode($row['data'][0], $row['content_type'], 'nid');
      $node = Node::load($nid);
      // Update node title and body after user confirmation.
      if (!empty($node)) {
        $node->set('title', Html::escape($row['data'][1]));
        if (!empty($row['save_body']) && !empty($body_value)) {
          $node->set('body', [
            'value' => $body_value,
            'summary' => $body_summary,
            'format' => 'full_html',
          ]
          );
        }
        // Check image/media name in CMS.
        if (strpos($media_info[0], 'media-') !== FALSE) {
          $media_image = @self::uploadOffersMedia($row['data'][2], $row['data'][3], $row['data'][4], $media_info);
          // Check image exists or not.
          @self::updateExistingNode($row, $node, $media_image, $media_info);
        }
        else {
          $media_image = @self::uploadOffersMedia($row['data'][2], $row['data'][3], $row['data'][4]);
          // Check image exists or not.
          @self::updateExistingNode($row, $node, $media_image);
        }
      }
    }
    elseif ((empty($row['data'][0]) && !empty($row['data'][1])) && !empty($row['new_node'])) {
      // Update image field for offer data.
      $node_object = @self::checkExitingNode($row['data'][1], $row['content_type'], 'title');
      if (strpos($media_info[0], 'media-') !== FALSE && !empty($row['data'][2]) && empty($node_object)) {
        $media_image = @self::uploadOffersMedia($row['data'][2], $row['data'][3], $row['data'][4], $media_info);
        // Create new node.
        @self::createNewNode($row, $media_image, $media_info);
      }
      elseif (!empty($row['data'][2]) && empty($node_object)) {
        $media_image = @self::uploadOffersMedia($row['data'][2], $row['data'][3], $row['data'][4]);
        // Create new node.
        @self::createNewNode($row, $media_image);
      }
    }
    $context['message'] = t('Running Batch "@id" @details', ['@id' => $row['data'][1], '@details' => $operation_details]
    );
    $context['results'] = $row['result'];
  }

  /**
   * Update Existing node.
   *
   * @param mixed $row
   *   Get csv row.
   * @param mixed $node
   *   Node object.
   * @param mixed $media_image
   *   Image media object.
   * @param mixed $media_info
   *   Media info array.
   */
  public static function updateExistingNode($row, $node, $media_image, $media_info = NULL) {
    // Check image exists or not.
    if (!empty($media_image)) {
      // Update image field in content type.
      $image_field_name = !empty($media_info) ? $media_info[2] : $row['image_field'];
      $node->{$image_field_name}->setValue($media_image);
    }
    // Save node object with updated value.
    $node->save();
  }

  /**
   * Create new node based on requirements.
   *
   * @param mixed $row
   *   Get csv data.
   * @param mixed $media_image
   *   Get Image object.
   * @param mixed $media_info
   *   Get Media array.
   *
   * @return mixed
   *   return node object.
   */
  public static function createNewNode($row, $media_image, $media_info = NULL) {
    $body_value = !empty($row['data'][6]) ? html_entity_decode($row['data'][6]) : '';
    $body_summary = !empty($row['data'][5]) ? html_entity_decode($row['data'][5]) : '';
    $image_field_name = $row['image_field'];
    $content_type = $row['content_type'];
    if (!empty($media_image)) {
      $node = Node::create(
          [
            'type' => $content_type,
            'title' => Html::escape($row['data'][1]),
            'body' => [
              'value' => $body_value,
              'summary' => $body_summary,
              'format' => 'full_html',
            ],
          ]
      );
      // Check media as well as normal image field.
      empty($media_info) ? $node->{$image_field_name}->setValue($media_image) : $node->{$media_info[2]}->setValue($media_image);

      $node->save();
    }
  }

  /**
   * Check exiting node in CMS.
   *
   * @param mixed $item_code
   *   Nid is here.
   * @param mixed $node_type
   *   Node type name.
   * @param mixed $field_name
   *   Image field name.
   *
   * @return mixed
   *   Return nid
   */
  public static function checkExitingNode($item_code, $node_type, $field_name) {
    $nodes = \Drupal::entityQuery('node')->condition('type', $node_type)->condition($field_name, $item_code, 'IN')->execute();
    $nid = key($nodes);
    if (!empty($nodes)) {
      return $nodes[$nid];
    }
  }

  /**
   * Create and update Media Image content.
   *
   * @param mixed $file_name
   *   Name of image file.
   * @param mixed $alt
   *   Alt tag of image.
   * @param mixed $title
   *   Title tag of image.
   * @param mixed $media_info
   *   Media info optional parameter.
   *
   * @return mixed
   *   Return Media object.
   */
  public function uploadOffersMedia($file_name, $alt, $title, $media_info = NULL) {
    // Get local image directory path. field_offer_media.
    $multi_image = explode('|', $file_name);
    $multi_alt = explode('|', $alt);
    $multi_title = explode('|', $title);
    if (count($multi_image) >= 1) {
      $i = 0;
      if (strpos($media_info[0], 'media-') !== FALSE) {
        foreach ($multi_image as $value) {
          $image_local_dir = \Drupal::service('file_system')->realpath("public://migrate_images");
          // Save Image in local from remote data.
          $data = file_get_contents($image_local_dir . "/" . $value);
          $media_bundle = explode('-', $media_info[0]);
          // Create Media and return media id.
          if (!empty($data)) {
            $file = file_save_data($data, "public://" . $value, FILE_EXISTS_RENAME);
            $media_obj = Media::create([
              'bundle' => $media_bundle[1],
              'name' => $value,
              $media_info[1] => [
                'target_id' => $file->id(),
                'alt' => !empty($multi_alt[$i]) ? Html::escape($multi_alt[$i]) : "",
                'title' => !empty($multi_title[$i]) ? Html::escape($multi_title[$i]) : "",
              ],
            ]);
            $media_obj->save();
            $media_image[$i] = $media_obj->id();
          }
          $i++;
        }
      }
      else {
        foreach ($multi_image as $value) {
          $image_local_dir = \Drupal::service('file_system')->realpath("public://migrate_images");
          // Save Image in local from remote data.
          $data = file_get_contents($image_local_dir . "/" . $value);
          if (!empty($data)) {
            $file = file_save_data($data, "public://" . $value, FILE_EXISTS_RENAME);
            // Check exiting media entity for this node.
            $media_image[$i] = [
              'target_id' => $file->id(),
              'alt' => !empty($multi_alt[$i]) ? Html::escape($multi_alt[$i]) : "",
              'title' => !empty($multi_title[$i]) ? Html::escape($multi_title[$i]) : "",
            ];
          }
          $i++;
        }
      }
      return $media_image;
    }
  }

  /**
   * Get an appropriate archiver class for the file.
   *
   * @param string $file
   *   The file path.
   */
  public function getArchiver($file) {
    $extension = strstr(pathinfo($file)['basename'], '.');
    switch ($extension) {
      case '.tar.gz':
      case '.tar':
        $this->archiver = new \PharData($file);
        break;

      case '.zip':
        $this->archiver = new \ZipArchive($file);
        $this->archiver->open($file);
      default:
        break;
    }
    return $this->archiver;
  }

}
