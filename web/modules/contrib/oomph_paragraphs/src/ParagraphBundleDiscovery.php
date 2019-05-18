<?php

declare(strict_types=1);

namespace Drupal\oomph_paragraphs;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a mechanism to discover paragraph bundles based on template files.
 */
class ParagraphBundleDiscovery {

  /**
   * Templates directory path in this module.
   */
  const TEMPLATES_DIR =  __DIR__ . '/../templates';

  /**
   * The entity type manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Symfony filesystem finder.
   *
   * @var Symfony\Component\Finder\Finder
   */
  protected $finder;

  /**
   * Constructor for ParagraphBundleTemplateDiscovery.
   *
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param Symfony\Component\Finder\Finder $finder
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    Finder $finder = NULL
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->finder = $finder ?: new Finder();
  }

  /**
   * Discover paragraph bundles in the templates directory.
   *
   * @return array
   *   An array of paragraph bundles.
   */
  public function discoverBundles(): array {
    $bundles = [];
    $registeredBundles = $this->entityTypeManager
      ->getStorage('paragraphs_type')
      ->loadMultiple();

    foreach ($this->findParagraphTemplates() as $template) {
      $bundle = $this->extractBundleFromFile($template);

      if ($bundle && array_key_exists($bundle, $registeredBundles)) {
        $bundles[] = $bundle;
      }
    }

    return $bundles;
  }

  /**
   * Extract the bundle name from the file.
   *
   * @param \SplFileInfo $file
   *   An object representing the template file.
   *
   * @return string|null
   *   The name of the bundle or NULL if the file name doesn't match the
   *   paragraph template filename patten.
   */
  protected function extractBundleFromFile(SplFileInfo $file): ?string {
    preg_match(
      '/^paragraph--([a-z\-]+)\.html\.twig$/',
      $file->getFilename(),
      $matches
    );

    return !empty($matches[1]) ? $this->formatBundleName($matches[1]) : NULL;
  }

  /**
   * Find all the paragraph templates in the templates directory.
   *
   * @return Symfony\Component\Finder\Finder
   *   A Symfony filesystem finder object searching in the templates directory.
   */
  protected function findParagraphTemplates(): Finder {
    return $this->finder
      ->files()
      ->in(self::TEMPLATES_DIR)
      ->name('paragraph--*.html.twig');
  }

  /**
   * Format the bundle name to the Drupal bundle machine name standards.
   *
   * @param string $bundle
   *   A bundle name.
   *
   * @return string
   *   A formatted bundle name.
   */
  protected function formatBundleName(string $bundle): string {
    return str_replace('-', '_', $bundle);
  }

}
