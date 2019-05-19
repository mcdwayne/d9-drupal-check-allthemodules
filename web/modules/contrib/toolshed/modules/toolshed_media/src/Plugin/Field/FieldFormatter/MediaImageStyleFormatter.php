<?php

namespace Drupal\toolshed_media\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\image\Entity\ImageStyle;
use Drupal\toolshed\Utility\FileHelper;

/**
 * A Field formatter for displaying file information with media entities.
 *
 * @FieldFormatter(
 *   id = "media_image_style_formatter",
 *   label = @Translation("Media image style"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class MediaImageStyleFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $fieldDef) {
    $fieldStorageDef = $fieldDef->getFieldStorageDefinition();
    $settings = $fieldDef->getSetting('handler_settings');

    return $fieldStorageDef->getSettings()['target_type'] === 'media'
      && !empty($settings['target_bundles']) && in_array('image', $settings['target_bundles']);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entityType = $this->fieldDefinition->getSetting('target_type');
    $elements = [];

    if ($entityType === 'media') {
      $styleName = $this->getSetting('image_style');
      $viewBuilder = \Drupal::entityTypeManager()->getHandler('media', 'view_builder');

      foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
        if ($entity->getSource()->getPluginId() === 'image') {
          $fileHelper = FileHelper::fromEntity($entity);
          $fileData = $fileHelper->getData();

          $elements[$delta] = [
            '#theme' => 'image',
            '#uri' => $fileHelper->getUri(),
          ];

          foreach (['alt', 'title', 'width', 'height', 'attributes'] as $attrName) {
            if (!empty($fileData[$attrName])) {
              $elements[$delta]["#$attrName"] = $fileData[$attrName];
            }
          }

          if (!empty($styleName)) {
            $elements[$delta]['#theme'] = 'image_style';
            $elements[$delta]['#style_name'] = $styleName;
          }
        }
        else {
          $elements[$delta] = $viewBuilder->view($entity, $this->getSetting('fallback_display'));
        }
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style' => '',
      'fallback_display' => 'default',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $styleId = $this->getSetting('image_style');
    $summary = [];

    try {
      $styleLabel = empty($styleId) ? $this->t('Original') : ImageStyle::load($styleId)->label();
      $styleLabel = Html::escape($styleLabel);
    }
    catch (PluginNotFoundException $e) {
      $styleLabel = $this->t('Unable to load image_style = %style_id', ['%style_id' => $styleId]);
    }

    return [
      $this->t('Image style:') . ' ' . $styleLabel,
      $this->t('Fallback display: %fallback_display', ['%fallback_display' => $this->getSetting('fallback_display')]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $fieldStorageDef = $this->fieldDefinition->getFieldStorageDefinition();

    $styleOpts = ['' => $this->t('Original')];
    foreach (ImageStyle::loadMultiple() as $style) {
      $styleOpts[$style->id()] = Html::escape($style->label());
    }

    $form['image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image style'),
      '#options' => $styleOpts,
      '#default_value' => $this->getSetting('image_style'),
    ];

    $targetType = $fieldStorageDef->getSettings()['target_type'];
    $viewModes = ['default' => $this->t('Default')];
    foreach (\Drupal::service('entity_display.repository')->getViewModes($targetType) as $viewModeId => $viewMode) {
      $viewModes[$viewModeId] = Html::escape($viewMode['label']);
    }

    $form['fallback_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Fallback display mode'),
      '#options' => $viewModes,
      '#default_value' => $this->getSetting('fallback_display'),
      '#description' => $this->t('If the the media entity is not an image, image styles cannot be applied and will use this view mode to render as a fallback.'),
    ];

    return $form;
  }

}
