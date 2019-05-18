<?php

namespace Drupal\file_management;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\field\Entity\FieldConfig;

/**
 * File Management helper methods.
 */
class FileManagement implements FileManagementInterface {

  /**
   * Generates a render array with file information from the given file entity.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity from which to get the information from.
   *
   * @return array
   *   The render array with the file information.
   */
  public static function getFileInformation(FileInterface $file) {
    // Build a link to the file.
    $link = Link::fromTextAndUrl(
      $file->getFilename(),
      Url::fromUri(file_create_url($file->getFileUri()))
    )->toRenderable();
    $link = \Drupal::service('renderer')->render($link);

    // Get owner information.
    $owner = [
      '#theme' => 'username',
      '#account' => $file->getOwner(),
    ];
    $owner = \Drupal::service('renderer')->render($owner);

    // Build the render array.
    $file_information = [];

    if (static::isImage($file)) {
      $image = [
        '#theme' => 'image',
        '#uri' => $file->getFileUri(),
      ];
      $image = \Drupal::service('renderer')->render($image);

      $file_information[] = [
        '#type' => 'item',
        '#title' => t('Image'),
        '#markup' => $image,
      ];
    }

    $file_information[] = [
      '#type' => 'item',
      '#title' => t('Filename'),
      '#markup' => $link,
    ];

    $file_information[] = [
      '#type' => 'item',
      '#title' => t('File URI'),
      '#markup' => $file->getFileUri(),
    ];

    $file_information[] = [
      '#type' => 'item',
      '#title' => t('File MIME Type'),
      '#markup' => $file->getMimeType(),
    ];

    $file_information[] = [
      '#type' => 'item',
      '#title' => t('File size'),
      '#markup' => format_size($file->getSize()),
    ];

    $file_information[] = [
      '#type' => 'item',
      '#title' => t('Owner'),
      '#markup' => $owner,
    ];

    $file_information[] = [
      '#type' => 'item',
      '#title' => t('Created'),
      '#markup' => \Drupal::service('date.formatter')->format(
        $file->getCreatedTime()
      ),
    ];

    $file_information[] = [
      '#type' => 'item',
      '#title' => t('Changed'),
      '#markup' => \Drupal::service('date.formatter')->format(
        $file->getChangedTime()
      ),
    ];

    return $file_information;
  }

  /**
   * Checks if a given file is an image.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity to check.
   *
   * @return bool
   *   Whether the file is an image or not.
   */
  public static function isImage(FileInterface $file) {
    if (empty(file_validate_is_image($file))) {
      return TRUE;
    }

    if ($file->getMimeType() === 'image/svg+xml') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Adds a back button with the provided text.
   *
   * @param array $form
   *   The render array to where to add the back button.
   * @param string $text
   *   The (translated) button text to use.
   */
  public static function addBackButton(array &$form, $text) {
    $destination = \Drupal::request()->query->get('destination');

    if (!empty($destination)) {
      $back = Link::fromTextAndUrl(
        $text,
        Url::fromUserInput($destination, [
          'attributes' => ['class' => 'button'],
        ])
      )->toString();
      $form['back'] = [
        '#type' => 'markup',
        '#markup' => $back,
      ];
    }
  }

  /**
   * Finds all usages of a given file and returns the allowed file extensions.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity to check for (global) allowed file extensions.
   *
   * @return array|bool
   *   Returns an array with the allowed file extensions,
   *   an empty array if there are no restrictions
   *   and FALSE if there are no common allowed file extensions.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function getAllowedFileExtensions(FileInterface $file) {
    $filtering = FALSE;
    $allowed_file_extensions = [];
    $overall_allowed_file_extensions = [];
    $file_references = file_get_file_references($file);
    $file_usages = \Drupal::service('file.usage')->listUsage($file);

    foreach ($file_usages as $module => $file_usage) {
      foreach ($file_usage as $entity_type => $entity_ids) {
        foreach ($entity_ids as $entity_id => $usage_count) {
          $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);

          if (!empty($entity)) {
            foreach ($file_references as $field_name => $file_reference) {
              $field_config = FieldConfig::loadByName($entity_type, $entity->bundle(), $field_name);

              if (!empty($field_config)) {
                $file_extensions = $field_config->getSetting('file_extensions');

                $overall_allowed_file_extensions[] = explode(' ', $file_extensions);
              }
            }
          }
        }
      }
    }

    if (!empty($overall_allowed_file_extensions)) {
      $filtering = TRUE;
      $allowed_file_extensions = $overall_allowed_file_extensions[0];
    }

    foreach ($overall_allowed_file_extensions as $single_allowed_file_extensions) {
      $allowed_file_extensions = array_intersect(
        $allowed_file_extensions,
        $single_allowed_file_extensions
      );
    }

    if ($filtering && empty($allowed_file_extensions)) {
      $allowed_file_extensions = FALSE;
    }

    return $allowed_file_extensions;
  }

}
