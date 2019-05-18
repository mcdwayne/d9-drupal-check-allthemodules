<?php

namespace Drupal\datex\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a block to show a localized current/relative date.
 *
 * @Block (
 *   id = "datex_block",
 *   admin_label=  @Translation("Datex Block"),
 * )
 */
class DatexBlock extends BlockBase {

  public function defaultConfiguration() {
    return [
      'cal' => 'persian',
      'fmt' => 'Y/m/d H:i:s',
      'tz' => 'user',
      'text' => '{}',
    ];
  }

  public function blockForm($form, FormStateInterface $form_state) {
    $f = parent::blockForm($form, $form_state);

    // Retrieve existing configuration for this block.
    $cfg = $this->getConfiguration();
    $cfg = [
      'cal' => isset($cfg['cal']) ? $cfg['cal'] : 'persian',
      'fmt' => isset($cfg['fmt']) ? $cfg['fmt'] : 'Y/m/d H:i:s',
      'tz' => isset($cfg['tz']) ? $cfg['tz'] : 'user',
      'text' => isset($cfg['text']) ? $cfg['text'] : '{}',
      'cache' => isset($cfg['cache']) ? $cfg['cache'] : 3600,
    ];

    $f['datex_calendar'] = [
      '#title' => t('Calendar'),
      '#type' => 'select',
      '#options' => _datex_available_calendars(),
      '#default_value' => $cfg['cal'],
    ];
    $f['datex_format'] = [
      '#title' => t('Date/Time format'),
      '#type' => 'textfield',
      '#description' => 'TODO add medium short and ... See php.net/manual/en/function.date.php for date formats',
      '#default_value' => $cfg['fmt'],
    ];
    $f['datex_timezone'] = [
      '#title' => t('Timezone'),
      '#type' => 'select',
      '#options' => [
          'site' => t("Use site's timezone"),
          'user' => t("Use user's timezone"),
        ] + system_time_zones(),
      '#default_value' => $cfg['tz'],
    ];
    $f['datex_text'] = [
      '#title' => t('Content'),
      '#type' => 'textfield',
      '#description' => t('The blocks content. {} is replaced with the actual date. If unsure, leave this field empty or set it to {}'),
      '#default_value' => $cfg['text'],
    ];
    $f['datex_cache'] = [
      '#title' => t('Cache lifetime'),
      '#type' => 'textfield',
      '#description' => t('How long the block should be cached, in seconds'),
      '#default_value' => $cfg['cache'],
    ];

    return $f;
  }

  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('fmt', $form_state->getValue('datex_fmt'));
    $this->setConfigurationValue('cal', $form_state->getValue('datex_calendar'));
    $this->setConfigurationValue('tz', $form_state->getValue('datex_tz'));

    if (strpos($form_state->getValue('datex_text'), '{}') < 0) {
      drupal_set_message(t('Invalid content, content set to {}'), 'warning');
      $this->setConfigurationValue('text', '{}');
    }
    else {
      $this->setConfigurationValue('text', $form_state->getValue('datex_text'));
    }

    $c = $form_state->getValue('datex_cache');
    if ($c !== '' && is_numeric($c) && $c >= 0) {
      $this->setConfigurationValue('cache', $form_state->getValue('datex_cache'));
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
    $cfg = $this->getConfiguration();
    $cfg = [
      'cal' => isset($cfg['cal']) ? $cfg['cal'] : 'persian',
      'fmt' => isset($cfg['fmt']) ? $cfg['fmt'] : 'Y/m/d H:i:s',
      'tz' => isset($cfg['tz']) ? $cfg['tz'] : 'user',
      'text' => isset($cfg['text']) ? $cfg['text'] : '{}',
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

    $calendar = datex_factory($tz, NULL, $cfg['cal']);
    $content = str_replace('{}', $calendar->format($cfg['fmt']), $cfg['text']);

    return [
      '#markup' => $content,
    ];
  }

}
