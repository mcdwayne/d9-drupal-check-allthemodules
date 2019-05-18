<?php

namespace Drupal\calendar_systems\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a block to show a localized current/relative date.
 *
 * @Block (
 *   id = "calendar_systems_block",
 *   admin_label=  @Translation("Calendar Systems Block"),
 * )
 */
class CalendarSystemsBlock extends BlockBase {

  public function defaultConfiguration() {
    return [
      'calendar_systems_calendar' => 'global',
      'calendar_systems_format' => 'Y/m/d H:i:s',
      'calendar_systems_timezone' => 'user',
      'calendar_systems_text' => '{}',
    ];
  }

  public function blockForm($form, FormStateInterface $form_state) {
    $f = parent::blockForm($form, $form_state);

    $def = $this->defaultConfiguration();

    // Retrieve existing configuration for this block.
    $cfg = $this->getConfiguration();
    $cfg = [
      'calendar_systems_calendar' => isset($cfg['calendar_systems_calendar']) 
        ? ($cfg['calendar_systems_calendar'] ?: $def['calendar_systems_calendar'])
        : $def['calendar_systems_calendar'],
      'calendar_systems_format' => isset($cfg['calendar_systems_format']) 
        ? ($cfg['calendar_systems_format'] ?: $def['calendar_systems_format'])
        : $def['calendar_systems_format'],
      'calendar_systems_timezone' => isset($cfg['calendar_systems_timezone']) 
        ? ($cfg['calendar_systems_timezone'] ?: $def['calendar_systems_timezone'])
        : $def['calendar_systems_timezone'],
      'calendar_systems_text' => isset($cfg['calendar_systems_text']) 
        ? ($cfg['calendar_systems_text'] ?: $def['calendar_systems_text'])
        : $text['calendar_systems_text'],
      'cache' => isset($cfg['cache']) ? $cfg['cache'] : 3600,
    ];

    $f['calendar_systems_calendar'] = [
      '#title' => t('Calendar'),
      '#type' => 'select',
      '#options' => ['persian' => t('Persian'), 'gregorian' => t('Gregorian'), 'global' => t("Global (by site's langauge")],
      '#default_value' => $cfg['calendar_systems_calendar'],
    ];
    $f['calendar_systems_format'] = [
      '#title' => t('Date/Time format'),
      '#type' => 'textfield',
      '#description' => 'TODO add medium short and ... See php.net/manual/en/function.date.php for date formats',
      '#default_value' => $cfg['calendar_systems_format'],
    ];
    $f['calendar_systems_timezone'] = [
      '#title' => t('Timezone'),
      '#type' => 'select',
      '#options' => [
          'site' => t("Use site's timezone"),
          'user' => t("Use user's timezone"),
        ] + system_time_zones(),
      '#default_value' => $cfg['calendar_systems_timezone'],
    ];
    $f['calendar_systems_text'] = [
      '#title' => t('Content'),
      '#type' => 'textfield',
      '#description' => t('The blocks content. {} is replaced with the actual date. If unsure, leave this field empty or set it to {}'),
      '#default_value' => $cfg['calendar_systems_text'],
    ];
    $f['cache'] = [
      '#title' => t('Cache lifetime'),
      '#type' => 'textfield',
      '#description' => t('How long the block should be cached, in seconds'),
      '#default_value' => $cfg['cache'],
    ];

    return $f;
  }

  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('calendar_systems_format', $form_state->getValue('calendar_systems_format'));
    $this->setConfigurationValue('calendar_systems_calendar', $form_state->getValue('calendar_systems_calendar'));
    $this->setConfigurationValue('calendar_systems_timezone', $form_state->getValue('calendar_systems_timezone'));

    if (strpos($form_state->getValue('calendar_systems_text'), '{}') < 0) {
      drupal_set_message(t('Invalid content, content set to {}'), 'warning');
      $this->setConfigurationValue('calendar_systems_text', '{}');
    }
    else {
      $this->setConfigurationValue('calendar_systems_text', $form_state->getValue('calendar_systems_text'));
    }

    $c = $form_state->getValue('cache');
    if ($c !== '' && is_numeric($c) && $c >= 0) {
      $this->setConfigurationValue('cache', $form_state->getValue('cache'));
    }
    else {
      drupal_set_message('block cache time set to one hour');
      $this->setConfigurationValue('cache', 3600);
    }
  }

  public function getCacheMaxAge() {
    $config = $this->getConfiguration();
    return isset($config['cache']) ? $config['cache'] : 3600;
  }

  public function build() {
    $def = $this->defaultConfiguration();
    $cfg = $this->getConfiguration();
    $cfg = [
      'cal' => isset($cfg['calendar_systems_calendar']) 
         ? ($cfg['calendar_systems_calendar'] ?: $def['calendar_systems_calendar'])
         : $def['calendar_systems_calendar'],
      'fmt' => isset($cfg['calendar_systems_format']) 
         ? ($cfg['calendar_systems_format'] ?: $def['calendar_systems_format'])
         : $def['calendar_systems_format'],
      'tz' => isset($cfg['calendar_systems_timezone']) 
         ? ($cfg['calendar_systems_timezone'] ?: $def['calendar_systems_timezone'])
         : $def['calendar_systems_timezone'],
      'text' => isset($cfg['calendar_systems_text']) 
         ? ($cfg['calendar_systems_text'] ?: $def['calendar_systems_text'])
         : $def['calendar_systems_text'],
    ];
    switch ($cfg['tz']) {
      case 'site':
        $config = \Drupal::config('system.date');
        $config_data_default_timezone = $config->get('timezone.default');
        $tz = !empty($config_data_default_timezone) ? $config_data_default_timezone : @date_default_timezone_get();
        break;
      case 'user':
        $tz = \drupal_get_user_timezone();
        break;
      default:
        $tz = $cfg['tz'];
    }

    if($cfg['cal'] === 'global') {
      $cfg['cal'] = '';
    }
    $calendar = calendar_systems_factory($tz, NULL, $cfg['cal']);
    $content = str_replace('{}', $calendar->format($cfg['fmt']), $cfg['text']);

    return [
      '#markup' => $content,
    ];
  }

}
