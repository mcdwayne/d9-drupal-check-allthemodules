<?php

namespace Drupal\simple_analytics;

/**
 * Simple Analytics Helper functions.
 */
class SimpleAnalyticsHelper {

  /**
   * Get Configuration Name.
   */
  public static function getConfigName() {
    return 'simple_analytics.settings';
  }

  /**
   * Get Configuration Object.
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   *   The configuration object.
   */
  public static function getConfig($editable = FALSE) {
    if ($editable) {
      $config = \Drupal::configFactory()->getEditable(static::getConfigName());
    }
    else {
      $config = \Drupal::config(static::getConfigName());
    }
    return $config;
  }

  /**
   * Check the tracking conditions.
   *
   * @return bool
   *   TRUE = Excluded.
   */
  public static function checkNotrackConditions($config = NULL) {

    if (!$config) {
      $config = SimpleAnalyticsHelper::getConfig();
    }

    $track_admin = $config->get('track_admin');
    $track_auth = $config->get('track_auth');
    $track_exclude_url = $config->get('track_exclude_url');
    $current_url = $_SERVER['REQUEST_URI'];

    // No tracking for admin pages.
    if (!$track_admin) {
      $route = \Drupal::routeMatch()->getRouteObject();
      if ($route) {
        if (\Drupal::service('router.admin_context')->isAdminRoute($route)) {
          return TRUE;
        }
      }
    }

    // No tracking for Authenticated Users.
    $current_user = \Drupal::currentUser();
    if (!$track_auth && $current_user->isAuthenticated()) {
      return TRUE;
    }

    // No tracking for excluded pages.
    if (!empty($track_exclude_url) && is_array($track_exclude_url)) {
      foreach ($track_exclude_url as $url) {
        if ($url && strpos($current_url, $url) !== FALSE) {
          return TRUE;
        }
      }
    }

    // No tracking for S.A system / API pages.
    $url = 'simple_analytics/api';
    if (strpos($current_url, $url) !== FALSE) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get Browser data (Altarnative to get_browser()).
   *
   * @param string $user_agent
   *   The user agent string.
   *
   * @return array
   *   The user agent data.
   */
  public static function getBrowser($user_agent = NULL) {

    $u_agent = empty($user_agent) ? $_SERVER['HTTP_USER_AGENT'] : $user_agent;
    $bname = 'Unknown';
    $platform = 'Unknown';
    $ub = "Unknow";
    $version = "";

    // Get the platform.
    if (preg_match('/linux/i', $u_agent)) {
      $platform = 'Linux';
    }
    elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
      $platform = 'Mac';
    }
    elseif (preg_match('/windows|win32/i', $u_agent)) {
      $platform = 'Windows';
    }

    // Get the name of the useragent.
    if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
      $bname = 'Internet Explorer';
      $ub = "MSIE";
    }
    elseif (preg_match('/Firefox/i', $u_agent)) {
      $bname = 'Mozilla Firefox';
      $ub = "Firefox";
    }
    elseif (preg_match('/Chrome/i', $u_agent)) {
      $bname = 'Google Chrome';
      $ub = "Chrome";
    }
    elseif (preg_match('/Safari/i', $u_agent)) {
      $bname = 'Apple Safari';
      $ub = "Safari";
    }
    elseif (preg_match('/Opera/i', $u_agent)) {
      $bname = 'Opera';
      $ub = "Opera";
    }
    elseif (preg_match('/Netscape/i', $u_agent)) {
      $bname = 'Netscape';
      $ub = "Netscape";
    }

    // Get the version number.
    $known = ['Version', $ub, 'other'];
    $pattern = '#(?<browser>' . implode('|', $known) .
      ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $u_agent, $matches)) {
      // No matching number, just continue.
    }
    // Count.
    $i = count($matches['browser']);
    if ($i != 1) {
      // See if version is before or after the name.
      if (strripos($u_agent, "Version") < strripos($u_agent, $ub)) {
        $version = isset($matches['version'][0]) ? $matches['version'][0] : NULL;
      }
      else {
        $version = isset($matches['version'][1]) ? $matches['version'][1] : NULL;
      }
    }
    else {
      $version = $matches['version'][0];
    }
    // Check if we have a number.
    if ($version == NULL || $version == "") {
      $version = "?";
    }
    $device_type = "Desktop";
    $ismobiledevice = 0;
    if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $u_agent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($u_agent, 0, 4))) {
      $device_type = "Mobile";
      $ismobiledevice = 1;
    }

    // Build result.
    $result = [];
    $result['browser_name_regex'] = $pattern;
    $result['parent'] = "{$bname} {$version}";
    $result['platform'] = $platform;
    $result['browser'] = $bname;
    $result['version'] = $version;
    $result['majorver'] = $version !== "?" ? substr($version, 0, strpos($version, ".")) : '';
    $result['minorver'] = '';

    // Extra data.
    $result['user_agent'] = $u_agent;
    $result['device_type'] = $device_type;
    $result['ismobiledevice'] = $ismobiledevice;
    // TODO.
    $result['cookies'] = 0;
    $result['javascript'] = 0;

    return $result;
  }

  /**
   * Check chartist library.
   */
  public static function checkLibraries($update = FALSE) {

    // Loockup directories.
    $library_dirs = [
      // Historical location.
      "libraries/chartist-js" => "simple_analytics/simple_analytics_chart.sa",
      // If using git as an drupal-library, via composer.
      "libraries/chartist-js/dist" => "simple_analytics/simple_analytics_chart.dist",
      // If using drupal chartist module.
      "sites/all/libraries/chartist" => "simple_analytics/simple_analytics_chart.module",
    ];
    // Files to check.
    $lib_files = ['chartist.min.js', 'chartist.min.css'];

    // Looking for each chartist lib locations.
    $lib_ok = FALSE;
    foreach ($library_dirs as $lib_dir => $library) {
      foreach ($lib_files as $lib_file) {
        $lib_ok = FALSE;
        if (file_exists($lib_dir . "/" . $lib_file)) {
          $lib_ok = $library;
        }
      }
      if ($lib_ok) {
        break;
      }
    }

    // Update lib info.
    if ($update) {
      $config = SimpleAnalyticsHelper::getConfig(TRUE);
      // Set configuration.
      if (!($config->get('lib_chartist_mode') && !$config->get('lib_chartist'))) {
        $config->set('lib_chartist', $lib_ok ? TRUE : FALSE);
      }
      $config->set('lib_chartist_mode', $lib_ok);
      $config->save();
    }

    return $lib_ok;
  }

}
