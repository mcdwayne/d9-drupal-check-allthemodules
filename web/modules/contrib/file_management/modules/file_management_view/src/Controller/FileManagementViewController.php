<?php

namespace Drupal\file_management_view\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\views\Views;

/**
 * Provides route responses for the PHSG Workflows module.
 */
class FileManagementViewController extends ControllerBase {

  /**
   * Returns the file overview page.
   *
   * @return array
   *   An array as expected by \Drupal\Core\Render\RendererInterface::render().
   */
  public function getOverview() {
    $build = $this->getView('file_management', 'overview');

    return $build;
  }

  /**
   * Returns the file overview page title.
   *
   * @return string
   *   The page title.
   */
  public function getOverviewTitle() {
    return $this->t('Files');
  }

  /**
   * Returns the file usage page.
   *
   * @param int $fid
   *   The file id of the file to load.
   *
   * @return array
   *   An array as expected by \Drupal\Core\Render\RendererInterface::render().
   */
  public function getFileUsage($fid) {
    $build = $this->getView('file_management', 'usage', [$fid]);

    return $build;
  }

  /**
   * Returns the file usage page title.
   *
   * @param int $fid
   *   The file id of the file to load.
   *
   * @return string
   *   The page title.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function getFileUsageTitle($fid) {
    $file = \Drupal::entityTypeManager()->getStorage('file')->load($fid);

    return $this->t(
      'File usage information for @file',
      ['@file' => $file->getFilename()]
    );
  }

  /**
   * Builds a renderable array for the given view and display id.
   *
   * @param string $viewName
   *   The view name.
   * @param string $displayId
   *   The display id.
   * @param array $arguments
   *   The arguments for this particular view and display id.
   *
   * @return array
   *   The renderable array.
   */
  protected function getView($viewName, $displayId, $arguments=[]) {
    $view = Views::getView($viewName);
    $view->setDisplay($displayId);
    $view->setArguments($arguments);
    $view->preExecute();
    $view->execute($displayId);

    return $view->buildRenderable($displayId, $arguments, FALSE);
  }
}
