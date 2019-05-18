<?php

namespace Drupal\dtuber\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\file\Entity\File;

/**
 * Plugin implementation of 'Dtuber Field' field type.
 *
 * @FieldType(
 *   id = "dtuber_field",
 *   label = @Translation("Dtuber - Upload to YouTube"),
 *   description = @Translation("Uploads videos to YouTube"),
 *   category = @Translation("Media"),
 *   default_widget = "dtuber_field_default_widget",
 *   default_formatter = "dtuber_field_default_formatter",
 * )
 */
class DtuberField extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        // reference: http://drupal.stackexchange.com/questions/13211/database-schema-for-image-field
        // FID to store managed_file in db.
        'fid' => array(
          'type' => 'int',
          'not null' => FALSE,
        ),
        // reference: http://drupal.stackexchange.com/questions/87962/which-type-to-use-for-checkbox-fields-in-hook-field-schema
        // file_uploaded_to_youtube : yes/no.
        'yt_uploaded' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => FALSE,
          'default' => 0,
        ),
        // youtube_videoid : youtube VIDEO ID.
        'yt_videoid' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['fid'] = DataDefinition::create('integer')->setLabel(t('Upload Video'));
    $properties['yt_uploaded'] = DataDefinition::create('integer')->setLabel(t('Video uploaded to YouTube? 1=y/0=n'));
    $properties['yt_videoid'] = DataDefinition::create('string')->setLabel(t('YouTube Video ID'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $fid = $this->get('fid')->getValue();
    $vid = $this->get('yt_videoid')->getValue();
    // If none of fid or youtube VId is present then it is considered empty.
    return ($fid === NULL && ($vid === '' || $vid === NULL));
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($data) {
    // kint($this);
    $entity = $this->getEntity();
    $field_id = $this->getParent()->getName();
    $field_val = $entity->get($field_id)->getValue()[0];
    $file = $field_val['fid'];
    $file = File::load($file);
    if ($field_val && isset($file)) {
      // If file is there.
      $path = file_create_url($file->getFileUri());
      global $base_url;
      $dtuberYouTubeService = \Drupal::service('dtuber_youtube_service');
      $options = array(
        'path' => str_replace($base_url, '', $path),
        'title' => $entity->label(),
        // Data sources required for description & tags fields.
        'description' => $entity->label(),
        'tags' => [],
      );

      // Check if video is already uploaded.
      if ($field_val['yt_uploaded'] != 1) {
        // Send a video upload request to.
        $video = $dtuberYouTubeService->uploadVideo($options);
        if ($video['status'] === 'OK') {
          // If upload successful.
          // update field.
          $value = [
            'fid' => $field_val['fid'],
            // If youtube Id Isn't set.
            'yt_videoid' => $video['video_id'],
            'yt_uploaded' => 1,
          ];
          // Update field here.
          $this->setValue($value);
          drupal_set_message($video['message']);
        }
        else {
          $value = [
            'yt_videoid' => NULL,
            'yt_uploaded' => 0,
          ];
          $this->setValue($value);
          drupal_set_message($this->t('Unable to Upload video to YouTube.'));
        }
      }
      else {
        // YouTube video Id already exists.
      }
    }
    else {
      // When fid is empty... remove any extra video ids and uploaded flag.
      $value = [
        'fid' => NULL,
        'yt_videoid' => NULL,
        'yt_uploaded' => 0,
      ];
      $this->setValue($value);
    }
    return TRUE;
  }

}
