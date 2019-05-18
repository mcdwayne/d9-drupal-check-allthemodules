<?php

namespace Drupal\jsnippet\Plugin\Field\FieldFormatter;

use Drupal\jsnippet\Entity\JSnippet;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity reference label' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_snippet",
 *   label = @Translation("Snippet"),
 *   description = @Translation("Attach or embed the referenced code snippet."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceSnippetFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      if ($entity instanceof JSnippet) {
        $elements[$delta] = [
          '#markup' => '',
        ];
        $elements[$delta]['#attached']['library'][] = "jsnippet/" . $entity->id();
      }
    }

    return $elements;
  }

}
