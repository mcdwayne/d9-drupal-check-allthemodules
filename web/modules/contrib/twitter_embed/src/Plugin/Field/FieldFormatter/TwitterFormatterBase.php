<?php

namespace Drupal\twitter_embed\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TwitterFormatterBase.
 */
abstract class TwitterFormatterBase extends FormatterBase {

  /**
   * Drupal\twitter_embed\TwitterWidgetInterface definition.
   *
   * @var \Drupal\twitter_embed\TwitterWidgetInterface
   */
  protected $twitterWidget;

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settingsForm = $this->twitterWidget->getSettingsForm($this->getSettings());
    // Removing display options until they can correctly be saved.
    unset($settingsForm['display_options']);
    $selector = 'fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings]';
    // Append javascript states.
    $settingsFormWithStates = $this->twitterWidget->setSettingsFormStates($settingsForm, $selector);
    return $settingsFormWithStates
    + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Display style: @display_style', [
      '@display_style' => $this->getSettings()['display_style'],
    ]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $configuration = $this->getSettings();
      // @todo sanitize, remove @, ...
      $configuration['username'] = $this->viewValue($item);
      $elements[$delta] = $this->twitterWidget->getWidget($configuration);
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

}
