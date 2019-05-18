<?php

namespace Drupal\flags_languagefield\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\languagefield\Plugin\Field\FieldFormatter\LanguageFormatter;

/**
 * Plugin implementation of the 'languagefield_flag' formatter.
 *
 * @FieldFormatter(
 *   id = "languagefield_flag",
 *   label = @Translation("Language with flag"),
 *   field_types = {
 *     "language_field"
 *   },
 *   weight = 1
 * )
 */
class LanguagefieldFlagFormatter extends LanguageFormatter {
  public static function defaultSettings() {
    $settings = parent::defaultSettings();

    // Fall back to field settings by default.
    $settings['flag_display'] = 'flag-before';

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['flag_display'] = array(
      '#type' => 'select',
      '#title' => $this->t('Output format'),
      '#default_value' => $this->getSetting('flag_display'),
      '#options' => $this->getOutputFormats(),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $format = $this->getSetting('flag_display');
    $formats = $this->getOutputFormats();

    $summary[] = $formats[$format];

    return $summary;
  }

  /**
   * Gets available view formats.
   *
   * @return string[]
   */
  protected function getOutputFormats() {
    return array(
      'flag-before' => $this->t('Flag before language name'),
      'flag-after' => $this->t('Flag after language name'),
      'flag-instead' => $this->t('Replace language name with flag'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function viewValue(FieldItemInterface $item) {
    $languagefield_view = parent::viewValue($item);

    // Base formatter uses #plain_text output but we're adding markup,
    // se we need to alter that.
    $code = $languagefield_view['#context']['value'];
    $text = $languagefield_view['#text'];

    $format = $this->getSetting('flag_display');
    $attributes = new Attribute(array('class' => array($format)));

    $view = array();

    if ('flag-instead' != $format) {
      $view['language'] = array('#plain_text' => $text);
    }

    $view['flag'] = array(
      '#theme' => 'flags',
      '#code' => strtolower($code),
      '#attributes' => $attributes,
      '#source' => 'language',
    );

    $view['#prefix'] = '<div class="field__flags__item">';
    $view['#suffix'] = '</div>';

    return $view;
  }
}
