<?php

namespace Drupal\sourcepoint\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sourcepoint\CmpInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;

/**
 * Provide settings for Sourcepoint.
 */
class SettingsForm extends ConfigFormBase {
  /**
   * CMP service.
   *
   * @var \Drupal\sourcepoint\CmpInterface
   */
  protected $cmp;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\sourcepoint\CmpInterface $cmp
   *   CMP service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    CmpInterface $cmp
  ) {
    parent::__construct($config_factory);
    $this->cmp = $cmp;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('sourcepoint.cmp')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sourcepoint_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sourcepoint.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $field_type = NULL) {
    $config = $this->config('sourcepoint.settings');

    $form['account_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account ID'),
      '#default_value' => $config->get('account_id'),
      '#required' => TRUE,
    ];

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enabled'),
      '#default_value' => $config->get('enabled'),
    ];

    // Content Control.
    $form['content_control'] = [
      '#type' => 'fieldset',
      '#title' => t('Content Control'),
    ];
    $form['content_control']['rid_enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable Recovery Interference Detection'),
      '#default_value' => $config->get('rid_enabled'),
    ];
    $form['content_control']['content_control_url'] = [
      '#type' => 'textfield',
      '#title' => t('Content Control landing page'),
      '#default_value' => $config->get('content_control_url'),
      '#size' => 50,
      '#description' => t('The Url of the landing page. i.e. http://www.example.com/page'),
    ];

    // Messaging.
    $form['messaging'] = [
      '#type' => 'fieldset',
      '#title' => t('Messaging'),
    ];
    $form['messaging']['mms_domain'] = [
      '#type' => 'textfield',
      '#title' => t('MMS Domain'),
      '#default_value' => $config->get('mms_domain'),
    ];

    // Consent Management Platform.
    $form['cmp'] = [
      '#type' => 'fieldset',
      '#title' => t('Consent Management Platform'),
    ];
    $form['cmp']['cmp_enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable Consent Management Platform'),
      '#default_value' => $config->get('cmp_enabled'),
    ];
    $form['cmp']['cmp_site_id'] = [
      '#type' => 'number',
      '#title' => t('Site ID'),
      '#default_value' => $config->get('cmp_site_id'),
      '#size' => 50,
      '#min' => 0,
    ];
    $form['cmp']['cmp_privacy_manager_id'] = [
      '#type' => 'textfield',
      '#title' => t('Privacy Manager ID'),
      '#default_value' => $config->get('cmp_privacy_manager_id'),
      '#size' => 50,
    ];
    $form['cmp']['cmp_shim_url'] = [
      '#type' => 'textfield',
      '#title' => t('Shim Script URL'),
      '#default_value' => $config->get('cmp_shim_url'),
      '#size' => 50,
    ];
    $form['cmp']['cmp_overlay_height'] = [
      '#type' => 'textfield',
      '#title' => t('Overlay Height'),
      '#default_value' => $config->get('cmp_overlay_height'),
      '#size' => 50,
    ];
    $form['cmp']['cmp_overlay_width'] = [
      '#type' => 'textfield',
      '#title' => t('Overlay Width'),
      '#default_value' => $config->get('cmp_overlay_width'),
      '#size' => 50,
    ];

    // Overlay iframe.
    if ($url = $this->cmp->getUrl()) {
      $link = Link::fromTextAndUrl($url->toString(), $url)->toRenderable();
      $link['#prefix'] = 'Overlay Example:';
      $link['#attributes']['rel'] = 'sourcepoint-cmp-overlay';

      $messages = [];
      $messages[] = $link;
      $messages[] = 'Add rel "sourcepoint-cmp-overlay" to links to open overlay.';

      $form['cmp']['cmp_overlay_demo_url'] = [
        '#theme' => 'item_list',
        '#items' => $messages,
      ];
      $form['cmp']['cmp_overlay_demo'] = $this->cmp->getOverlay();
    }

    // Detection Timeout Management.
    $form['detection'] = [
      '#type' => 'fieldset',
      '#title' => t('Detection Timeout Management'),
    ];

    $form['detection']['dtm_enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable detection timeout'),
      '#default_value' => $config->get('dtm_enabled'),
    ];
    $form['detection']['dtm_timeout'] = [
      '#type' => 'number',
      '#title' => t('Timeout'),
      '#default_value' => $config->get('dtm_timeout'),
      '#description' => t('Detection timeout in milliseconds.'),
      '#min' => 0,
    ];

    // Style Manager.
    $form['style_manager'] = [
      '#type' => 'fieldset',
      '#title' => t('Style Manager'),
    ];
    $form['style_manager']['style_manager_enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable Style Manager'),
      '#default_value' => $config->get('style_manager_enabled'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('sourcepoint.settings');

    $keys = [
      'account_id',
      'enabled',
      'rid_enabled',
      'content_control_url',
      'cmp_enabled',
      'cmp_site_id',
      'mms_domain',
      'cmp_privacy_manager_id',
      'cmp_overlay_height',
      'cmp_overlay_width',
      'cmp_shim_url',
      'dtm_enabled',
      'dtm_timeout',
      'style_manager_enabled',
    ];
    foreach ($keys as $key) {
      $config->set($key, $form_state->getValue($key));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
