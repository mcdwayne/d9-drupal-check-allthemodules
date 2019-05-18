<?php

namespace Drupal\pdb_vue\Plugin\Block;

use Drupal\pdb\Plugin\Block\PdbBlock;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\pdb_vue\Render\VueMarkup;

/**
 * Exposes a Vue component as a block.
 *
 * @Block(
 *   id = "vue_component",
 *   admin_label = @Translation("Vue component"),
 *   deriver = "\Drupal\pdb_vue\Plugin\Derivative\VueBlockDeriver"
 * )
 */
class VueBlock extends PdbBlock implements ContainerFactoryPluginInterface {

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Creates a VueBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $info = $this->getComponentInfo();
    $machine_name = $info['machine_name'];
    $config = $this->configFactory->get('pdb_vue.settings');
    $template = '';

    $build = parent::build();
    if (isset($config) && $config->get('use_spa') && isset($info['component']) && $info['component']) {
      // Create markup string of component properties.
      $props_string = $this->buildPropertyString();

      $build['#allowed_tags'] = [$machine_name];
      $build['#markup'] = '<' . $machine_name . $props_string . ' instance-id="' . $this->configuration['uuid'] . '"></' . $machine_name . '>';
    }
    else {
      // Use raw HTML if a template is provided.
      if (!empty($info['template'])) {
        $template = file_get_contents($info['path'] . '/' . $info['template']);
      }

      $build['#markup'] = VueMarkup::create('<div class="' . $machine_name . '" id="' . $this->configuration['uuid'] . '">' . $template . '</div>');
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function attachSettings(array $component) {
    $attached = [];

    $config = $this->configFactory->get('pdb_vue.settings');
    if (isset($config)) {
      $attached['drupalSettings']['pdbVue']['developmentMode'] = $config->get('development_mode');
      if ($config->get('use_spa')) {
        $attached['drupalSettings']['pdbVue']['spaElement'] = $config->get('spa_element');
      }
    }
    else {
      $attached['drupalSettings']['pdbVue']['developmentMode'] = FALSE;
    }

    return $attached;
  }

  /**
   * {@inheritdoc}
   */
  public function attachLibraries(array $component) {
    $config = $this->configFactory->get('pdb_vue.settings');

    // Get inline assets.
    $libraries = parent::attachLibraries($component);

    // Add existing libraries which have already been registered with Drupal.
    if (isset($component['libraries'])) {
      // Add the main Vue library if there are no inline libraries. Normally an
      // inline library will already have Vue as a dependency.
      if (empty($libraries)) {
        $libraries[] = 'pdb_vue/vue';
      }

      // Add libraries attached in the block's .info file. An inline script will
      // automatically have these libraries as dependencies.
      // @see pdb_vue_library_info_alter().
      $libraries = array_merge($libraries, $component['libraries']);
    }

    if (isset($config) && $config->get('use_spa') && isset($component['component']) && $component['component']) {
      $libraries[] = 'pdb_vue/vue.spa-init';
    }

    $library_attachments = [
      'library' => $libraries,
    ];

    return $library_attachments;
  }

  /**
   * Create a string of Vue Component property parameters.
   *
   * @return string
   *   A string of property elements to inject into the DOM directive.
   */
  public function buildPropertyString() {
    $props_string = '';

    if (isset($this->configuration['pdb_configuration'])) {
      $props = $this->configuration['pdb_configuration'];

      foreach ($props as $field => $value) {
        // Determine if the property should use v-bind (:shorthand) due to the
        // value being numeric.
        $bind = '';
        if (is_numeric($value)) {
          $bind = ':';
        }

        // Convert camelCase to kebab-case.
        $field = $this->convertKebabCase($field);

        // Create the string of properties.
        $props_string .= ' ' . $bind . $field . '="' . $value . '"';
      }
    }

    return $props_string;
  }

  /**
   * Convert camelCase to kebab-case.
   *
   * See https://vuejs.org/v2/guide/components.html#camelCase-vs-kebab-case.
   */
  public function convertKebabCase($value) {
    return ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '-$0', $value)), '-');
  }

}
