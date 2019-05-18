<?php

namespace Drupal\linky\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dynamic_entity_reference\Plugin\Field\FieldFormatter\DynamicEntityReferenceLabelFormatter;

/**
 * Plugin implementation of the 'dynamic entity reference label' formatter.
 *
 * @FieldFormatter(
 *   id = "linky_label",
 *   label = @Translation("Direct link formatter"),
 *   description = @Translation("Links managed links directly to their external link"),
 *   field_types = {
 *     "dynamic_entity_reference"
 *   }
 * )
 */
class LinkyFormatter extends DynamicEntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + [
      'parent_entity_label_link_text' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['parent_entity_label_link_text'] = [
      '#title' => $this->t('Use parent entity label as link text'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('parent_entity_label_link_text'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->getSetting('parent_entity_label_link_text') ? $this->t('Use parent entity label as link text') : $this->t('Use link title as link text');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $output_as_link = $this->getSetting('link');
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      if ($entity->getEntityTypeId() !== 'linky') {
        continue;
      }

      if ($output_as_link && !$entity->isNew()) {
        // Use the parent entity's label instead of the link title.
        if ($this->getSetting('parent_entity_label_link_text')) {
          $elements[$delta]['#title'] = $items[$delta]->getEntity()->label();
        }
        else {
          $elements[$delta]['#title'] = $entity->link->title;
        }
        $elements[$delta]['#url'] = $entity->link->first()->getUrl();
      }
      else {
        $elements[$delta] = ['#plain_text' => $entity->link->title];
      }
    }
    return $elements;
  }

}
