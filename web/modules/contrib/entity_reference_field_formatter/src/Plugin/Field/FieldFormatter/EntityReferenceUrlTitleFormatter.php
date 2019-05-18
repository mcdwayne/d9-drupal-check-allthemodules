<?php

namespace Drupal\entity_reference_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity reference ID' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_url_title",
 *   label = @Translation("Entity Url,Title"),
 *   description = @Translation("Entity Url,Title."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceUrlTitleFormatter extends EntityReferenceFormatterBase {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'separator' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['separator'] = [
      '#title' => t('Separator For title and url'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('separator'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->getSetting('separator') ? 'Separator : ' . $this->getSetting('separator') : t('No Separator');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    global $base_url;
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $uri = $entity->urlInfo();
      $internal_path = $uri->getInternalPath();
      $label = $entity->label();
      $elements[$delta] = ['#plain_text' => $label . $this->getSetting('separator') . $base_url . '/' . $internal_path];
    }
    return $elements;
  }

}
