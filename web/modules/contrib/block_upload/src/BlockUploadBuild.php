<?php

namespace Drupal\block_upload;

use Drupal\file\Entity\File;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Component\Utility\Bytes;
use Drupal\user\Entity\User;

/**
 * BlockUploadBuild class.
 */
class BlockUploadBuild {

  /**
   * Builds form for removing the files with block.
   */
  public static function blockUploadRemoveForm($field_limit, $node, $field_name) {
    $field_images = $node->get($field_name);
    foreach ($field_images->getValue() as $field_image) {
      $file = File::load($field_image['target_id']);
      $uid = $file->get('uid')->target_id;
      $uploader = User::load($uid);
      global $base_url;
      $url = Url::fromUri($base_url . '/user/' . $uid, []);
      $uploader = $uploader ? Link::fromTextAndUrl($uploader->getDisplayName(), $url) : '';
      $options[$field_image['target_id']] = [
        [
          'data' => [
            '#type' => 'item',
            '#title' => $uploader ? $uploader->toString()->getGeneratedLink() : '',
          ],
        ],
        [
          'data' => [
            '#type' => 'item',
            '#title' => \Drupal::service('date.formatter')->format($file->getCreatedTime()),
          ],
        ],
        [
          'data' => [
            '#theme' => 'file_link',
            '#file' => (object) $file,
          ],
          'field_type' => $field_limit->getType(),
        ],
      ];

    }
    $header = [t('Uploader'), t('Created time'), t('File')];
    $form = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => t('No content available.'),
      '#attributes' => ['class' => ['delete-files']],
    ];
    return $form;
  }

  /**
   * Returns destinaton for file upload.
   *
   * @return string
   *   Destination path.
   */
  public static function blockUploadGetUploadDestination($field) {
    if ($destination = $field->getSetting('file_directory')) {
      if (\Drupal::request()->attributes->has('node')) {
        $node = \Drupal::request()->attributes->get('node');
      }
      $token = \Drupal::token();
      $destination = $token->replace($destination, ['node' => $node]);
    }
    $field_info = FieldStorageConfig::loadByName($field->get('entity_type'), $field->getName());
    $uri_scheme = $field_info->getSetting('uri_scheme');
    if (!$uri_scheme) {
      $uri_scheme = 'public';
    }
    $destination = $uri_scheme . '://' . $destination;
    file_prepare_directory($destination, FILE_CREATE_DIRECTORY);
    return $destination;
  }

  /**
   * Deletes files marked by checkbox in deletion form.
   */
  public static function blockUploadDeleteFiles($node, $field_name, &$values) {
    $delete_files = array_values($values['remove_files']);
    $count = 0;
    foreach ($node->get($field_name)->getValue() as $file_field) {
      if (in_array($file_field['target_id'], $delete_files)) {
        $node->get($field_name)->removeItem($count);
        file_delete($file_field['target_id']);
      }
      else {
        $count++;
      }

    }
    drupal_set_message(t('File(s) was successfully deleted!'));
  }

  /**
   * Returns validators array.
   *
   * @return array
   *   List of validators.
   */
  public static function blockUploadGetValidators($field_name, $fields_info, $node) {
    $settings = $node->get($field_name)->getSettings();
    $validators = [
      'file_validate_extensions' => [$settings['file_extensions']],
      'file_validate_size' => [Bytes::toInt($settings['max_filesize'])],
    ];
    $min_resolution = isset($settings['min_resolution']) ? $settings['min_resolution'] : NULL;
    $max_resolution = isset($settings['max_resolution']) ? $settings['max_resolution'] : NULL;
    if (isset($min_resolution) || isset($min_resolution)) {
      $validators['file_validate_image_resolution'] = [$max_resolution, $min_resolution];
      $validators['file_validate_image_min_resolution'] = [$min_resolution];
    }
    return $validators;
  }

}
