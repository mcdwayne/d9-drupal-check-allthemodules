<?php

namespace Drupal\theme_permission\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\system\Controller\SystemController;
use Drupal\Core\Url;

/**
 * Access Controller.
 *
 * @package Drupal\theme_permission\Controller.
 */
class AccessController extends SystemController {

  /**
   * Check permission.
   *
   * @param Drupal\Core\Session\AccountInterface $account
   *   Get Account.
   * @param string $theme
   *   Theme Name.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, $theme = NULL) {
    if (empty($theme)) {
      $theme = \Drupal::request()->query->get('theme');
    }

    $auth = $account->hasPermission("administer themes $theme");
    if ($auth) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function themesPage() {

    $config = $this->config('system.theme');
    // Get all available themes.
    $themes = $this->themeHandler->rebuildThemeData();
    uasort($themes, 'system_sort_modules_by_info_name');

    $theme_default = $config->get('default');
    $theme_groups = ['installed' => [], 'uninstalled' => []];
    $admin_theme = $config->get('admin');
    $admin_theme_options = [];


    foreach ($themes as &$theme) {
      $theme_name = $theme->getName();
      $auth = \Drupal::currentUser()
        ->hasPermission("administer themes $theme_name");
      $uninstall_theme = \Drupal::currentUser()
        ->hasPermission("uninstall themes $theme_name");
      if ($auth) {
        if (!empty($theme->info['hidden'])) {
          continue;
        }
        $theme->is_default = ($theme->getName() == $theme_default);
        $theme->is_admin = ($theme->getName() == $admin_theme || ($theme->is_default && $admin_theme == '0'));

        // Identify theme screenshot.
        $theme->screenshot = NULL;
        // Create a list which includes the current theme and all its base themes.
        if (isset($themes[$theme->getName()]->base_themes)) {
          $theme_keys = array_keys($themes[$theme->getName()]->base_themes);
          $theme_keys[] = $theme->getName();
        }
        else {
          $theme_keys = [$theme->getName()];
        }


        // Look for a screenshot in the current theme or in its closest ancestor.
        foreach (array_reverse($theme_keys) as $theme_key) {
          if (isset($themes[$theme_key]) && file_exists($themes[$theme_key]->info['screenshot'])) {
            $theme->screenshot = [
              'uri' => $themes[$theme_key]->info['screenshot'],
              'alt' => $this->t('Screenshot  for @theme theme', ['@theme' => $theme->info['name']]),
              'title' => $this->t('Screenshot  for @theme theme', ['@theme' => $theme->info['name']]),
              'attributes' => ['class' => ['screenshot']],
            ];
            break;
          }
        }

        if (empty($theme->status)) {
          // Ensure this theme is compatible with this version of core.
          $theme->incompatible_core = !isset($theme->info['core']) || ($theme->info['core'] != \DRUPAL::CORE_COMPATIBILITY);
          // Require the 'content' region to make sure the main page
          // content has a common place in all themes.
          $theme->incompatible_region = !isset($theme->info['regions']['content']);
          $theme->incompatible_php = version_compare(phpversion(), $theme->info['php']) < 0;
          // Confirm that all base themes are available.
          $theme->incompatible_base = (isset($theme->info['base theme']) && !($theme->base_themes === array_filter($theme->base_themes)));
          // Confirm that the theme engine is available.
          $theme->incompatible_engine = isset($theme->info['engine']) && !isset($theme->owner);


        }
        $theme->operations = [];
        if (!empty($theme->status) || !$theme->incompatible_core && !$theme->incompatible_php && !$theme->incompatible_base && !$theme->incompatible_engine) {
          // Create the operations links.
          $query['theme'] = $theme->getName();
          if ($this->themeAccess->checkAccess($theme->getName()) && $auth) {
            $theme->operations[] = [
              'title' => $this->t('Settings'),
              'url' => Url::fromRoute('system.theme_settings_theme', ['theme' => $theme->getName()]),
              'attributes' => ['title' => $this->t('Settings for @theme theme', ['@theme' => $theme->info['name']])],
            ];
          }
          if (!empty($theme->status)) {
            if (!$theme->is_default) {
              $theme_uninstallable = TRUE;
              if ($theme->getName() == $admin_theme) {
                $theme_uninstallable = FALSE;
              }
              // Check it isn't the base of theme of an installed theme.
              foreach ($theme->required_by as $themename => $dependency) {
                if (!empty($themes[$themename]->status)) {
                  $theme_uninstallable = FALSE;
                }
              }
              if ($theme_uninstallable && $uninstall_theme) {
                $theme->operations[] = [
                  'title' => $this->t('Uninstall'),
                  'url' => Url::fromRoute('system.theme_uninstall'),
                  'query' => $query,
                  'attributes' => ['title' => $this->t('Uninstall @theme theme', ['@theme' => $theme->info['name']])],
                ];
              }
              $theme->operations[] = [
                'title' => $this->t('Set as default'),
                'url' => Url::fromRoute('system.theme_set_default'),
                'query' => $query,
                'attributes' => ['title' => $this->t('Set @theme as default theme', ['@theme' => $theme->info['name']])],
              ];
            }
            $admin_theme_options[$theme->getName()] = $theme->info['name'];
          }
          else {
            $theme->operations[] = [
              'title' => $this->t('Install'),
              'url' => Url::fromRoute('system.theme_install'),
              'query' => $query,
              'attributes' => ['title' => $this->t('Install @theme theme', ['@theme' => $theme->info['name']])],
            ];
            $theme->operations[] = [
              'title' => $this->t('Install and set as default'),
              'url' => Url::fromRoute('system.theme_set_default'),
              'query' => $query,
              'attributes' => ['title' => $this->t('Install @theme as default theme', ['@theme' => $theme->info['name']])],
            ];
          }
        }

        // Add notes to default and administration theme.
        $theme->notes = [];
        if ($theme->is_default) {
          $theme->notes[] = $this->t('default theme');
        }
        if ($theme->is_admin) {
          $theme->notes[] = $this->t('administration theme');
        }

        // Sort installed and uninstalled themes into their own groups.
        $theme_groups[$theme->status ? 'installed' : 'uninstalled'][] = $theme;
      }
    }

    // There are two possible theme groups.
    $theme_group_titles = [
      'installed' => $this->formatPlural(count($theme_groups['installed']), 'Installed theme', 'Installed themes'),
    ];
    if (!empty($theme_groups['uninstalled'])) {
      $theme_group_titles['uninstalled'] = $this->formatPlural(count($theme_groups['uninstalled']), 'Uninstalled theme', 'Uninstalled themes');
    }

    uasort($theme_groups['installed'], 'system_sort_themes');
    $this->moduleHandler()->alter('system_themes_page', $theme_groups);


    $build = [];
    $build[] = [
      '#theme' => 'system_themes_page',
      '#theme_groups' => $theme_groups,
      '#theme_group_titles' => $theme_group_titles,
    ];
    $admin_theme_auth = \Drupal::currentUser()
      ->hasPermission("Edit Administration theme");
    if ($admin_theme_auth) {
      $build[] = $this->formBuilder->getForm('Drupal\system\Form\ThemeAdminForm', $admin_theme_options);
    }
    return $build;

  }

}
