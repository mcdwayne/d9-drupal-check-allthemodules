<?php

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datex\Datex\DatexDrupalDateTime;

/**
 * List of saved schemas plus 'default' and 'disabled' for form_options list.
 */
function _datex_schema_form_options() {
  $cfg = \Drupal::config('datex.schemas')->get();
  $cfg = array_keys($cfg);
  $cfg_c = [];
  foreach ($cfg as $value) {
    if ($value[0] !== '_') {
      $cfg_c[] = $value;
    }
  }
  $cfg = $cfg_c;
  $cfg = array_combine($cfg, $cfg);
  return [
      'disabled' => t('Disabled'),
      'default' => t('Default'),
    ] + $cfg;
}

/**
 * Find the datex schema set for a field (or element, or views filter, or...),
 * or the global schema of none is set.
 */
function _datex_element_schema($e) {
  $ret = NULL;
  if (!is_array($e)) {
    $ret = NULL;
  }
  elseif (isset($e['datex_schema'])) {
    $ret = $e['datex_schema'];
  }
  else {
    $ret = 'default';
  }
  return empty($ret) ? 'default' : $ret;
}

/**
 * Get calendar for a/current language in a mode (if given) from schema.
 *
 * @param string $for_schema
 * @param null $for_lang
 *
 * @return null
 */
function _datex_language_calendar_name($for_schema = 'default', $for_lang = NULL) {
  if (!$for_lang) {
    $for_lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
  }
  if (!$for_schema) {
    $for_schema = 'default';
  }
  $cfg = \Drupal::config('datex.schemas')->get($for_schema);
  return is_array($cfg) && isset($cfg[$for_lang]) ? $cfg[$for_lang] : NULL;
}

trait DatexWidgetTrait {

  public static function defaultSettings() {
    return [
        'datex_schema' => 'default',
      ] + parent::defaultSettings();
  }

  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['datex_schema'] = [
      '#type' => 'select',
      '#title' => t('Date schema'),
      '#default_value' => $this->getSetting('datex_schema'),
      '#options' => _datex_schema_form_options(),
    ];

    return $element;
  }

  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Datex schema: @schema', ['@schema' => $this->getSetting('datex_schema')]);

    return $summary;
  }

}
