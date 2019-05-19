<?php

namespace Drupal\toolshed_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Drupal\toolshed\Utility\FileHelper;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * A Field formatter for displaying file information with media entities.
 *
 * @FieldFormatter(
 *   id = "file_info_formatter",
 *   label = @Translation("File Info Format"),
 *   field_types = {
 *     "file",
 *     "entity_reference",
 *   },
 * )
 */
class FileInfoFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $fieldDef) {
    $fieldStorageDef = $fieldDef->getFieldStorageDefinition();

    switch ($fieldStorageDef->getType()) {
      case 'entity_reference':
        return in_array($fieldStorageDef->getSetting('target_type'), ['file', 'media']);

      case 'file':
        return TRUE;

      default:
        return FALSE;
    }
  }

  /**
   * Get the list of available options for linking the entity reference field.
   *
   * For media it is valid for the resulting link to point to either the file
   * or the original media object. For file, the entity is the file, so they
   * actually both generate the same link.
   *
   * @return array
   *   Return a key/value list of available options for linking the field.
   */
  public function getLinkOptions() {
    return [
      'file' => $this->t('File'),
      'entity' => $this->t('Referenced entity'),
    ];
  }

  /**
   * Convert the number of raw bytes into a human friendly readable format.
   *
   * This will convert the bytes integer to a string of the file size with
   * the appropriate unit suffix.
   *
   * @param int $bytes
   *   The size of a file in number of bytes.
   * @param int $decimals
   *   Number of decimal places to keep in the returned string.
   *
   * @return string
   *   Return the file size with a unit suffix, and up to the number
   *   of decimal places requested.
   */
  protected function readableFileSize($bytes, $decimals = 2) {
    $i = 0;
    $divisor = 1;
    $next = 1024;
    $suffixes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

    while (($bytes / $next) > 1.0 && isset($suffixes[$i])) {
      $divisor = $next;
      $next *= 1024;
      ++$i;
    }

    return number_format($bytes / $divisor, $decimals) . ' ' . $suffixes[$i];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entityType = $this->fieldDefinition->getSetting('target_type');

    if (in_array($entityType, ['file', 'media'])) {
      $elements = [];
      $linkTo = $this->getSetting('link_to');
      $displayInfo = array_flip(array_filter($this->getSetting('info_shown')));

      foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
        $fileHelper = FileHelper::fromEntity($entity);

        $itemUrl = NULL;
        $label = $this->getSetting('link_text');
        $label = empty($label) ? $entity->label() : $label;

        if ($fileHelper) {
          $info = [];

          if (isset($displayInfo['mime'])) {
            $info[] = $fileHelper->getMime();
          }
          if (isset($displayInfo['size'])) {
            $info[] = $this->readableFileSize($fileHelper->getSize());
          }

          $label .= (empty($info) ? '' : ' (' . implode(', ', $info) . ')');
        }

        if (!empty($linkTo)) {
          $itemUrl = $fileHelper && ($linkTo == 'file' || $entity instanceof File)
            ? Url::fromUri(file_create_url($fileHelper->getUri())) : $entity->urlInfo();
        }

        if (!empty($itemUrl)) {
          $elements[$delta] = [
            '#type' => 'link',
            '#title' => $label,
            '#url' => $itemUrl,
            '#options' => $itemUrl->getOptions(),
          ];
        }
        else {
          $elements[$delta] = [
            '#prefix' => '<span>',
            '#suffix' => '</span>',
            '#markup' => Html::escape($label),
          ];
        }
      }

      return $elements;
    }

    // Unrecognized entity type, so we return an empty render array.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'link_to' => 'file',
      'link_text' => NULL,
      'info_shown' => [
        'mime',
        'size',
      ],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $infoDisplay = array_filter($this->getSetting('info_shown'));
    if (!empty($infoDisplay)) {
      $summary[] = $this->t('Displaying file info: %display', [
        '%display' => implode(', ', $infoDisplay),
      ]);
    }

    $linkTo = $this->getSetting('link_to');
    $linkOpts = $this->getLinkOptions();

    if (!empty($linkTo) && isset($linkOpts[$linkTo])) {
      $summary[] = $this->t('Linked to <strong>@target</strong>', ['@target' => $linkOpts[$linkTo]]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['link_to'] = [
      '#type' => 'select',
      '#title' => $this->t('Link to:'),
      '#options' => ['' => $this->t('No link')] + $this->getLinkOptions(),
      '#default_value' => $this->getSetting('link_to'),
    ];

    $form['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text:'),
      '#default_value' => $this->getSetting('link_text'),
      '#description' => $this->t('Leave blank to use default'),
    ];

    $form['info_shown'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Info Shown'),
      '#description' => $this->t('Use the options to determine what will be displayed'),
      '#options' => [
        'mime' => $this->t('File mime'),
        'size' => $this->t('File size'),
      ],
      '#default_value' => $this->getSetting('info_shown'),
    ];

    return $form;
  }

}
