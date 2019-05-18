<?php

namespace Drupal\seo_manager\Commands;

use Drush\Commands\DrushCommands;

/**
 * SeoManagerCommands class.
 */
class SeoManagerCommands extends DrushCommands {

  /**
   * Uninstall module dependencies.
   *
   * @command seo_manager:uninstall
   * @aliases smun
   */
  public function uninstall() {
    // Uninstall modules.
    exec('drush pmu seo_manager');
    exec('drush pmu yoast_seo');
    exec('drush pmu simple_sitemap');
    exec('drush pmu google_analytics');

    // Uninstall redirect modules.
    exec('drush pmu redirect_404');
    exec('drush pmu redirect');

    // Uninstall schema_metatag modules.
    exec('drush pmu schema_web_site');
    exec('drush pmu schema_web_page');
    exec('drush pmu schema_item_list');
    exec('drush pmu schema_article');
    exec('drush pmu schema_metatag');

    // Uninstall metatag modules.
    exec('drush pmu metatag_verification');
    exec('drush pmu metatag_twitter_cards');
    exec('drush pmu metatag_open_graph');
    exec('drush pmu metatag_mobile');
    exec('drush pmu metatag_hreflang');
    exec('drush pmu metatag_google_plus');
    exec('drush pmu metatag_facebook');
    exec('drush pmu metatag_views');
    exec('drush pmu metatag');

    $this->logger()->success(dt('Successfully uninstalled: seo_manager with all depenendcies'));
  }

}
