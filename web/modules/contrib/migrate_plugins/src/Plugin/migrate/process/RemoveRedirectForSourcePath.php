<?php

namespace Drupal\migrate_plugins\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\redirect\RedirectRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Removes redirect that match a given source path.
 *
 * @MigrateProcessPlugin(
 *  id = "remove_redirect_for_source_path"
 * )
 */
class RemoveRedirectForSourcePath extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Redirect repository service.
   *
   * @var \Drupal\redirect\RedirectRepository
   */
  protected $redirectRepository;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, MigrationInterface $migration, RedirectRepository $redirectRepository) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->redirectRepository = $redirectRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $migration,
      $container->get('redirect.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($value) && is_string($value)) {
      // The source path must start with leading slash.
      $source = trim($value, '/');

      // Delete the redirects that match source path.
      if ($redirects = $this->redirectRepository->findBySourcePath($source)) {
        foreach ($redirects as $redirect) {
          $redirect->delete();
        }
      }
    }

    return $value;
  }

}
