<?php

namespace Drupal\tb_megamenu\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tb_megamenu\TBMegaMenuBuilder;

/**
 * Provides blocks which belong to TB Mega Menu.
 *
 * @Block(
 *   id = "tb_megamenu_menu_block",
 *   admin_label = @Translation("TB Mega Menu"),
 *   category = @Translation("TB Mega Menu"),
 *   deriver = "Drupal\tb_megamenu\Plugin\Derivative\TBMegaMenuBlock",
 * )
 *
 * TODO: Add injection
 */
class TBMegaMenuBlock extends BlockBase {

  /**
   * Current theme name;
   *
   * @var string
   */
  private $themeName;

  /**
   * {@inheritdoc}
   */
  public function build() {
    $menu_name = $this->getDerivativeId();
    $theme_name = $this->getThemeName();
    $menu = TBMegaMenuBuilder::getMenus($menu_name, $theme_name);
    if ($menu === NULL) {
      return [];
    }
    return [
      '#theme' => 'tb_megamenu',
      '#menu_name' => $menu_name,
      '#block_theme' => $theme_name,
      '#attached' => ['library' => ['tb_megamenu/theme.tb_megamenu']],
      '#post_render' => ['tb_megamenu_attach_number_columns'],
    ];
  }

  /**
   * Default cache is disabled.
   *
   * @param array $form
   *   The form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state information.
   *
   * @return array
   *   The configuration render array
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $rebuild_form = parent::buildConfigurationForm($form, $form_state);
    $rebuild_form['cache']['max_age']['#default_value'] = 0;
    return $rebuild_form;
  }
  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Rebuild block when menu or config changes
    $configName = "{$this->getDerivativeId()}__{$this->getThemeName()}";
    $cacheTags = parent::getCacheTags();
    $cacheTags[] = 'config:system.menu.' . $this->getDerivativeId();
    $cacheTags[] = 'config:tb_megamenu.menu_config.' . $configName;
    return $cacheTags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // ::build() uses MenuLinkTreeInterface::getCurrentRouteMenuTreeParameters()
    // to generate menu tree parameters, and those take the active menu trail
    // into account. Therefore, we must vary the rendered menu by the active
    // trail of the rendered menu.
    // Additional cache contexts, e.g. those that determine link text or
    // accessibility of a menu, will be bubbled automatically.
    $menu_name = $this->getDerivativeId();
    return Cache::mergeContexts(parent::getCacheContexts(), ['route.menu_active_trails:' . $menu_name]);
  }

  /**
   * Get the current Theme Name
   *
   * @return string
   * The current theme name.
   */
  public function getThemeName() {
    if (!isset($this->themeName)) {
      $this->themeName = \Drupal::service('theme.manager')->getActiveTheme()->getName();
    }
    return $this->themeName;
  }
}

