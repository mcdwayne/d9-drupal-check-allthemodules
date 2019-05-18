<?php

namespace Drupal\better_subthemes;

use Drupal\block\BlockRepositoryInterface;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Provides a repository for Block config entities.
 */
class BetterSubthemesBlockRepository implements BlockRepositoryInterface {

  /**
   * The decorated block repository.
   *
   * @var \Drupal\better_subthemes\BetterSubthemesManager
   */
  protected $betterSubthemesManager;

  /**
   * The decorated block repository.
   *
   * @var \Drupal\block\BlockRepositoryInterface
   */
  protected $blockRepository;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Constructs a new BetterSubthemesBlockRepository.
   *
   * @param BetterSubthemesManager $better_subthemes_manager
   *   The Better sub-themes manager.
   * @param \Drupal\block\BlockRepositoryInterface $block_repository
   *   The decorated block repository.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   */
  public function __construct(BetterSubthemesManager $better_subthemes_manager, BlockRepositoryInterface $block_repository, ThemeManagerInterface $theme_manager) {
    $this->betterSubthemesManager = $better_subthemes_manager;
    $this->blockRepository = $block_repository;
    $this->themeManager = $theme_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibleBlocksPerRegion(array &$cacheable_metadata = []) {
    // Get the currently active theme.
    /** @var \Drupal\Core\Theme\ActiveTheme $original_theme */
    $original_theme = $this->themeManager->getActiveTheme();

    // Get the source theme if we are inheriting the block layout and
    // temporarily set it as the active theme.
    /** @var \Drupal\Core\Theme\ActiveTheme $source_theme */
    $source_theme = $this->betterSubthemesManager->getSourceTheme('block layout');
    if ($source_theme !== $original_theme) {
      $this->themeManager->setActiveTheme($source_theme);
    }

    // Get an array of regions and their block entities.
    $assignments = $this->blockRepository->getVisibleBlocksPerRegion($cacheable_metadata);

    // Re-map block layout assignments.
    $assignments = $this->betterSubthemesManager->remapBlockLayout($assignments);

    // Restore original theme.
    if ($source_theme !== $original_theme) {
      $this->themeManager->setActiveTheme($original_theme);
    }

    return $assignments;
  }

}
