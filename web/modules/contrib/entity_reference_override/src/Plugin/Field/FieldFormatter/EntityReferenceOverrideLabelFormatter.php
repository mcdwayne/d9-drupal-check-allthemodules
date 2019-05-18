<?php

namespace Drupal\entity_reference_override\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_reference_override_label' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_override_label",
 *   label = @Translation("Label"),
 *   description = @Translation("Display the label of the referenced entities with or a custom title."),
 *   field_types = {
 *     "entity_reference_override"
 *   }
 * )
 */
class EntityReferenceOverrideLabelFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'override_action' => 'title',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['override_action'] = array(
      '#type' => 'radios',
      '#options' => [
        'title' => t('Replace the title'),
        'title-append' => t('Append to the title'),
        'suffix' => t('Add after title'),
        'class' => t('Add link class'),
      ],
      '#title' => t('Use custom text to'),
      '#default_value' => $this->getSetting('override_action'),
      '#required' => TRUE,
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    switch ($this->getSetting('override_action')) {
      case 'title':
        $override = t('title override');
        break;
      case 'title-append':
        $override = t('title addition');
        break;
      case 'class':
        $override = t('custom CSS class');
        break;
      case 'suffix':
        $override = t('note after title');
        break;
        
    }
    $summary[] = t('Per-entity @override', array('@override' => $override));

    return $summary;
  }

  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $values = $items->getValue();

    foreach ($elements as $delta => $entity) {
      if (!empty($values[$delta]['override'])) {
        switch ($this->getSetting('override_action')) {
          case 'title':
            $elements[$delta]['#title'] = $values[$delta]['override'];
            break;
          case 'title-append':
            $elements[$delta]['#title'] .= ' (' . $values[$delta]['override'] . ')';
            break;
          case 'class':
            $elements[$delta]['#attributes']['class'][] = $values[$delta]['override'];
            break;
          case 'suffix':
            if (!isset($elements[$delta]['#suffix'])) {
              $elements[$delta]['#suffix'] = '';
            }
            $elements[$delta]['#suffix'] .= ' (' . $values[$delta]['override'] . ')';
            break;
        }
      }
    }

    return $elements;
  }
}
