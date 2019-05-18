<?php

namespace Drupal\popup_message\Form;

define('POPUP_MESSAGE_CSS_NAME', 'popup.css');

use Drupal\Core\Asset\CssCollectionOptimizer;
use Drupal\Core\Asset\JsCollectionOptimizer;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PopupMessageSettingsForm.
 *
 * @package Drupal\popup_message\Form
 */
class PopupMessageSettingsForm extends ConfigFormBase {

  /**
   * CssCollectionOptimizer service.
   *
   * @var \Drupal\Core\Asset\CssCollectionOptimizer
   */
  protected $cssOptimizer;

  /**
   * JsCollectionOptimizer service.
   *
   * @var \Drupal\Core\Asset\JsCollectionOptimizer
   */
  protected $jsOptimizer;

  /**
   * PopupMessageSettingsForm constructor.
   *
   * @param \Drupal\Core\Asset\CssCollectionOptimizer $cssOptimizer
   *   Load service css collection optimizer.
   * @param \Drupal\Core\Asset\JsCollectionOptimizer $jsOptimizer
   *   Load service js collection optimizer.
   */
  public function __construct(CssCollectionOptimizer $cssOptimizer, JsCollectionOptimizer $jsOptimizer) {
    $this->cssOptimizer = $cssOptimizer;
    $this->jsOptimizer = $jsOptimizer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('asset.css.collection_optimizer'),
      $container->get('asset.js.collection_optimizer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'popup_message_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('popup_message.settings');

    $form['popup_message_enable'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Enable Popup'),
      '#default_value' => $config->get('enable') ? $config->get('enable') : 0,
      '#options' => array(
        1 => $this->t('Enabled'),
        0 => $this->t('Disabled'),
      ),
    );
    $form['popup_message_fieldset'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Popup message settings'),
      '#collapsed' => FALSE,
      '#collapsible' => TRUE,
    );
    $form['popup_message_fieldset']['popup_message_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Message title'),
      '#required' => TRUE,
      '#default_value' => $config->get('title'),
    );

    $popup_message_body = $config->get('body');

    $form['popup_message_fieldset']['popup_message_body'] = array(
      '#type' => 'text_format',
      '#base_type' => 'textarea',
      '#title' => $this->t('Message body'),
      '#default_value' => $popup_message_body['value'],
      '#format' => isset($popup_message_body['format']) ? $popup_message_body['format'] : NULL,
    );
    $form['popup_message_fieldset']['popup_message_width'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Window width'),
      '#required' => TRUE,
      '#default_value' => empty($config->get('width')) ? 300 : $config->get('width'),
    );
    $form['popup_message_fieldset']['popup_message_height'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Window height'),
      '#required' => TRUE,
      '#default_value' => empty($config->get('height')) ? 300 : $config->get('height'),
    );
    $form['popup_message_fieldset']['popup_message_check_cookie'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Check cookie'),
      '#description' => $this->t('If enabled message will be displayed only once per browser session'),
      '#default_value' => $config->get('check_cookie') ? $config->get('check_cookie') : 0,
      '#options' => array(
        1 => $this->t('Enabled'),
        0 => $this->t('Disabled'),
      ),
    );
    $form['popup_message_fieldset']['popup_message_delay'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Delay'),
      '#description' => $this->t('Message will show after this number of seconds. Set to 0 to show instantly.'),
      '#default_value' => $config->get('delay') ? $config->get('delay') : 0,
    );

    // Styles.
    // Find styles in module directory.
    $directory = drupal_get_path('module', 'popup_message') . '/styles';
    $subdirectories = scandir($directory);
    $styles = array();

    foreach ($subdirectories as $subdir) {
      if (is_dir($directory . '/' . $subdir)) {
        if (file_exists($directory . '/' . $subdir . '/' . POPUP_MESSAGE_CSS_NAME)) {
          $path = $directory . '/' . $subdir . '/' . POPUP_MESSAGE_CSS_NAME;
          $lib_path = $subdir . '/' . POPUP_MESSAGE_CSS_NAME;
          $styles[$lib_path] = $path;
        }
      }
    }

    $form['popup_message_fieldset']['popup_message_style'] = array(
      '#type' => 'select',
      '#title' => $this->t('Popup style'),
      '#default_value' => empty($config->get('style')) ? 0 : $config->get('style'),
      '#options' => $styles,
      '#description' => $this->t('To add custom styles create directory and file "modules/popup_message/popup_message_styles/custom_style/popup.css" and set in this file custom CSS code.'),
    );
    $form['popup_message_fieldset']['visibility']['path'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Pages'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#group' => 'visibility',
      '#weight' => 0,
    );
    $options = array(
      $this->t('All pages except those listed'),
      $this->t('Only the listed pages'),
    );
    $description = $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.",
      array(
        '%blog' => 'blog',
        '%blog-wildcard' => 'blog/*',
        '%front' => '<front>',
      )
    );

    $title = $this->t('Pages');
    $form['popup_message_fieldset']['visibility']['path']['popup_message_visibility'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Show block on specific pages'),
      '#options' => $options,
      '#default_value' => $config->get('visibility') ? $config->get('visibility') : 0,
    );

    $form['popup_message_fieldset']['visibility']['path']['popup_message_visibility_pages'] = array(
      '#type' => 'textarea',
      '#default_value' => $config->get('visibility_pages') ? $config->get('visibility_pages') : '',
      '#description' => $description,
      '#title' => '<span class="element-invisible">' . $title . '</span>',
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('popup_message.settings');
    $flush_cache = ($config->get('style') == $form_state->getValue('popup_message_enable')) ? FALSE : TRUE;
    $flush_cache_css = ($config->get('style') == $form_state->getValue('popup_message_style')) ? FALSE : TRUE;
    $flush_cache_js = ($config->get('style') == $form_state->getValue('popup_message_check_cookie')) ? FALSE : TRUE;

    $config->set('enable', $form_state->getValue('popup_message_enable'))
      ->set('title', $form_state->getValue('popup_message_title'))
      ->set('body', $form_state->getValue('popup_message_body'))
      ->set('height', $form_state->getValue('popup_message_height'))
      ->set('width', $form_state->getValue('popup_message_width'))
      ->set('check_cookie', $form_state->getValue('popup_message_check_cookie'))
      ->set('delay', $form_state->getValue('popup_message_delay'))
      ->set('style', $form_state->getValue('popup_message_style'))
      ->set('visibility', $form_state->getValue('popup_message_visibility'))
      ->set('visibility_pages', $form_state->getValue('popup_message_visibility_pages'))
      ->save();

    if ($flush_cache) {
      drupal_flush_all_caches();
    }

    if ($flush_cache_css) {
      $this->cssOptimizer->deleteAll();
    }
    if ($flush_cache_js) {
      $this->jsOptimizer->deleteAll();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'popup_message.settings',
    ];
  }

}
