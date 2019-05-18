<?php

namespace Drupal\developer_module\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Yaml\Yaml;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\developer_module\Controller\DeveloperModuleGetMethods;

/**
 * Configure developer_module settings for this site.
 */
class Configuration extends ConfigFormBase {

  private $developerModuleGetMethods;

  /**
   * Created cutructor for accessing service methods.
   */
  public function __construct(DeveloperModuleGetMethods $developerModuleGetMethods) {
    $this->developerModuleGetMethods = $developerModuleGetMethods;
  }

  /**
   * Created create function for accessing container interface.
   */
  public static function create(ContainerInterface $container) {
    return new static(
    // Load the service required to construct this class.
      $container->get('developer_module.access_methods')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'developer_module_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'developer_module.formsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('developer_module.formsettings');

    $form['panel'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Developer Configuration'),
      '#open' => TRUE,
    ];

    $form['panel']['enable_theme_debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Theme Debug'),
      '#description'  => $this->t('This will allow to theme debug.'),
      '#default_value' => $config->get('enable_theme_debug'),
    ];

    $form['panel']['disable_cache_during_development'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Cache During Development'),
      '#description'  => $this->t('This will allow to disable cache during development for authenicated user.'),
      '#default_value' => $config->get('disable_cache_during_development'),
    ];

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $theme_debug = $form_state->getValue('enable_theme_debug');
    $disable_cache_during_development = $form_state->getValue('disable_cache_during_development');
    $path = DRUPAL_ROOT . '/modules/developer_module/developer_module.services.yml';
    $getMethodService = $this->developerModuleGetMethods;
    $theme_debug_flag = $getMethodService->getEnableThemeDebug();
    $disable_cache_during_development_flag = $getMethodService->getDisableCacheDuringDevelopment();

    $clear_cache_function_flag = 0;
    if ($theme_debug == 1) {
      $twig_debug_mode = TRUE;

    }
    else {
      $twig_debug_mode = FALSE;
    }

    if ($disable_cache_during_development == 1) {
      $twig_cache = FALSE;
      $twig_auto_reload = TRUE;
      $twig_http_response_debug_cacheability_headers = TRUE;
    }
    else {
      $twig_cache = TRUE;
      $twig_auto_reload = FALSE;
      $twig_http_response_debug_cacheability_headers = FALSE;
    }

    $mySetting = Settings::get('container_yamls', NULL);
    if (file_exists($mySetting[0])) {
      $path = $mySetting[0];
    }

    /* Update YML File : Theme Debug Enable*/
    $yaml = Yaml::parse(file_get_contents($path));

    $yaml['parameters']['twig.config']['debug'] = $twig_debug_mode;
    $yaml['parameters']['twig.config']['auto_reload'] = $twig_auto_reload;
    $yaml['parameters']['twig.config']['cache'] = $twig_cache;
    $yaml['parameters']['twig.config']['http.response.debug_cacheability_headers'] = $twig_http_response_debug_cacheability_headers;

    if ($theme_debug_flag !== $theme_debug) {
      $clear_cache_function_flag++;
    }

    if ($disable_cache_during_development_flag !== $disable_cache_during_development) {

      if ($disable_cache_during_development == 0) {
        unset($yaml['services']["cache.backend.null"]);
      }
      else {
        $yaml["services"]["cache.backend.null"]["class"] = "Drupal\Core\Cache\NullBackendFactory";
      }
      $yaml['parameters']['twig.config']['http.response.debug_cacheability_headers'] = $twig_http_response_debug_cacheability_headers;
      $clear_cache_function_flag++;
    }

    $new_yaml = Yaml::dump($yaml, 3);
    file_put_contents($path, $new_yaml);
    $yaml = Yaml::parse(file_get_contents($path));
    if ($clear_cache_function_flag > 0) {
      drupal_flush_all_caches();
    }
    $this->config('developer_module.formsettings')
      ->set('enable_theme_debug', $form_state->getValue('enable_theme_debug'))
      ->set('disable_cache_during_development', $form_state->getValue('disable_cache_during_development'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
