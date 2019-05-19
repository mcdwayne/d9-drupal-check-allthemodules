<?php

namespace Drupal\sir_trevor\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\sir_trevor\Plugin\SirTrevorBlockPlugin;
use Drupal\sir_trevor\Plugin\SirTrevorPluginManagerInterface;
use Drupal\sir_trevor\Plugin\SirTrevorPlugin;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @FieldWidget(
 *   id = "sir_trevor",
 *   label = @Translation("Sir Trevor"),
 *   multiple_values = TRUE,
 *   field_types = {
 *     "sir_trevor"
 *   }
 * )
 */
class SirTrevor extends WidgetBase implements ContainerFactoryPluginInterface {
  /** @var \Drupal\sir_trevor\Plugin\SirTrevorPluginManagerInterface  */
  private $pluginManager;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array_merge(parent::defaultSettings(), [
      'enabled_blocks' => [],
      'default_block' => 'Text',
    ]);
  }

  /**
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\sir_trevor\Plugin\SirTrevorPluginManagerInterface $pluginManager
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, SirTrevorPluginManagerInterface $pluginManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->pluginManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $widget = [
      '#type' => 'textarea',
      '#default_value' => $items->getString(),
      '#attributes' => [
        'data-sir-trevor-field-name' => [$items->getName()],
      ],
      '#attached' => [
        'library' => $this->getLibraries(),
      ],
    ];

    $widget['#attached']['drupalSettings']['sirTrevor'][$items->getName()] = $this->getJavascriptSettings();

    return ['json' => $widget];
  }

  /**
   * @return string[]
   */
  private function getJavascriptSettings() {
    $settings['blockTypes'] = $this->getSirTrevorIdentifiersForEnabledBlocks();
    $settings['defaultType'] = $this->getSetting('default_block');
    return $settings;
  }

  /**
   * @return string[]
   */
  private function getEnabledBlocksSetting() {
    return array_filter($this->getSetting('enabled_blocks'));
  }

  /**
   * @return string[]
   */
  private function getSirTrevorIdentifiersForEnabledBlocks() {
    $globallyEnabled = $this->pluginManager->getEnabledBlocks();
    $allBlocks = $this->pluginManager->getBlocks();

    $globallyEnabledBlockTypes = [];
    foreach ($globallyEnabled as $block) {
      $globallyEnabledBlockTypes[] = $this->blockTypeToSirTrevorIdentifier($block->getMachineName());
    }

    $blockTypes = [];
    $enabledBlocksSetting = $this->getEnabledBlocksSetting();
    if (empty($enabledBlocksSetting)) {
      if ($globallyEnabled == $allBlocks) {
        return $blockTypes;
      }
      else {
        return $globallyEnabledBlockTypes;
      }
    }

    foreach ($enabledBlocksSetting as $blockType) {
      $identifier = $this->blockTypeToSirTrevorIdentifier($blockType);
      if (in_array($identifier, $globallyEnabledBlockTypes)) {
        $blockTypes[] = $identifier;
      }
    }

    return $blockTypes;
  }

  /**
   * @param string $blockTypeName
   * @return string
   */
  private function blockTypeToSirTrevorIdentifier($blockTypeName) {
    $parts = explode('_', $blockTypeName);

    array_walk($parts, function (&$val) {
      $val = ucfirst($val);
    });

    return implode('', $parts);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(){
    $enabled_blocks = $this->getEnabledBlocksSetting();
    if (empty($enabled_blocks)) {
      $enabled_blocks = 'All';
    }
    else {
      sort($enabled_blocks);
      $enabled_blocks = implode(', ', $enabled_blocks);
    }

    $summary = [
      $this->t('Enabled blocks: @blocks', ['@blocks' => $enabled_blocks]),
      $this->t('Default block: @block', ['@block' => $this->machineNameToHumanReadable($this->getSetting('default_block'))]),
    ];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $formState) {
    $availableBlockOptions = $this->getAvailableBlockOptions();

    $form['enabled_blocks'] = [
      '#title' => $this->t('Enabled blocks'),
      '#type' => 'checkboxes',
      '#options' => $availableBlockOptions,
      '#default_value' => $this->getSetting('enabled_blocks'),
    ];

    $form['default_block'] = [
      '#title' => $this->t('Default block'),
      '#type' => 'select',
      '#options' => $availableBlockOptions,
      '#default_value' => $this->getSetting('default_block'),
    ];

    return $form;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.sir_trevor')
    );
  }

  /**
   * @return array
   */
  private function getAvailableBlockOptions() {
    $options = [];

    /** @var SirTrevorBlockPlugin $plugin */
    foreach ($this->pluginManager->getEnabledBlocks() as $plugin) {
      $humanReadable = $this->machineNameToHumanReadable($plugin->getMachineName());
      $options[$plugin->getMachineName()] = $this->t($humanReadable);
    };

    return $options;
  }

  /**
   * @return array
   */
  private function getLibraries() {
    $libraries = [
      'sir_trevor/sir-trevor',
    ];

    foreach ($this->pluginManager->createInstances() as $plugin) {
      $libraries[] = "{$plugin->getDefiningModule()}/{$plugin::getType()}.{$plugin->getMachineName()}.editor";
    }

    return $libraries;
  }

  /**
   * @param string $machineName
   * @return string
   */
  private function machineNameToHumanReadable($machineName) {
    $name = ucfirst($machineName);
    $name = str_replace('_', ' ', $name);
    return $name;
  }
}
