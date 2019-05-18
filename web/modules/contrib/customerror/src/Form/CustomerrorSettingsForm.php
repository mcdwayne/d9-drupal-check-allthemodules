<?php

namespace Drupal\customerror\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Link;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides a form controller for module settings.
 */
class CustomerrorSettingsForm extends ConfigFormBase {
  protected $aliasManager;

  /**
   *
   */
  public function __construct(ConfigFactory $config_factory, AliasManagerInterface $alias_manager) {
    parent::__construct($config_factory);
    $this->aliasManager = $alias_manager;
  }


  /**
   * This method lets us inject the services this class needs.
   *
   * Only inject services that are actually needed. Which services
   * are needed will vary by the controller.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.alias_manager')
    );
  }


  /**
   * Implements get function for form ID.
   */
  public function getFormId() {
    return 'customerror_settings_form';
  }


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'customerror.settings',
    ];
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('customerror.settings');

    $form['customerror_form_description'] = [
      '#markup' => $this->t('Enter the error pages that will be seen by your visitors when they get the respective errors. You can enter any HTML text. You can point the users to the FAQ, inform them that you reorganized the site, ask them to report the error, login or register, ...etc.'),
    ];

    $themes = \Drupal::service('theme_handler')->listInfo();
    ksort($themes);
    $theme_options[''] = $this->t('System default');
    foreach ($themes as $key => $theme) {
      $theme_options[$key] = $theme->info['name'];
    }

    $errors = [
      403 => $this->t('access denied'),
      404 => $this->t('requested page not found'),
    ];
    foreach ($errors as $code => $desc) {
      if (\Drupal::config('system.site')->get("page.$code") != "/customerror/{$code}") {
        drupal_set_message($this->t('Custom error is not configured for @error errors. Please ensure that the default @error page is set to be /customerror/@error on the @link.', ['@error' => $code, '@link' => Link::createFromRoute($this->t('Site information settings page'), 'system.site_information_settings')->toString()]), 'error', FALSE);
      }
    }
    foreach ($errors as $code => $desc) {
      $form[$code] = [
        '#type'  => 'fieldset',
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
        '#title' => $this->t('Code @code settings', ['@code' => $code]),
        '#tree' => TRUE,
      ];
      $form[$code]["title"] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('Title for @code', ['@code' => $code]),
        '#maxlength'     => 70,
        '#description'   => $this->t('Title of @code error page', ['@code' => $code]),
        '#default_value' => $config->get("{$code}.title"),
      ];
      $form[$code]["body"] = [
        '#type'          => 'textarea',
        '#title'         => $this->t('Description for @code', ['@code' => $code]),
        '#rows'          => 10,
        '#description'   => $this->t('This text will be displayed if a @code (@desc) error occurs.', ['@code' => $code, '@desc' => $desc]),
        '#default_value' => $config->get("{$code}.body"),
      ];
      $form[$code]["theme"] = [
        '#type'          => 'select',
        '#options'       => $theme_options,
        '#title'         => $this->t('Theme'),
        '#description'   => $this->t('Theme to be used on the error page.'),
        '#default_value' => $config->get("{$code}.theme"),
      ];
    }

    $form['redirects'] = [
      '#type'  => 'details',
      '#title' => $this->t('Redirects'),
      '#open'  => FALSE,
    ];
    $form['redirects']['redirect'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Redirect list'),
      '#rows'          => 10,
      '#description'   => t('These are custom redirect pairs, one per line. Each pair requires a path to match (which is a regular expression) and a destination separated by a space. The keyword <em>&lt;front></em> is allowed as a destination. If you are unfamilar with regular expressions, a simple search string will work, but will match any part of the URl. For example <em>index.html &lt;front></em> will match both <em>http://example.com/index.html &amp; http://example.com/archive/index.html</em>.'),
      '#default_value' => $config->get('redirect'),
    ];

    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('customerror.settings');

    $form_state->cleanValues();

    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }
}
