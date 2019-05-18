<?php

namespace Drupal\composer_forced;

use Drupal\Core\Url;

/**
 * Class ComposerForced.
 *
 * @package Drupal\composer_forced\ComposerForced
 */
class ComposerForced {

  /**
   * Disables the update form with a proper message.
   *
   * @param array $form
   *   The form to alter.
   */
  public static function replaceUpdateManagerUpdateForm(array &$form) {
    $form['composer_forced'] = [
      '#prefix' => '<div class="messages messages--warning">',
      '#markup' => t('Using the UI to update outdated modules is disabled by the <em>Composer Forced</em> module to force the developers of the website to use <a href="@url_using_composer">composer in order to manage site dependencies and upgrades</a>.', [
        '@url_using_composer' => Url::fromUri('https://www.drupal.org/docs/develop/using-composer/using-composer-to-install-drupal-and-manage-dependencies')
          ->toString(),
      ]) . ' ' . t("Please contact the website's developers to find out if composer should be executed from the same folder where Drupal's index.php resides in or from another folder like <a href='@url_drupal_composer' target='_blank'>drupal-composer/drupal-project</a> expects.<br>PS: You can still use the UI to enable or uninstall modules or the <a href='@url_update_manager_settings' target='_blank'>settings page</a> to configure notifications about new versions of modules and core.", [
        '@url_drupal_composer' => Url::fromUri('https://github.com/drupal-composer/drupal-project')
          ->toString(),
        '@url_update_manager_settings' => Url::fromRoute('update.settings')
          ->toString(),
      ]),
      '#suffix' => '</div>',
    ];
  }

  /**
   * Disables the install form with a proper message.
   *
   * @param array $form
   *   The form to alter.
   */
  public static function replaceUpdateManagerInstallForm(array &$form) {
    $form['composer_forced'] = [
      '#prefix' => '<div class="messages messages--warning">',
      '#markup' => t('Using the UI to install modules is disabled by the <em>Composer Forced</em> module to force the developers of the website to use <a href="@url_using_composer">composer in order to manage site dependencies and upgrades</a>.', [
        '@url_using_composer' => Url::fromUri('https://www.drupal.org/docs/develop/using-composer/using-composer-to-install-drupal-and-manage-dependencies')
          ->toString(),
      ]) . ' ' . t("Please contact the website's developers to find out if composer should be executed from the same folder where Drupal's index.php resides in or from another folder like <a href='@url_drupal_composer' target='_blank'>drupal-composer/drupal-project</a> expects.<br>PS: You can still use the UI to enable or uninstall modules or the <a href='@url_update_manager_settings' target='_blank'>settings page</a> to configure notifications about new versions of modules and core.", [
        '@url_drupal_composer' => Url::fromUri('https://github.com/drupal-composer/drupal-project')
          ->toString(),
        '@url_update_manager_settings' => Url::fromRoute('update.settings')
          ->toString(),
      ]),
      '#suffix' => '</div>',
    ];
  }

  /**
   * Cleans values from table's #rows which are value for tableselect #options.
   *
   * @param array $rows
   *   The table #rows to clean.
   */
  public static function cleanTableRows(array &$rows) {
    foreach ($rows as $module => $data) {
      if (isset($rows[$module]['#weight'])) {
        unset($rows[$module]['#weight']);
      }
      if (isset($rows[$module]['#attributes'])) {
        unset($rows[$module]['#attributes']);
      }
    }
  }

}
