<?php

namespace Drupal\upgrade_tool\Controller;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Diff\DiffFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for config module routes.
 */
class UpgradeLogConfigController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The target storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $targetStorage;

  /**
   * The source storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $sourceStorage;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The diff formatter.
   *
   * @var \Drupal\Core\Diff\DiffFormatter
   */
  protected $diffFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.storage'),
      $container->get('config.manager'),
      $container->get('diff.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(StorageInterface $target_storage, ConfigManagerInterface $config_manager, DiffFormatter $diff_formatter) {
    $this->targetStorage = $target_storage;
    $this->configManager = $config_manager;
    $this->diffFormatter = $diff_formatter;
  }

  /**
   * Returns diff between configs.
   *
   * @param \Drupal\Core\Entity\EntityInterface $upgrade_log
   *   Logger entity.
   *
   * @return array
   *   Render array with diff table.
   */
  public function diff(EntityInterface $upgrade_log) {
    $source_name = $upgrade_log->getName();
    if (!$upgrade_log->getConfigPath()) {
      return [
        '#markup' => $this->t('There are no storage directory path.'),
      ];
    }
    // Get source_dir from config_path.
    $config_path = $upgrade_log->getConfigPath();
    $source_dir = substr($config_path, 0, strripos($config_path, '/'));
    // Create sourceStorage for config directory.
    $this->sourceStorage = new FileStorage($source_dir);
    $diff = $this->configManager->diff($this->targetStorage, $this->sourceStorage, $source_name, NULL, NULL);
    $this->diffFormatter->show_header = FALSE;

    $build = [];
    $build['#title'] = $this->t('View changes of @config_file', ['@config_file' => $source_name]);
    // Add the CSS for the inline diff.
    $build['#attached']['library'][] = 'system/diff';
    $build['diff'] = [
      '#type' => 'table',
      '#attributes' => ['class' => ['diff']],
      '#header' => [
        ['data' => $this->t('Active'), 'colspan' => '2'],
        ['data' => $this->t('Staged'), 'colspan' => '2'],
      ],
      '#rows' => $this->diffFormatter->format($diff),
    ];
    return $build;

  }

}
