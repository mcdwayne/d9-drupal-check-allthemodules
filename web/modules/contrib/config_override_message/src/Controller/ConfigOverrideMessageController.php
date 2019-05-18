<?php

namespace Drupal\config_override_message\Controller;

use Drupal\config_override_message\ConfigOverrideMessageManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller for the config override message module.
 */
class ConfigOverrideMessageController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * The module extension list provider.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The config override message manager.
   *
   * @var \Drupal\config_override_message\ConfigOverrideMessageManagerInterface
   */
  protected $manager;

  /**
   * Constructs a ConfigOverrideMessageController object.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list provider.
   * @param \Drupal\config_override_message\ConfigOverrideMessageManagerInterface $config_override_message_manager
   *   The config override message manager.
   */
  public function __construct(ModuleExtensionList $module_extension_list, ConfigOverrideMessageManagerInterface $config_override_message_manager) {
    $this->moduleExtensionList = $module_extension_list;
    $this->manager = $config_override_message_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('extension.list.module'),
      $container->get('config_override_message.manager')
    );
  }

  /**
   * Builds a page listing configuration override messages.
   *
   * @return array
   *   A render array representing a table of configuration override messages.
   */
  public function overview() {
    $header = [
      $this->t('Config name'),
      $this->t('Source / Module'),
      $this->t('Message'),
      $this->t('Paths'),
    ];

    $overrides = $this->manager->getOverrides();

    $rows = [];

    // Site.
    foreach ($overrides['site'] as $config_name => $config_data) {
      $rows[] = $this->buildRow($config_name, $this->t('Site wide'), $config_data);
    }

    // Modules.
    foreach ($overrides['modules'] as $module_name => $configs) {
      foreach ($configs as $config_name => $config_data) {
        $module_info = $this->moduleExtensionList->get($module_name);
        $source = $this->t('@module (@name)', [
          '@module' => $module_info->info['name'],
          '@name' => $module_name,
        ]);
        $rows[] = $this->buildRow($config_name, $source, $config_data);
      }
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

  /**
   * Build table row.
   *
   * @param string $config_name
   *   Config file name.
   * @param string $source
   *   Config file source.
   * @param array $data
   *   Config data.
   *
   * @return array
   *   A table row.
   */
  protected function buildRow($config_name, $source, array $data) {
    $row = [];
    $row[] = $config_name;
    $row[] = $source;
    if (isset($data['_config_override_message'])) {
      $row[] = $data['_config_override_message'];
    }
    else {
      $row[] = '';
    }
    if (isset($data['_config_override_paths'])) {
      $links = [];
      foreach ($data['_config_override_paths'] as $path) {
        $links[] = [
          '#type' => 'link',
          '#title' => $path,
          '#url' => Url::fromUri("base:$path"),
          '#suffix' => '<br/>',
        ];
      }
      $row[] = ['data' => $links];
      $row[] = '';
    }
    else {
      $row[] = '';
    }
    return $row;
  }

}
