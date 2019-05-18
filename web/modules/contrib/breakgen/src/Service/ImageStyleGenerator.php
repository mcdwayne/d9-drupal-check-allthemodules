<?php

namespace Drupal\breakgen\Service;

use Drupal\breakpoint\BreakpointInterface;
use Drupal\breakpoint\BreakpointManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Theme\ThemeManager;
use Drupal\image\Entity\ImageStyle;

/**
 * Class ImageStyleGenerator.
 *
 * Breakgen service in charge of generating Image Styles. And firing hooks
 * after certain events.
 *
 * @package Drupal\breakgen\Service
 */
class ImageStyleGenerator {

  const EFFECT_ALTER_HOOK = 'breakgen_image_style_effect_alter';

  const IMAGE_STYLE_ALTER_HOOK = 'breakgen_image_style_alter';

  const PRE_CLEAR_HOOK = 'breakgen_pre_clear_image_styles';

  const POST_SAVE_HOOK = 'breakgen_post_save_image_styles';

  private $breakpointManager;

  private $entityTypeManager;

  private $themeManager;

  private $moduleHandler;

  /**
   * ImageStyleGenerator constructor.
   *
   * @param \Drupal\breakpoint\BreakpointManager $breakpointManager
   *   Breakpoint manager for retrieving breakpoint plugin groups.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   EntityTypeManager to save entities to storage.
   * @param \Drupal\Core\Theme\ThemeManager $themeManager
   *   Theme manager for retrieving the current theme.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler interface for firing hooks.
   */
  public function __construct(
    BreakpointManager $breakpointManager,
    EntityTypeManager $entityTypeManager,
    ThemeManager $themeManager,
    ModuleHandlerInterface $moduleHandler
  ) {
    $this->breakpointManager = $breakpointManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->themeManager = $themeManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Generate image styles and effects from the breakpoint file.
   *
   * @param string|null $theme
   *   Theme to get breakgen configuration from.
   */
  public function generate($theme = NULL) {
    if ($theme === NULL) {
      $theme = $this->themeManager->getActiveTheme()->getName();
    }

    $this->clear();
    $breakpoints = $this->breakpointManager->getBreakpointsByGroup($theme);
    foreach ($breakpoints as $key => $breakpoint) {
      $this->generateImagesStyles($key, $breakpoint);
    }
  }

  /**
   * Clear all image styles related to breakgen.
   */
  public function clear() {
    $this->moduleHandler->invokeAll(self::PRE_CLEAR_HOOK);

    $imageStyles = $this->entityTypeManager->getStorage('image_style')
      ->getQuery()
      ->condition('name', "breakgen", 'CONTAINS')
      ->execute();

    $imageStyles = $this->entityTypeManager->getStorage('image_style')
      ->loadMultiple($imageStyles);

    $this->entityTypeManager->getStorage('image_style')->delete($imageStyles);
  }

  /**
   * Generate image styles for breakgen.
   *
   * @param string $key
   *   Breakgen configuration key.
   * @param \Drupal\breakpoint\BreakpointInterface $breakpoint
   *   Breakpoint Interface of plugin.
   */
  private function generateImagesStyles($key, BreakpointInterface $breakpoint) {
    // If this breakpoint has breakgen mapping.
    if (isset($breakpoint->getPluginDefinition()['breakgen'])) {
      $breakgen = $breakpoint->getPluginDefinition()['breakgen'];
      foreach ($breakgen as $groupName => $data) {
        $this->generateImageStyle(
          $key,
          $breakpoint->getLabel()->__toString(),
          $groupName,
          $data['style_effects']
        );

        if (isset($data['percentages'])) {
          $this->generatePercentageDeviation(
            $data['percentages'],
            $key,
            $groupName,
            $breakpoint,
            $data['style_effects']
          );
        }

        $this->moduleHandler->invokeAll(self::POST_SAVE_HOOK, [
          $key,
          &$breakpoint,
          &$breakgen,
        ]);
      }
    }
  }

  /**
   * Generates a percentage deviation of the original image style.
   *
   * @param array $percentages
   *   Array of percentages to generate.
   * @param string $key
   *   Breakgen configuration key.
   * @param string $groupName
   *   Breakgen group name.
   * @param \Drupal\breakpoint\BreakpointInterface $breakpoint
   *   Breakpoint Interface of plugin.
   * @param array $styleEffects
   *   Array of style effects.
   */
  private function generatePercentageDeviation(
    array $percentages,
    $key,
    $groupName,
    BreakpointInterface $breakpoint,
    array $styleEffects
  ) {
    foreach ($percentages as $percentage) {
      $modifier = str_replace('%', '', $percentage) / 100;
      $percentage = str_replace('%', '', $percentage);

      $this->generateImageStyle(
        $key . ".$percentage",
        $breakpoint->getLabel()->__toString() . " ($percentage%)",
        $groupName,
        $styleEffects,
        $modifier
      );
    }
  }

  /**
   * Generate image style for breakgen.
   *
   * @param string $breakpointKey
   *   Breakgen configuration key.
   * @param string $breakpointLabel
   *   Breakgen label suffix for image style.
   * @param string $groupName
   *   Breakgen group name.
   * @param array $styleEffects
   *   Array of style effects.
   * @param string|null $modifier
   *   Current modifier in percentages.
   */
  private function generateImageStyle(
    $breakpointKey,
    $breakpointLabel,
    $groupName,
    array $styleEffects,
    $modifier = NULL
  ) {
    // Generate machine name.
    $machineName = str_replace('.', '_',$breakpointKey) . '_breakgen_' . $groupName;

    // Generate label.
    $label = "$breakpointLabel $groupName";

    // Create a image style entity.
    $imageStyle = ImageStyle::create([
      'name'  => $machineName,
      'label' => $label,
    ]);

    foreach ($styleEffects as $effectConfiguration) {
      // Invoke effect alter hook for altering the effect configuration
      // by 3rd party applications.
      $this->moduleHandler->invokeAll(self::EFFECT_ALTER_HOOK, [&$effectConfiguration]);

      // If there is a modifier modify the width and height.
      if ($modifier !== NULL && isset($effectConfiguration['data']['width'])) {
        $effectConfiguration['data']['width'] = $effectConfiguration['data']['width'] * $modifier;
      }
      if ($modifier !== NULL && isset($effectConfiguration['data']['height'])) {
        $effectConfiguration['data']['height'] = $effectConfiguration['data']['height'] * $modifier;
      }

      // Add image effect to style.
      $imageStyle->addImageEffect($effectConfiguration);
    }

    // Invoke pre save hook for module to interact with breakgen.
    $this->moduleHandler->invokeAll(self::IMAGE_STYLE_ALTER_HOOK, [&$imageStyle]);

    // Save the image style.
    $imageStyle->save();
  }
}