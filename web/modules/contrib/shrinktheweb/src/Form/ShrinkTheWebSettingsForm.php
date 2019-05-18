<?php

namespace Drupal\shrinktheweb\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Random;

class ShrinkTheWebSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shrinktheweb_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('shrinktheweb.settings');

    module_load_include('inc', 'shrinktheweb', 'shrinktheweb.api');
    $aAccountInfo = shrinktheweb_getAccountInfo();
    $response_status = $aAccountInfo['stw_response_status'];
    $inside_pages = (!is_null($aAccountInfo['stw_inside_pages'])) ? $aAccountInfo['stw_inside_pages']->__toString() : 0;
    $custom_size = $aAccountInfo['stw_custom_size'];
    $full_length = (!is_null($aAccountInfo['stw_full_length'])) ? $aAccountInfo['stw_full_length']->__toString() : 0;
    $custom_delay = $aAccountInfo['stw_custom_delay'];
    $custom_quality = $aAccountInfo['stw_custom_quality'];
    $custom_resolution = $aAccountInfo['stw_custom_resolution'];
    $custom_messages = $aAccountInfo['stw_custom_messages'];

    if ($config->get('shrinktheweb_token') == '') {
      $generator = new Random();
      $config->set('shrinktheweb_token', $generator->name(32, TRUE));
      $config->save();
    }

    $form = array();
    $form['shrinktheweb_clearcache'] = array(
      '#type' => 'fieldset',
      '#title' => t('ShrinkTheWeb Clear Cache'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );
    $form['shrinktheweb_clearcache']['shrinktheweb_clear_imagecache'] = array(
      '#type' => 'checkbox',
      '#title' => t('Clear Cached Screenshots'),
      '#default_value' => FALSE,
      '#description' => t('By selecting this checkbox all cached thumbshots gets deleted'),
      '#disabled' => FALSE,
    );
    $form['shrinktheweb_clearcache']['shrinktheweb_clear_errorcache'] = array(
      '#type' => 'checkbox',
      '#title' => t('Clear Error Images'),
      '#default_value' => FALSE,
      '#description' => t('By selecting this checkbox all cached error images gets deleted'),
      '#disabled' => FALSE,
    );
    $form['shrinktheweb_clearcache']['shrinktheweb_refresh_thumbnails'] = array(
      '#type' => 'checkbox',
      '#title' => t('Refresh All Thumbnails'),
      '#default_value' => FALSE,
      '#description' => t('By selecting this checkbox, all sites with cached thumbnails will be re-captured and the latest screenshots downloaded automatically'),
      '#disabled' => FALSE,
    );

    $form['shrinktheweb_info'] = array(
      '#type' => 'fieldset',
      '#title' => t('ShrinkTheWeb Information'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );
    $form['shrinktheweb_info']['shrinktheweb_get_account'] = array(
      '#type' => 'item',
      '#title' => t('Get Account'),
      '#description' => t('You can get your "Access Key" and "Secret Key" by signing up for automated screenshots at: <a target="_blank" href="https://shrinktheweb.com/">ShrinkTheWeb</a>'),
    );
    $form['shrinktheweb_info']['shrinktheweb_referrer_list'] = array(
      '#type' => 'item',
      '#title' => t('Referrer List'),
      '#description' => t('By default, you must add your server\'s IP address to <a href="https://shrinktheweb.com/content/how-do-i-lock-my-account.html" target="_blank">ShrinkTheWeb\'s "Allowed Referrers" list</a>'),
    );
    $form['shrinktheweb_info']['shrinktheweb_usage_instructions'] = array(
      '#type' => 'item',
      '#title' => t('Usage Instructions'),
      '#description' => t('This module adds new field formatters to the fields of type Link. You can also put screenshots anywhere in PHP code. This module works with <a href="https://www.drupal.org/project/views_php" target="_blank">Views PHP</a> or <a href="https://www.drupal.org/project/php" target="_blank">PHP</a> module. <a href="https://www.drupal.org/node/1067900" target="_blank">Learn more</a>'),
    );

    $form['shrinktheweb_keys'] = array(
      '#type' => 'fieldset',
      '#title' => t('ShrinkTheWeb API keys'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );
    $form['shrinktheweb_keys']['shrinktheweb_access_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Access key'),
      '#default_value' => $config->get('shrinktheweb_access_key'),
      '#required' => TRUE,
    );
    $form['shrinktheweb_keys']['shrinktheweb_secret_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Secret key'),
      '#default_value' => $config->get('shrinktheweb_secret_key'),
      '#required' => TRUE,
    );
    if ($config->get('shrinktheweb_access_key') == '' || $config->get('shrinktheweb_secret_key') == '' || $response_status != 'Success') {
      $form['shrinktheweb_keys']['shrinktheweb_status'] = array(
        '#type' => 'item',
        '#title' => t('Invalid account credentials detected'),
        '#description' => t('Please enter your account credentials and make sure they are correct'),
      );
    } else {

      $form['shrinktheweb_options'] = array(
        '#type' => 'fieldset',
        '#title' => t('ShrinkTheWeb Options'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
      );
      $form['shrinktheweb_options']['shrinktheweb_notifynopush'] = array(
        '#type' => 'select',
        '#title' => t('Screenshot Delivery Method'),
        '#options' => array(
          0 => t('Recieve data with notification'),
          1 => t('Make separate request for data'),
        ),
        '#default_value' => $config->get('shrinktheweb_notifynopush'),
        '#required' => TRUE,
        '#description' => t('Making a separate request for data uses more server resources but will reduce bandwidth usage when working with large images or large quantities of images'),
      );
      if ($config->get('shrinktheweb_enable_https_set_automatically') == 0) {
        $form['shrinktheweb_options']['shrinktheweb_enable_https'] = array(
            '#type' => 'select',
            '#title' => t('Enable HTTP Secure (HTTPS)'),
            '#options' => array(
                0 => t('Disable'),
                1 => t('Enable'),
            ),
            '#default_value' => $config->get('shrinktheweb_enable_https'),
            '#required' => TRUE,
        );
      }
      $form['shrinktheweb_options']['shrinktheweb_token'] = array(
        '#type' => 'textfield',
        '#title' => t('Token'),
        '#default_value' => $config->get('shrinktheweb_token'),
        '#description' => t('Random token for getting screenshots'),
        '#size' => 32,
        '#maxlength' => 32,
      );
      $form['shrinktheweb_options']['shrinktheweb_inside_pages'] = array(
        '#type' => 'checkbox',
        '#title' => t('Inside Page Captures'),
        '#default_value' => $inside_pages == 1 ? TRUE : FALSE,
        '#description' => $inside_pages == 0 ? t('Upgrade required to use this feature') : t('i.e. not just homepages and sub-domains, auto selected since you have purchased this pro feature'),
        '#disabled' => TRUE,
      );
      $form['shrinktheweb_options']['shrinktheweb_thumb_size'] = array(
        '#type' => 'select',
        '#title' => t('Default Thumbnail size'),
        '#options' => array(
          'mcr' => t('mcr'),
          'tny' => t('tny'),
          'vsm' => t('vsm'),
          'sm' => t('sm'),
          'lg' => t('lg'),
          'xlg' => t('xlg'),
        ),
        '#default_value' => $config->get('shrinktheweb_thumb_size'),
        '#required' => TRUE,
        '#description' => t('width: mcr 75px, tny 90px, vsm 100px, sm 120px, lg 200px, xlg 320px'),
      );
      $form['shrinktheweb_options']['shrinktheweb_thumb_size_custom'] = array(
        '#type' => 'textfield',
        '#title' => t('Custom Width'),
        '#default_value' => $custom_size == 0 && $full_length == 0 ? '' : $config->get('shrinktheweb_thumb_size_custom'),
        '#description' => $custom_size == 0 && $full_length == 0 ? t('Upgrade required to use this feature') : t('Enter your custom image width, this will override default size'),
        '#size' => 10,
        '#maxlength' => 10,
        '#disabled' => $custom_size == 1 || $full_length == 1 ? FALSE : TRUE,
      );
      $form['shrinktheweb_options']['shrinktheweb_full_size'] = array(
        '#type' => 'checkbox',
        '#title' => t('Full-Length capture'),
        '#default_value' => $full_length == 0 ? FALSE : $config->get('shrinktheweb_full_size'),
        '#description' => $full_length == 0 ? t('Upgrade required to use this feature') : '',
        '#disabled' => $full_length == 1 ? FALSE : TRUE,
      );
      $form['shrinktheweb_options']['shrinktheweb_max_height'] = array(
        '#type' => 'textfield',
        '#title' => t('Max height'),
        '#default_value' => $full_length == 0 ? '' : $config->get('shrinktheweb_max_height'),
        '#description' => $full_length == 0 ? t('Upgrade required to use this feature') : t('use if you want to set maxheight for fullsize capture'),
        '#size' => 10,
        '#maxlength' => 10,
        '#disabled' => $full_length == 1 ? FALSE : TRUE,
      );
      $form['shrinktheweb_options']['shrinktheweb_native_res'] = array(
        '#type' => 'textfield',
        '#title' => t('Native resolution'),
        '#default_value' => $custom_resolution == 0 ? '' : $config->get('shrinktheweb_native_res'),
        '#description' => $custom_resolution == 0 ? t('Upgrade required to use this feature') : t('i.e. 640 for 640x480'),
        '#size' => 10,
        '#maxlength' => 10,
        '#disabled' => $custom_resolution == 1 ? FALSE : TRUE,
      );
      $form['shrinktheweb_options']['shrinktheweb_widescreen_y'] = array(
        '#type' => 'textfield',
        '#title' => t('Widescreen resolution Y'),
        '#default_value' => $custom_resolution == 0 ? '' : $config->get('shrinktheweb_widescreen_y'),
        '#description' => $custom_resolution == 0 ? t('Upgrade required to use this feature') : t('i.e. 900 for 1440x900 if 1440 is set for Native resolution'),
        '#size' => 10,
        '#maxlength' => 10,
        '#disabled' => $custom_resolution == 1 ? FALSE : TRUE,
      );
      $form['shrinktheweb_options']['shrinktheweb_delay'] = array(
        '#type' => 'textfield',
        '#title' => t('Delay After Load'),
        '#default_value' => $custom_delay == 0 ? '' : $config->get('shrinktheweb_delay'),
        '#description' => $custom_delay == 0 ? t('Upgrade required to use this feature') : t('max. 45'),
        '#size' => 10,
        '#maxlength' => 10,
        '#disabled' => $custom_delay == 1 ? FALSE : TRUE,
      );
      $form['shrinktheweb_options']['shrinktheweb_quality'] = array(
        '#type' => 'textfield',
        '#title' => t('Quality'),
        '#default_value' => $custom_quality == 0 ? '' : $config->get('shrinktheweb_quality'),
        '#description' => $custom_quality == 0 ? t('Upgrade required to use this feature') : t('0 .. 100'),
        '#size' => 10,
        '#maxlength' => 10,
        '#disabled' => $custom_quality == 1 ? FALSE : TRUE,
      );

      $form['shrinktheweb_adv_options'] = array(
        '#type' => 'fieldset',
        '#title' => t('ShrinkTheWeb Advanced Options'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
      );
      $form['shrinktheweb_adv_options']['shrinktheweb_cache_days'] = array(
        '#type' => 'textfield',
        '#title' => t('Cache days'),
        '#default_value' => $config->get('shrinktheweb_cache_days'),
        '#description' => t('How many days the images are valid in your cache, Enter 0 (zero) to never update screenshots once cached or -1 to disable caching and always use embedded method instead'),
        '#size' => 10,
        '#maxlength' => 10,
      );
      $form['shrinktheweb_adv_options']['shrinktheweb_thumbs_folder'] = array(
        '#type' => 'textfield',
        '#title' => t('Thumbnails folder'),
        '#default_value' => $config->get('shrinktheweb_thumbs_folder'),
        '#required' => TRUE,
        '#description' => t('This is a subfolder of the "File system path" folder.'),
      );
      $form['shrinktheweb_adv_options']['shrinktheweb_custom_msg_url'] = array(
        '#type' => 'textfield',
        '#title' => t('Custom Messages URL'),
        '#default_value' => $custom_messages == 0 ? '' : $config->get('shrinktheweb_custom_msg_url'),
        '#description' => $custom_messages == 0 ? t('Upgrade required to use this feature') : t('specify the URL where your custom message images are stored'),
        '#size' => 10,
        '#maxlength' => 10,
        '#disabled' => $custom_messages == 1 ? FALSE : TRUE,
      );
      $form['shrinktheweb_adv_options']['shrinktheweb_debug'] = array(
        '#type' => 'checkbox',
        '#title' => t('Debug'),
        '#default_value' => $config->get('shrinktheweb_debug'),
        '#description' => t('Store debug info in database'),
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $form_state->setValue('shrinktheweb_access_key', trim($form_state->getValue(['shrinktheweb_access_key'])));
    $form_state->setValue('shrinktheweb_secret_key', trim($form_state->getValue(['shrinktheweb_secret_key'])));
    $form_state->setValue('shrinktheweb_cache_days', trim($form_state->getValue(['shrinktheweb_cache_days'])));
    $form_state->setValue('shrinktheweb_thumb_size_custom', trim($form_state->getValue(['shrinktheweb_thumb_size_custom'])));
    $form_state->setValue('shrinktheweb_max_height', trim($form_state->getValue(['shrinktheweb_max_height'])));
    $form_state->setValue('shrinktheweb_native_res', trim($form_state->getValue(['shrinktheweb_native_res'])));
    $form_state->setValue('shrinktheweb_widescreen_y', trim($form_state->getValue(['shrinktheweb_widescreen_y'])));
    $form_state->setValue('shrinktheweb_delay', trim($form_state->getValue(['shrinktheweb_delay'])));
    $form_state->setValue('shrinktheweb_quality', trim($form_state->getValue(['shrinktheweb_quality'])));
    $form_state->setValue('shrinktheweb_custom_msg_url', trim($form_state->getValue(['shrinktheweb_custom_msg_url'])));
    $form_state->setValue('shrinktheweb_token', trim($form_state->getValue(['shrinktheweb_token'])));

    module_load_include('inc', 'shrinktheweb', 'shrinktheweb.api');
    $aAccountInfo = shrinktheweb_getAccountInfo($form_state->getValue(['shrinktheweb_access_key']), $form_state->getValue(['shrinktheweb_secret_key']));
    $response_status = $aAccountInfo['stw_response_status'];

    if ($form_state->getValue(['shrinktheweb_access_key']) == '' || $form_state->getValue(['shrinktheweb_secret_key']) == '' || $response_status != 'Success') {
      $form_state->setErrorByName('invalid_account_credentials', t('Invalid account credentials detected.'));
    }

    $cache_days = $form_state->getValue(['shrinktheweb_cache_days']);
    if (!empty($cache_days)) {
      if (!is_numeric($cache_days)) {
        $form_state->setErrorByName('shrinktheweb_cache_days', t('You must enter an integer for days in cache.'));
      }
      else {
        if ($cache_days < -1) {
          $form_state->setErrorByName('shrinktheweb_cache_days', t('Number of days in cache must be greater then -1.'));
        }
      }
    }

    $thumb_size_custom = $form_state->getValue([
      'shrinktheweb_thumb_size_custom'
    ]);
    if (!empty($thumb_size_custom)) {
      if (!is_numeric($thumb_size_custom)) {
        $form_state->setErrorByName('shrinktheweb_thumb_size_custom', t('You must enter an integer for custom thumb size.'));
      }
      else {
        if ($thumb_size_custom < 0) {
          $form_state->setErrorByName('shrinktheweb_thumb_size_custom', t('Custom thumb size must be positive.'));
        }
      }
    }
    $max_height = $form_state->getValue(['shrinktheweb_max_height']);
    if (!empty($max_height)) {
      if (!is_numeric($max_height)) {
        $form_state->setErrorByName('shrinktheweb_max_height', t('You must enter an integer for maximum height.'));
      }
      else {
        if ($max_height < 0) {
          $form_state->setErrorByName('shrinktheweb_max_height', t('Maximum height must be positive.'));
        }
      }
    }
    $native_res = $form_state->getValue(['shrinktheweb_native_res']);
    if (!empty($native_res)) {
      if (!is_numeric($native_res)) {
        $form_state->setErrorByName('shrinktheweb_native_res', t('You must enter an integer for native resolution.'));
      }
      else {
        if ($native_res < 0) {
          $form_state->setErrorByName('shrinktheweb_native_res', t('Native resolution must be positive.'));
        }
      }
    }
    $widescreen_y = $form_state->getValue(['shrinktheweb_widescreen_y']);
    if (!empty($widescreen_y)) {
      if (!is_numeric($widescreen_y)) {
        $form_state->setErrorByName('shrinktheweb_widescreen_y', t('You must enter an integer for widescreen Y.'));
      }
      else {
        if ($widescreen_y < 0) {
          $form_state->setErrorByName('shrinktheweb_widescreen_y', t('Widescreen Y must be positive.'));
        }
      }
    }
    $delay = $form_state->getValue(['shrinktheweb_delay']);
    if (!empty($delay)) {
      if (!is_numeric($delay)) {
        $form_state->setErrorByName('shrinktheweb_delay', t('You must enter an integer for delay.'));
      }
      else {
        if ($delay < 0 || $delay > 45) {
          $form_state->setErrorByName('shrinktheweb_delay', t('Delay must be between 0 and 45.'));
        }
      }
    }
    $quality = $form_state->getValue(['shrinktheweb_quality']);
    if (!empty($quality)) {
      if (!is_numeric($quality)) {
        $form_state->setErrorByName('shrinktheweb_quality', t('You must enter an integer for quality.'));
      }
      else {
        if ($quality < 0 || $quality > 100) {
          $form_state->setErrorByName('shrinktheweb_quality', t('Quality must be between 0 and 100.'));
        }
      }
    }
    $token = $form_state->getValue(['shrinktheweb_token']);
    if (!empty($token)) {
      if (mb_strlen($token) < 32) {
        $form_state->setErrorByName('shrinktheweb_token', t('Token should contain 32 symbols'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('shrinktheweb.settings');

    if ($config->get('shrinktheweb_cache_days') != '-1' && $form_state->getValue('shrinktheweb_refresh_thumbnails') != TRUE) {
      module_load_include('inc', 'shrinktheweb', 'shrinktheweb.api');
      shrinktheweb_deleteAllImages();
    }

    entity_render_cache_clear();

    $config->set('shrinktheweb_access_key', $form_state->getValue('shrinktheweb_access_key'))
      ->set('shrinktheweb_secret_key', $form_state->getValue('shrinktheweb_secret_key'))
      ->set('shrinktheweb_thumb_size_custom', $form_state->getValue('shrinktheweb_thumb_size_custom'))
      ->set('shrinktheweb_max_height', $form_state->getValue('shrinktheweb_max_height'))
      ->set('shrinktheweb_native_res', $form_state->getValue('shrinktheweb_native_res'))
      ->set('shrinktheweb_widescreen_y', $form_state->getValue('shrinktheweb_widescreen_y'))
      ->set('shrinktheweb_delay', $form_state->getValue('shrinktheweb_delay'))
      ->set('shrinktheweb_quality', $form_state->getValue('shrinktheweb_quality'))
      ->set('shrinktheweb_custom_msg_url', $form_state->getValue('shrinktheweb_custom_msg_url'))
      ->set('shrinktheweb_token', $form_state->getValue('shrinktheweb_token'))
      ->set('shrinktheweb_full_size', $form_state->getValue('shrinktheweb_full_size'));

    //Make default settings not to be overriden by the form submits before access and secret keys were set
    if ($form_state->getValue('shrinktheweb_thumb_size') != ''){
      $config->set('shrinktheweb_thumb_size', $form_state->getValue('shrinktheweb_thumb_size'));
    }
    if ($form_state->getValue('shrinktheweb_cache_days') != ''){
      $config->set('shrinktheweb_cache_days', $form_state->getValue('shrinktheweb_cache_days'));
    }
    if ($form_state->getValue('shrinktheweb_thumbs_folder') != ''){
      $config->set('shrinktheweb_thumbs_folder', $form_state->getValue('shrinktheweb_thumbs_folder'));
    }
    if ($form_state->getValue('shrinktheweb_debug') != ''){
      $config->set('shrinktheweb_debug', $form_state->getValue('shrinktheweb_debug'));
    }
    if ($form_state->getValue('shrinktheweb_notifynopush') != '') {
      $config->set('shrinktheweb_notifynopush', $form_state->getValue('shrinktheweb_notifynopush'));
    }
    if ($form_state->getValue('shrinktheweb_enable_https') != '') {
      $config->set('shrinktheweb_enable_https', $form_state->getValue('shrinktheweb_enable_https'));
    }

    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Realize form checkbox handlers.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function _submitForm(array &$form, FormStateInterface $form_state) {
    module_load_include('inc', 'shrinktheweb', 'shrinktheweb.api');
    $clear_imagecache = $form_state->getValue('shrinktheweb_clear_imagecache');
    if (!empty($clear_imagecache)) {
      if ($clear_imagecache == TRUE) {
        shrinktheweb_deleteAllImages();
        drupal_set_message(t('All cached thumbshots got deleted'));
      }
    }
    $clear_errorcache = $form_state->getValue('shrinktheweb_clear_errorcache');
    if (!empty($clear_errorcache)) {
      if ($clear_errorcache == TRUE) {
        shrinktheweb_deleteErrorImages();
        drupal_set_message(t('All cached error images got deleted'));
      }
    }
    $refresh_thumbnails = $form_state->getValue('shrinktheweb_refresh_thumbnails');
    if (!empty($refresh_thumbnails)) {
      if ($refresh_thumbnails == TRUE) {

        $shrinktheweb_fields = \Drupal::database()->select('shrinktheweb_fields', 't')
          ->fields('t', array(
            'stw_entity_bundle',
            'stw_field_name',
          ))
          ->execute();

        $fields = array();
        $field = $shrinktheweb_fields->fetchAssoc();
        while ($field) {
          $fields = array_merge_recursive($fields, array($field['stw_entity_bundle'] => $field['stw_field_name']));
          $field = $shrinktheweb_fields->fetchAssoc();
        }

        $urls = array();
        foreach ($fields as $bundle => $field_list) {
          $nids = \Drupal::entityQuery('node')
            ->condition('type', $bundle)
            ->execute();
          $nodes = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadMultiple($nids);

          foreach ($nodes as $node) {
            if (is_array($field_list)) {
              foreach ($field_list as $field) {
                $node_array = $node->toArray();
                if (!empty($node_array[$field]['0']['uri'])) {
                  $urls[] = $node_array[$field]['0']['uri'];
                }
              }
            }
            elseif (!empty($field_list)) {
              $node_array = $node->toArray();
              $urls[] = $node_array[$field_list]['0']['uri'];
            }
          }
        }

        foreach ($urls as $url) {
          $operations_request[] = array(
            'requestThumbnailsRefresh',
            array($url)
          );
        }

        if (count($operations_request) > 0) {
          $batch = array(
            'operations' => $operations_request,
            'finished' => 'requestThumbnailsRefreshFinished',
            'title' => t('Requesting thumbnails refresh'),
            'init_message' => t('Preparing to request thumbnails refresh'),
            'progress_message' => t('Requested refresh for @current of @total thumbnails.'),
            'error_message' => t('An error has occurred.'),
            'file' => drupal_get_path('module', 'shrinktheweb') . '/src/RefreshThumbnailsBatch.inc',
          );

          batch_set($batch);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['shrinktheweb.settings'];
  }

}