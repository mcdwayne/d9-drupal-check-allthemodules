<?php
/**
 * @file
 * Contains \Drupal\flickity_fields\Plugin\Field\FieldFormatter\FlickityEntityView
 */

namespace Drupal\flickity_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'flickity entity view' formatter.
 *
 * @FieldFormatter(
 *   id = "flickity_entity_view",
 *   label = @Translation("Flickity"),
 *   description = @Translation("Display the referenced entities in a Flickity carousel."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */

class FlickityEntityView extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return array(
      'flickity_settings' => array(
        '#title' => $this->t('Flickity settings'),
        '#type' => 'select',
        '#options' => flickity_settings_list(),
        '#default_value' => $this->getSetting('flickity_settings'),
        '#required' => TRUE,
      )
    ) + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'flickity_settings' => 'default_group'
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return parent::settingsSummary(); // TODO: Add selected settings group to summary
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array(
      '#theme' => 'flickity_entity_view',
      '#children' => parent::viewElements($items, $langcode),
      '#settings' => $this->getSetting('flickity_settings'),
      '#title' => $this->fieldDefinition->getLabel(),
      '#label_display' => $this->label,
    );

    return $elements;
  }
}
