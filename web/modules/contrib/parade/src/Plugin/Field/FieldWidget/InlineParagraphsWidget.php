<?php

namespace Drupal\parade\Plugin\Field\FieldWidget;

use Drupal\paragraphs\Plugin\Field\FieldWidget\InlineParagraphsWidget as ParagraphsInlineParagraphsWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_reference parade' widget.
 *
 * We hide add / remove buttons when translating to avoid accidental loss of
 * data because these actions effect all languages.
 *
 * @FieldWidget(
 *   id = "entity_reference_parade",
 *   label = @Translation("Parade Classic"),
 *   description = @Translation("A parade inline form widget."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class InlineParagraphsWidget extends ParagraphsInlineParagraphsWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();

    // New values.
    $settings['add_text_needed'] = FALSE;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    // New values.
    $elements['add_text_needed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Add 'Add ' text before title on entity add buttons."),
      '#default_value' => $this->getSetting('add_text_needed'),
      '#weight' => 2,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    // New values.
    $summary[] = $this->t("'Add ' text will be added before title?: @answer", ['@answer' => $this->getSetting('add_text_needed') ? 'Yes' : 'No']);

    return $summary;
  }

}
