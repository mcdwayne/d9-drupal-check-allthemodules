<?php

namespace Drupal\drd\Agent\Remote\V7;

class Requirements {

  /**
   * @param string $phase
   * @return array
   */
  static public function collect($phase) {
    $requirements = array();
    $t = get_t();

    if ($phase == 'runtime') {
      $info = \Database::getConnectionInfo();
      $info['default'] += array(
        'driver' => '',
        'host' => '',
        'port' => '',
        'database' => '',
        'username' => '',
        'password' => '',
        'prefix' => '',
      );
      $requirements['drd_agent.database'] = array(
        'title' => $t('Database setup'),
        'value' => '<table>' .
          '<tr><td>' . $t('Driver') . '</td><td>' . $info['default']['driver'] . '</td></tr>' .
          '<tr><td>' . $t('Host') . '</td><td>' . $info['default']['host'] . '</td></tr>' .
          '<tr><td>' . $t('Port') . '</td><td>' . $info['default']['port'] . '</td></tr>' .
          '<tr><td>' . $t('Database') . '</td><td>' . $info['default']['database'] . '</td></tr>' .
          '<tr><td>' . $t('Username') . '</td><td>' . $info['default']['username'] . '</td></tr>' .
          '<tr><td>' . $t('Password') . '</td><td>' . $info['default']['password'] . '</td></tr>' .
          '<tr><td>' . $t('Prefix') . '</td><td>' . $info['default']['prefix']['default'] . '</td></tr>' .
          '</table>',
        'severity' => REQUIREMENT_INFO,
        'description' => $t('These are the database settings that have been configured for this site in settings.php.'),
      );

      $analytics = (
        module_exists('googleanalytics') ||
        module_exists('google_tag') ||
        module_exists('piwik')
      );
      $requirements['drd_agent.module.analytics'] = array(
        'title' => $t('Analytics module installed'),
        'value' => $analytics ? $t('Yes') : $t('No'),
        'severity' => $analytics ? REQUIREMENT_OK: REQUIREMENT_WARNING,
        'description' => $analytics ?
          $t('This site uses an analytics tool, go here to configure <a href="@url1">Google Analytics</a>, <a href="@url2">Google Tag Manager</a> or <a href="@url3">Piwik</a>.', array('@url1' => url('/admin/config/system/googleanalytics'), '@url2' => url('/admin/config/system/google_tag'), '@url3' => url('/admin/config/system/piwik')) ) :
          $t('For SEO improvements you should use an analytics tool like <a href="@url1">Google Analytics</a>, <a href="@url2">Google Tag Manager</a> or <a href="@url3">Piwik</a>.', array('@url1' => url('https://www.drupal.org/project/google_analytics', array('external' => TRUE,)), '@url2' => url('https://www.drupal.org/project/google_tag', array('external' => TRUE,)), '@url3' => url('https://www.drupal.org/project/piwik', array('external' => TRUE,))) ),
      );

      $devel = !module_exists('devel');
      $requirements['drd_agent.module.devel'] = array(
        'title' => $t('Devel module disabled'),
        'value' => $devel ? $t('Yes') : $t('No'),
        'severity' => $devel ? REQUIREMENT_OK: REQUIREMENT_WARNING,
        'description' => $t('On production sites the <a href="@url">Devel module</a> should be disabled for security and performance reasons.', array('@url' => url('admin/modules')) ),
      );

      $metatag = module_exists('metatag');
      $requirements['drd_agent.module.metatag'] = array(
        'title' => $t('Module MetaTag installed'),
        'value' => $metatag ? $t('Yes') : $t('No'),
        'severity' => $metatag ? REQUIREMENT_OK: REQUIREMENT_WARNING,
        'description' => $metatag ?
          $t('The MetaTag module is enabled, its <a href="@url">settings can be managed here</a>.', array('@url' => url('admin/config/search/metatags')) ) :
          $t('For SEO improvements you should use the <a href="@url">MetaTag</a> module.', array('@url' => url('https://www.drupal.org/project/metatag')) ),
      );

      $pathauto = module_exists('pathauto');
      $requirements['drd_agent.module.pathauto'] = array(
        'title' => $t('Module PathAuto installed'),
        'value' => $pathauto ? $t('Yes') : $t('No'),
        'severity' => $pathauto ? REQUIREMENT_OK: REQUIREMENT_WARNING,
        'description' => $pathauto ?
          $t('The PathAuto module is enabled, it <a href="@url">can be managed here</a>.', array('@url' => url('admin/config/search/path/patterns')) ) :
          $t('For SEO improvements you should use the <a href="@url">PathAuto</a> module.', array('@url' => url('https://www.drupal.org/project/pathauto', array('external' => TRUE,))) ),
      );

      $php = !module_exists('php');
      $requirements['drd_agent.module.php'] = array(
        'title' => $t('Module PHP Filter disabled'),
        'value' => $php ? $t('Yes') : $t('No'),
        'severity' => $php ? REQUIREMENT_OK: REQUIREMENT_ERROR,
        'description' => $php ?
          $t('For security reasons you should try to avoid using the <a href="@url">PHP Filter</a> module.', array('@url' => url('admin/modules')) ) :
          $t('For security reasons you should keep the <a href="@url">PHP Filter</a> module disabled.', array('@url' => url('admin/modules')) ),
      );

      $xmlsitemap = module_exists('xmlsitemap');
      $requirements['drd_agent.module.xmlsitemap'] = array(
        'title' => $t('Module XML-Sitemap installed'),
        'value' => $xmlsitemap ? $t('Yes') : $t('No'),
        'severity' => $xmlsitemap ? REQUIREMENT_OK: REQUIREMENT_WARNING,
        'description' => $xmlsitemap ?
          $t('The XML Sitemap module is enabled, it <a href="@url">can be managed here</a>.', array('@url' => url('admin/config/search/xmlsitemap')) ) :
          $t('For SEO improvements you should use the <a href="@url">XML Sitemap</a> module.', array('@url' => url('https://www.drupal.org/project/xmlsitemap', array('external' => TRUE,))) ),
      );

      $user1 = user_load(1);
      $user1_ok = !in_array(strtolower($user1->name), array('admin', 'root', 'superadmin', 'manager', 'administrator', 'adm'));
      $requirements['drd_agent.user1'] = array(
        'title' => $t('Name of user 1'),
        'value' => $user1_ok ? $t('Good') : $t('Too obvious'),
        'severity' => $user1_ok ? REQUIREMENT_OK: REQUIREMENT_WARNING,
        'description' => $user1_ok ?
          $t('The name of user 1 is uncommon enough to not be a very obvious security risk') :
          $t('For security reasons the name of user 1 should not be so obvious as it is now.'),
      );

      $admin_role = variable_get('user_admin_role', 0);
      $count_admin = db_select('users_roles', 'ur')
        ->condition('ur.rid', $admin_role)
        ->countQuery()
        ->execute()
        ->fetchField();
      $requirements['drd_agent.admincount'] = array(
        'title' => $t('Number of admins'),
        'value' => ($count_admin <= 3) ? $t('Good (!count)', array('!count' => $count_admin)) : $t('Too many (!count)', array('!count' => $count_admin)),
        'severity' => ($count_admin <= 3) ? REQUIREMENT_OK: REQUIREMENT_WARNING,
        'description' => $t('For security reasons you should only have a small amount of users with an administer role.'),
      );

      $css = variable_get('preprocess_css', 0);
      $requirements['drd_agent.compress.css'] = array(
        'title' => $t('Aggregate and compress CSS files'),
        'value' => $css ? $t('Yes') : $t('No'),
        'severity' => $css ? REQUIREMENT_OK: REQUIREMENT_WARNING,
        'description' => $css ?
          $t('The CSS is aggregated on this site. <a href="@url">Performance settings can be managed here</a>.', array('@url' => url('admin/config/development/performance')) ) :
          $t('For performance reasons you should allow your <a href="@url">CSS to be aggregated</a> on production sites.', array('@url' => url('admin/config/development/performance')) ),
      );

      $js = variable_get('preprocess_js', 0);
      $requirements['drd_agent.compress.js'] = array(
        'title' => $t('Aggregate JavaScript files'),
        'value' => $js ? $t('Yes') : $t('No'),
        'severity' => $js ? REQUIREMENT_OK: REQUIREMENT_WARNING,
        'description' => $js ?
          $t('The JS is aggregated on this site. <a href="@url">Performance settings can be managed here</a>.', array('@url' => url('admin/config/development/performance')) ) :
          $t('For performance reasons you should allow your <a href="@url">JS to be aggregated</a> on production sites.', array('@url' => url('admin/config/development/performance')) ),
      );

      $page = variable_get('page_compression', 0);
      $requirements['drd_agent.compress.page'] = array(
        'title' => $t('Compress cached pages'),
        'value' => $page ? $t('Yes') : $t('No'),
        'severity' => $page ? REQUIREMENT_OK: REQUIREMENT_WARNING,
        'description' => $page ?
          $t('The pages are being compressed on this site. <a href="@url">Performance settings can be managed here</a>.', array('@url' => url('admin/config/development/performance')) ) :
          $t('For performance reasons you should allow <a href="@url">cached pages to be compressed</a> on production sites.', array('@url' => url('admin/config/development/performance')) ),
      );

      $page403 = variable_get('site_403', '');
      $requirements['drd_agent.defined.403'] = array(
        'title' => $t('Default 403 (access denied) page'),
        'value' => empty($page403) ? $t('Undefined') : $page403,
        'severity' => empty($page403) ? REQUIREMENT_WARNING: REQUIREMENT_OK,
        'description' => $page403 ?
          $t('There is a 403 page defined. <a href="@url">The 403 page can be managed here</a>.', array('@url' => url('admin/config/system/site-information')) ) :
          $t('For improved user experience you should define a <a href="@url">default 403 (Access denied)</a> page.', array('@url' => url('admin/config/system/site-information')) ),
      );

      $page404 = variable_get('site_404', '');
      $requirements['drd_agent.defined.404'] = array(
        'title' => $t('Default 404 (not found) page'),
        'value' => empty($page404) ? $t('Undefined') : $page404,
        'severity' => empty($page404) ? REQUIREMENT_WARNING: REQUIREMENT_OK,
        'description' => $page404 ?
          $t('There is a 404 page defined. <a href="@url">The 404 page can be managed here</a>.', array('@url' => url('admin/config/system/site-information')) ) :
          $t('For improved user experience you could define a <a href="@url">default 404 (Not found)</a> page.', array('@url' => url('admin/config/system/site-information')) ),
      );

      $cache = variable_get('cache', 0);
      $requirements['drd_agent.enable.cache'] = array(
        'title' => $t('Cache pages for anonymous users'),
        'value' => $cache ? $t('Yes') : t('No'),
        'severity' => $cache ? REQUIREMENT_OK: REQUIREMENT_WARNING,
        'description' => $cache ?
          $t('The pages are being cached. <a href="@url">Performance settings can be managed here</a>.', array('@url' => url('admin/config/development/performance')) ) :
          $t('For performance reasons you should <a href="@url">cache pages for anonymous users</a> on production sites.', array('@url' => url('admin/config/development/performance')) ),
      );

      $clean_url = variable_get('clean_url', 0);
      $requirements['drd_agent.enable.cleanurl'] = array(
        'title' => $t('Enable clean URLs'),
        'value' => $clean_url ? $t('Yes') : t('No'),
        'severity' => $clean_url ? REQUIREMENT_OK: REQUIREMENT_WARNING,
        'description' => $clean_url ?
          $t('Clean URLs are configured and its <a href="@url">settings can be managed here</a>.', array('@url' => url('admin/config/search/clean-urls')) ) :
          $t('For SEO improvements you should <a href="@url">enable clean URLs</a>.', array('@url' => url('admin/config/search/clean-urls')) ),
      );

      $favicon = theme_get_setting('default_favicon', variable_get('theme_default', 'bartik'));
      $requirements['drd_agent.favicon'] = array(
        'title' => $t('Default favicon used'),
        'value' => $favicon ? $t('Yes') : t('No'),
        'severity' => $favicon ? REQUIREMENT_WARNING: REQUIREMENT_OK,
        'description' => $t('For improved user experience you should set a <a href="@url">custom favicon</a>.', array('@url' => url('admin/appearance/settings')) ),
      );
      $warnings = variable_get('error_level', ERROR_REPORTING_DISPLAY_ALL);
      $requirements['drd_agent.hidden.warnings'] = array(
        'title' => $t('Error messages to display'),
        'description' => $t('For security reasons you should <a href="@url">write all errors and warnings</a> to the log.', array('@url' => url('admin/config/development/logging')) ),
      );

      switch ($warnings) {
        case ERROR_REPORTING_HIDE:
          $requirements['drd_agent.hidden.warnings']['value'] = t('None');
          $requirements['drd_agent.hidden.warnings']['severity'] = REQUIREMENT_OK;
          break;

        case ERROR_REPORTING_DISPLAY_SOME:
          $requirements['drd_agent.hidden.warnings']['value'] = t('Errors and warnings');
          $requirements['drd_agent.hidden.warnings']['severity'] = REQUIREMENT_WARNING;
          break;

        default:
          $requirements['drd_agent.hidden.warnings']['value'] = t('All messages');
          $requirements['drd_agent.hidden.warnings']['severity'] = REQUIREMENT_ERROR;
      }

      $txtfiles = array();
      $files_to_remove = array(
        'CHANGELOG.txt',
        'COPYRIGHT.txt',
        'INSTALL.mysql.txt',
        'INSTALL.pgsql.txt',
        'INSTALL.txt',
        'LICENSE.txt',
        'MAINTAINERS.txt',
        'README.txt',
        'UPGRADE.txt'
      );
      foreach ($files_to_remove as $file) {
        if (file_exists(DRUPAL_ROOT . '/' . $file))
          $txtfiles[] = $file;
      }
      $requirements['drd_agent.removed.txtfiles'] = array(
        'title' => $t('Info files to be removed'),
        'value' => empty($txtfiles) ? $t('All info files properly removed') : implode(', ', $txtfiles),
        'severity' => empty($txtfiles) ? REQUIREMENT_OK: REQUIREMENT_WARNING,
        'description' => $requirements ?
          $t('The info files of Drupal Core are removed.') :
          $t('The info files in the Drupal Core could be removed to expose less about which version Drupal is running.'),
      );

      $robotsurl = url('robots.txt', array('absolute' => TRUE, 'language' => (object) array('language' => LANGUAGE_NONE)));
      $request = drupal_http_request($robotsurl, array('max_redirects' => 0, 'timeout' => 2, ));
      $robots = isset($request->code) && ($request->code == 200);
      $requirements['drd_agent.robots.txt'] = array(
        'title' => $t('File robots.txt is available'),
        'value' => $robots ? $t('Yes') : $t('No'),
        'severity' => $robots ? REQUIREMENT_OK: REQUIREMENT_WARNING,
        'description' => $robots ?
          $t('This site contains a <a href="@url">robots.txt</a> file', array('@url' => url('robots.txt')) ) :
          $t('For SEO reasons this site should have a <a href="@url">robots.txt</a> file in the Drupal Core.', array('@url' => url('https://www.drupal.org/project/robotstxt', array('external' => TRUE,))) ),
      );

      $themeregistry = variable_get('devel_rebuild_theme_registry', FALSE);
      $requirements['drd_agent.theme.registry'] = array(
        'title' => $t('Rebuild theme registry on each page load'),
        'value' => $themeregistry ? $t('Yes') : $t('No'),
        'severity' => $themeregistry ? REQUIREMENT_WARNING: REQUIREMENT_OK,
        'description' => $themeregistry ?
          $t('Your site is not rebuilding the them registry on each page load. Thats good.') :
          $t('For performance reasons this site should not <a href="@url">rebuild the theme registry</a> on each page load.', array('@url' => url('admin/appearance/settings')) ),
      );

      $watchdog = variable_get('dblog_row_limit', 1000);
      $requirements['drd_agent.trim.watchdog'] = array(
        'title' => $t('Database log messages to keep'),
        'value' => empty($watchdog) ? t('All') : $watchdog,
        'severity' => ($watchdog <= 1000 && $watchdog > 0) ? REQUIREMENT_OK: REQUIREMENT_WARNING,
        'description' => $t('For performance reasons the <a href="@url">database log</a> should not be bigger then 1000 messages.', array('@url' => url('admin/config/development/logging')) ),
      );
    }

    return $requirements;
  }

}
