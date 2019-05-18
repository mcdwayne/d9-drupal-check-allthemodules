<?php

namespace Drupal\config_overridden\Plugin\ConfigFormOverrider;

use Drupal\Component\Utility\NestedArray;
use Drupal\config_overridden\Plugin\ConfigFormOverriderBase;
use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FormOverriderDefault.
 *
 * @package Drupal\config_overriden\Plugin\FormOverrider
 *   FormOveride class.
 *
 * @ConfigFormOverrider(
 *   id = "form_default",
 *   name = @Translation("Default form overrider"),
 *   weight = 10000
 * )
 */
class ConfigFormOverriderDefault extends ConfigFormOverriderBase {


  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory, \Drupal\Core\Config\ConfigFactoryInterface $config_factory, FormBuilder $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory);

    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    return isset($this->getApplicableFormIds()[$this->form_id]);
  }

  protected function getOverrideDefinition() {
    return $this->getApplicableFormIds()[$this->form_id];
  }

  /**
   * Overrides highlighted form.
   */
  public function highlightOverrides() {
    $overridden = $this->filterNotOverriddenProperties();

    // Only load additional CSS and JS if we have overridden elements.
    if (!empty($overridden)) {
      $this->form['#attached']['library'][] = 'config_overridden/config-highlight';
    }

    foreach ($overridden as $property) {
      $form_element = &$this->findFormElementForProperty($property);
      if ($form_element !== NULL) {
        $this->highlightFormElement($form_element, $property);
      }
    }

    $this->form['#config_overridden_processed'] = TRUE;
  }

  /**
   * Highlight Form Element.
   */
  public function highlightFormElement(&$element, $property) {
    $override_definition = $this->getOverrideDefinition();
    $config = $this->getConfig();

    $currentValue = $config->get($property);
    $storedValue = $config->getOriginal($property, FALSE);


    // $suffix2 = $currentValue;.
    $element['#title'] = $this->t(
       '<span class="config-overriden">@element_title(<span class="suffix1">overrides: </span><span class="suffix2">@store_value</span>)</span>)',
         ['@element_title' => $element['#title'], '@store_value' => $storedValue]
       );

    // Disable element if needed.
    if (!empty($override_definition['disable_elements'])) {
      $element['#disabled'] = TRUE;
    }

    if ($storedValue !== NULL && $currentValue !== NULL) {
      $element['#default_value'] = $currentValue;
      $element['#config_overridden_value'] = $storedValue;
      $element['#value_callback'] = [$this, 'configOverriddenValueCalback'];
    }
  }

  /**
   * Config Overridden Value Calback.
   */
  public function configOverriddenValueCalback($element, $input = FALSE, FormStateInterface $form_state) {
    if ($input === FALSE) {
      return isset($element['#default_value']) ? $element['#default_value'] : NULL;
    }
    else {
      return isset($element['#config_overridden_value']) ? $element['#config_overridden_value'] : $input;
    }
  }

  /**
   * Find Form Element For Property.
   */
  protected function &findFormElementForProperty($property) {
    $result = NULL;

    $override_definition = $this->getOverrideDefinition();

    $form = &$this->form;
    $form_path = $property;
    $form_part = &$this->form;
    if (isset($override_definition['search_prefix']) && isset($form[ $override_definition['search_prefix'] ])) {
      $path_parts = explode('.', $override_definition['search_prefix']);
      $form_part = &NestedArray::getValue($form, $path_parts);
    }

    if (isset($override_definition['map'][$property])) {
      // In case if need to disable fake highlighting of some forms.
      if ($override_definition['map'][$property] === FALSE) {
        return $result;
      }

      $form_path = $override_definition['map'][$property];
    }

    $form_path = explode('.', $form_path);
    $result = &NestedArray::getValue($form_part, $form_path);

    return $result;
  }

  /**
   * Filter Not Overridden Properties.
   */
  protected function filterNotOverriddenProperties() {
    $config = $this->getConfig();
    $config_flat = $this->flatConfigs(NULL, $config->get());
    $override_definition = $this->getOverrideDefinition();
    $overridden = [];
    foreach ($config_flat as $property) {
      if (!empty($override_definition['debug']) || ($config->get($property) != $config->getOriginal($property, FALSE))) {
        $overridden[] = $property;
      }
    }

    return $overridden;
  }

  /**
   * Flat Configs.
   */
  protected function flatConfigs($first = NULL, $data = NULL) {
    $configs = [];
    if (is_array($data)) {
      foreach ($data as $_property => $_value) {
        $combined = array_filter([$first, $_property]);
        $configs = array_merge($configs, $this->flatConfigs(implode('.', $combined), $_value));
      }
    }
    elseif ($first !== NULL) {
      $configs[] = $first;
    }

    return $configs;
  }

  /**
   * Get Applicable Form Ids.
   */
  protected function getApplicableFormIds() {
    // @todo: Move to config
    return [
      // @see \Drupal\system\Form\SiteInformationForm
      'system_site_information_settings' => [
        /*
         * Config name.
         * Check Drupal\system\Form\SiteInformationForm::getEditableNames().
         */
        'config_name' => 'system.site',

        /*
         * If set to TRUE, all items will be highlighted as Overridden.
         * Only use for testing mappings.
         * (optional) Default: FALSE
         */
        // 'debug' => FALSE,

        /*  If all your elements on form lives under $form['this']['prefix'].
         *  Access to array elements via dot ('.').
         * (optional) Default: NULL
         */
        // 'search_prefix' => 'this.prefix',

        /*
         * Whether need or not to disable element which is overridden.
         * (optional) Default: TRUE.
         */
        // 'disable_elements' => TRUE,

        /*
         * Mappings.
         * The key is property from $config->get(property), the value is
         * a path to find this element in form array. Use dot ('.') to
         * access to form elements.
         */
        'map' => [
          // Example for $form['site_information']['site_name'].
          'name' => 'site_information.site_name',
          'slogan' => 'site_information.site_slogan',
          'mail' => 'site_information.site_mail',
          'page.front' => 'front_page.site_frontpage',
          'page.404' => 'error_page.site_404',
          'page.403' => 'error_page.site_403',
        ],
      ],
      // @see \Drupal\system\Form\FileSystemForm
      'system_file_system_settings' => [
        'config_name' => 'system.file',
        'map' => [
          'path.temporary' => 'file_temporary_path',
          'default_scheme' => 'file_default_scheme',
        ],
      ],
    ];
  }

  /**
   * Get Form Config.
   * @return Config
   */
  protected function getConfig() {
    $config_name = $this->getApplicableFormIds()[$this->form_id]['config_name'];

    return $this->configFactory->get($config_name);
  }

}
