<?php
/**
 * @file
 * Prerequisites: PHP4 (tested 4.4.1+), PHP5
 * Maintainers: Andreas Pachler, Brandon Elliott.
 * For the latest documentation and best practices: please visit:
 * https://shrinktheweb.com/content/shrinktheweb-pagepix-documentation.html.
 */

$config = \Drupal::config('shrinktheweb.settings');
define('ACCESS_KEY', $config->get('shrinktheweb_access_key'));
define('SECRET_KEY', $config->get('shrinktheweb_secret_key'));
define('THUMBNAIL_DIR', file_default_scheme() . '://' . $config->get('shrinktheweb_thumbs_folder') . '/');
define('THUMBNAIL_URI', file_create_url(THUMBNAIL_DIR) . '/');
// Set to true if inside capturing should be allowed.
define('INSIDE_PAGES', $config->get('shrinktheweb_inside_pages'));
// i.e. 'http://yourdomain.com/path/to/your/custom/msgs'
define('CUSTOM_MSG_URL', $config->get('shrinktheweb_custom_msg_url'));
// How many days should the local copy be valid?
// Enter 0 (zero) to never update screenshots once cached
// Enter -1 to disable caching and always use embedded method instead.
define('CACHE_DAYS', $config->get('shrinktheweb_cache_days'));
define('CALLBACKURL', \Drupal::request()->getHost() . '/shrinktheweb/callback');
define('NOTIFYNOPUSH', $config->get('shrinktheweb_notifynopush'));
define('TOKEN', $config->get('shrinktheweb_token'));
// Allows us to identify known bugs and version control; DON'T touch!
define('VER', '2.0.8_drupal8');
define('QUOTA_IMAGE', 'quota.jpg');
define('BANDWIDTH_IMAGE', 'bandwidth.jpg');
define('NO_RESPONSE_IMAGE', 'no_response.jpg');
define('MAINTENANCE', 'ShrinkTheWeb is temporarily offline for maintenance');
define('PROTOCOL', $config->get('shrinktheweb_enable_https') ? 'https://' : 'http://');
// MUST be "true" to log debug entries to database.
define('DEBUG', $config->get('shrinktheweb_debug'));
error_reporting(E_ALL ^ E_NOTICE);

/******************************************
 *  !! DO NOT CHANGE BELOW THIS LINE !!    *
 *  ...unless you know what you are doing  *
 *******************************************/

/**
 * Gets the thumbnail for the specified website, stores it in the cache,
 * and then returns the HTML for loading the image.
 * !!! SLIGHTLY CHANGED FOR DRUPAL !!!
 * @param $sUrl
 * @param array $aOptions
 * @return bool|string
 */
function shrinktheweb_getThumbnailURL($sUrl, $aOptions = array()) {
  $sImageURL = FALSE;
  $aOptions = shrinktheweb_generateOptions($aOptions);

  $sImageURL = shrinktheweb_getThumbnailAdvanced($sUrl, $aOptions);

  return $sImageURL;
}


/**
 * Delete thumbnail.
 * @param $sUrl
 * @param array $aOptions
 */
function shrinktheweb_deleteThumbnail($sUrl, $aOptions = array()) {
  $aOptions = shrinktheweb_generateOptions($aOptions);
  $aArgs = shrinktheweb_generateRequestArgs($aOptions);
  $aArgs['stwurl'] = $sUrl;

  $sFilename = shrinktheweb_generateHash($aArgs) . '.jpg';
  $sFile = THUMBNAIL_DIR . $sFilename;

  if (file_exists($sFile)) {
    @unlink($sFile);
  }
}

/**
 * Delete cached images.
 */
function shrinktheweb_deleteAllImages() {
  $rDir = opendir(THUMBNAIL_DIR);
  while (FALSE !== ($sFile = readdir($rDir))) {
    if ($sFile != "." && $sFile != "..") {
      @unlink(THUMBNAIL_DIR . $sFile);
    }
  }
  closedir($rDir);
}

/**
 * Delete error images.
 */
function shrinktheweb_deleteErrorImages() {
  $aFilenames = array(
    'quota' => THUMBNAIL_DIR . QUOTA_IMAGE,
    'bandwidth' => THUMBNAIL_DIR . BANDWIDTH_IMAGE,
    'no_response' => THUMBNAIL_DIR . NO_RESPONSE_IMAGE,
  );

  foreach ($aFilenames as $sFile) {
    if (file_exists($sFile)) {
      @unlink($sFile);
    }
  }
}

/**
 * Refresh a thumbnail for a url with specified options.
 * First delete it and then do a new request and return the HTML for image loading.
 * !!! SLIGHTLY CHANGED FOR DRUPAL !!!
 * @param $thumbnail
 * @param array $aOptions
 */
function shrinktheweb_refreshThumbnail($url, $aOptions = array()) {
  $aOptions = shrinktheweb_generateOptions($aOptions);
  $aOptions['RefreshOnDemand'] = TRUE;

  shrinktheweb_deleteThumbnail($url, $aOptions);
  shrinktheweb_getThumbnail($url, $aOptions);
}

    /**********************
    *  PRIVATE FUNCTIONS  *
    **********************/

/**
 * Getting the thumbnail with advanced api.
 * !!! SLIGHTLY CHANGED FOR DRUPAL !!!
 * @param $sUrl
 * @param $aOptions
 * @return bool|string
 */
function shrinktheweb_getThumbnailAdvanced($sUrl, $aOptions) {
  $sImageUrl = shrinktheweb_getThumbnail($sUrl, $aOptions);

  // If WAY OVER the limits (i.e. request is ignored by STW), grab an "Account Problem" image and store it as NO_RESPONSE_IMAGE
  if ($sImageUrl == 'no_response') {
    $sImageUrl = shrinktheweb_getNoResponseImage($sUrl, $aOptions);
  }

  return $sImageUrl;
}

/**
 * Gets the thumbnail for the specified website, stores it in the cache,
 * and then returns the relative path to the cached image.
 * @param $sUrl
 * @param $aOptions
 * @return bool|string
 */
function shrinktheweb_getThumbnail($sUrl, $aOptions) {
  // Create cache directory if it doesn't exist.
  shrinktheweb_createCacheDirectory();

  $aArgs = shrinktheweb_generateRequestArgs($aOptions);

  // Try to grab the thumbnail.
  $iCacheDays = CACHE_DAYS + 0;
  if ($iCacheDays >= 0 && $aOptions['Embedded'] != 1) {
    $aArgs['stwurl'] = $sUrl;
    $sImageUrl = shrinktheweb_getCachedThumbnail($aArgs);
  }
  else {
    // Do NOT trigger notify callback on embedded!
    unset($aArgs['stwcallback']);
    // ONLY on "Advanced" method requests!! (not allowed on embedded).
    unset($aArgs['stwu']);
    $aArgs['stwembed'] = 1;
    // MUST come last, since we haven't been urlencoding URL
    $aArgs['stwurl'] = $sUrl;
    if ($aOptions['RefreshOnDemand']) {
      $sRequestUrl = PROTOCOL . 'images.shrinktheweb.com/xino.php';
      shrinktheweb_fileGetContent($sRequestUrl, $aArgs);
    }
    else {
      // Get raw image data.
      $sImageUrl = urldecode(PROTOCOL . 'images.shrinktheweb.com/xino.php?' . http_build_query($aArgs, '', '&'));
    }
  }

  return $sImageUrl;
}

/**
 * Generate options.
 * !!! SLIGHTLY CHANGED FOR DRUPAL !!!
 * @param $aOptions
 * @return mixed
 */
function shrinktheweb_generateOptions($aOptions) {
  // Check if there are options set, otherwise set it to default or false.
  $config = \Drupal::config('shrinktheweb.settings');
  $aOptions['Size'] = isset($aOptions['Size']) ? $aOptions['Size'] : $config->get('shrinktheweb_thumb_size');
  $aOptions['SizeCustom'] = isset($aOptions['SizeCustom']) ? $aOptions['SizeCustom'] : $config->get('shrinktheweb_thumb_size_custom');
  $aOptions['FullSizeCapture'] = isset($aOptions['FullSizeCapture']) ? $aOptions['FullSizeCapture'] : $config->get('shrinktheweb_full_size');
  $aOptions['MaxHeight'] = isset($aOptions['MaxHeight']) ? $aOptions['MaxHeight'] : $config->get('shrinktheweb_max_height');
  $aOptions['NativeResolution'] = isset($aOptions['NativeResolution']) ? $aOptions['NativeResolution'] : $config->get('shrinktheweb_native_res');
  $aOptions['WidescreenY'] = isset($aOptions['WidescreenY']) ? $aOptions['WidescreenY'] : $config->get('shrinktheweb_widescreen_y');
  $aOptions['RefreshOnDemand'] = isset($aOptions['RefreshOnDemand']) ? $aOptions['RefreshOnDemand'] : FALSE;
  $aOptions['Delay'] = isset($aOptions['Delay']) ? $aOptions['Delay'] : $config->get('shrinktheweb_delay');
  $aOptions['Quality'] = isset($aOptions['Quality']) ? $aOptions['Quality'] : $config->get('shrinktheweb_quality');

  return $aOptions;
}

/**
 * Generate the request arguments.
 * @param $aOptions
 * @return mixed
 */
function shrinktheweb_generateRequestArgs($aOptions) {
  $aArgs['stwaccesskeyid'] = ACCESS_KEY;
  $aArgs['stwu'] = SECRET_KEY;
  $aArgs['stwver'] = VER;

  // If SizeCustom is specified and widescreen capturing is not activated,
  // then use that size rather than the size stored in the settings.
  if (!$aOptions['FullSizeCapture'] && !$aOptions['WidescreenY']) {
    // Do we have a custom size?
    if ($aOptions['SizeCustom']) {
      $aArgs['stwxmax'] = $aOptions['SizeCustom'];
    }
    else {
      $aArgs['stwsize'] = $aOptions['Size'];
    }
  }

  // Use fullsize capturing?
  if ($aOptions['FullSizeCapture']) {
    $aArgs['stwfull'] = 1;
    if ($aOptions['SizeCustom']) {
      $aArgs['stwxmax'] = $aOptions['SizeCustom'];
    }
    elseif (isset($aOptions['Size'])) {
      switch ($aOptions['Size']) {
        case 'mcr':
          $aArgs['stwxmax'] = 75;
          break;

        case 'tny':
          $aArgs['stwxmax'] = 90;
          break;

        case 'vsm':
          $aArgs['stwxmax'] = 100;
          break;

        case 'sm':
          $aArgs['stwxmax'] = 120;
          break;

        case 'lg':
          $aArgs['stwxmax'] = 200;
          break;

        case 'xlg':
          $aArgs['stwxmax'] = 320;
          break;
      }
    }
    if ($aOptions['MaxHeight']) {
      $aArgs['stwymax'] = $aOptions['MaxHeight'];
    }
  }

  // Change native resolution?
  if ($aOptions['NativeResolution']) {
    $aArgs['stwnrx'] = $aOptions['NativeResolution'];
    if ($aOptions['WidescreenY']) {
      $aArgs['stwnry'] = $aOptions['WidescreenY'];
      if ($aOptions['SizeCustom']) {
        $aArgs['stwxmax'] = $aOptions['SizeCustom'];
      }
      elseif (isset($aOptions['Size'])) {
        switch ($aOptions['Size']) {
          case 'mcr':
            $aArgs['stwxmax'] = 75;
            break;

          case 'tny':
            $aArgs['stwxmax'] = 90;
            break;

          case 'vsm':
            $aArgs['stwxmax'] = 100;
            break;

          case 'sm':
            $aArgs['stwxmax'] = 120;
            break;

          case 'lg':
            $aArgs['stwxmax'] = 200;
            break;

          case 'xlg':
            $aArgs['stwxmax'] = 320;
            break;
        }
      }
    }
  }

  // Wait after page load in seconds.
  if ($aOptions['Delay']) {
    $aArgs['stwdelay'] = intval($aOptions['Delay']) <= 45 ? intval($aOptions['Delay']) : 45;
  }

  // Use Refresh On-Demand?
  if ($aOptions['RefreshOnDemand']) {
    $aArgs['stwredo'] = 1;
  }

  // Use URL-to_PDF?
  if ($aOptions['PDF']) {
    $aArgs['stwpdf'] = 1;
  }

  // Use another image quality in percent.
  if ($aOptions['Quality']) {
    $aArgs['stwq'] = intval($aOptions['Quality']);
  }

  // Use custom messages?
  if (CUSTOM_MSG_URL) {
    $aArgs['stwrpath'] = CUSTOM_MSG_URL;
  }

  // Use callback notification? (no need to check for new requests; they will automatically download).
  if (CALLBACKURL) {
    $aArgs['stwcallback'] = urlencode(CALLBACKURL . '?token=' . TOKEN);
    // Set the callback like:  &stwcallback='.urlencode('http://domain.tld/stw_notify.php?token=18298jkjsse3').'&stwparameter=...)
    // Your callback MUST be encoded or it may not work with the "notifyNoPush" parameter (to avoid sending data with the notify).
  }

  // Send the notifyNoPush parameter.
  if (NOTIFYNOPUSH) {
    $aArgs['stwcallback'] .= ',' . NOTIFYNOPUSH;
    // We must use JSON with Base64 encoding, which adds 33% size to the image data. If too great, you may send notifyNoPush as:
    // &stwcallback='.urlencode('http://domain.tld/stw_notify.php?token=18298jkjsse3').',1&stwparameter=...)  -- notice the comma and 1
  }

  return $aArgs;
}

/**
 * Get a thumbnail, caching it first if possible.
 * @param null $aArgs
 * @return bool|string
 * @throws \Exception
 */
function shrinktheweb_getCachedThumbnail($aArgs = NULL) {
  $aArgs = is_array($aArgs) ? $aArgs : array();

  // Use arguments to work out the target filename.
  $sFilename = shrinktheweb_generateHash($aArgs) . '.jpg';
  $sFile = THUMBNAIL_DIR . $sFilename;
  $sReturnName = FALSE;
  // Work out if we need to update the cached thumbnail.
  $iForceUpdate = $aArgs['stwredo'] ? TRUE : FALSE;
  if ($iForceUpdate || shrinktheweb_cacheFileExpired($sFile)) {
    // If bandwidth limit has reached return the BANDWIDTH_IMAGE
    if (shrinktheweb_checkLimitReached(THUMBNAIL_DIR . BANDWIDTH_IMAGE)) {
      $sFilename = BANDWIDTH_IMAGE;
    }
    // If quota limit has reached return the QUOTA_IMAGE
    elseif (shrinktheweb_checkLimitReached(THUMBNAIL_DIR . QUOTA_IMAGE)) {
      $sFilename = QUOTA_IMAGE;
    }
    // If WAY OVER the limits (i.e. request is ignored by STW) return the NO_RESPONSE_IMAGE
    elseif (shrinktheweb_checkLimitReached(THUMBNAIL_DIR . NO_RESPONSE_IMAGE)) {
      $sFilename = NO_RESPONSE_IMAGE;
    }
    else {
      // Check if the thumbnail was captured.
      $aImage = shrinktheweb_checkWebsiteThumbnailCaptured($aArgs);
      switch ($aImage['status']) {
        case 'save':
          // Download the image to local path.
          shrinktheweb_downloadRemoteImageToLocalPath($aImage['url'], $sFile);
          break;

        case 'nosave':
          // Don't save the image but return the url.
          return $aImage['url'];
          break;

        case 'quota_exceed':
          // Download the image to local path for locking requests.
          $sFilename = QUOTA_IMAGE;
          $sFile = THUMBNAIL_DIR . $sFilename;
          shrinktheweb_downloadRemoteImageToLocalPath($aImage['url'], $sFile);
          break;

        case 'bandwidth_exceed':
          // Download the image to local path for locking requests.
          $sFilename = BANDWIDTH_IMAGE;
          $sFile = THUMBNAIL_DIR . $sFilename;
          shrinktheweb_downloadRemoteImageToLocalPath($aImage['url'], $sFile);
          break;

        default:
          // Otherwise return the status.
          return $aImage['status'];
      }
    }
  }

  $sFile = THUMBNAIL_DIR . $sFilename;
  // Check if file exists.
  if (file_exists($sFile)) {
    $sReturnName = THUMBNAIL_URI . $sFilename;
  }

  if ($iForceUpdate) {
    return $aImage['captured_on'];
  }
  else {
    return $sReturnName;
  }
}

/**
 * Method that checks if the thumbnail for the specified website exists.
 * @param $aArgs
 * @return array
 * @throws \Exception
 */
function shrinktheweb_checkWebsiteThumbnailCaptured($aArgs) {
  $sRequestUrl = PROTOCOL . 'images.shrinktheweb.com/xino.php';
  $sRemoteData = shrinktheweb_fileGetContent($sRequestUrl, $aArgs);

  // Check if we get no response or the maintenance string.
  if ($sRemoteData == '' || $sRemoteData == 'offline') {
    $aImage = array('status' => 'no_response');
    if ($sRemoteData != '') {
      $aImage['message'] = MAINTENANCE;
    }
  }
  else {
    $aResponse = shrinktheweb_getXMLResponse($sRemoteData);
    // Thumbnail is existing, download it.
    if ($aResponse['exists'] && $aResponse['thumbnail'] != '') {
      $aImage = array(
        'status' => 'save',
        'url' => $aResponse['thumbnail'],
        'captured_on' => $aResponse['stw_last_captured']
      );
    }
    // Bandwidth limit has reached, grab embedded image and store it as BANDWIDTH_IMAGE
    elseif ($aResponse['stw_bandwidth_remaining'] == 0 && !$aResponse['locked'] && !$aResponse['invalid'] && !$aResponse['exists'] && !$aResponse['problem']) {
      $aImage = array(
        'status' => 'bandwidth_exceed',
        'url' => $aResponse['thumbnail']
      );
    }
    // Quota limit has reached, grab embedded image and store it as QUOTA_IMAGE
    elseif ($aResponse['stw_quota_remaining'] == 0 && !$aResponse['locked'] && !$aResponse['invalid'] && !$aResponse['exists'] && !$aResponse['problem']) {
      $aImage = array(
        'status' => 'quota_exceed',
        'url' => $aResponse['thumbnail']
      );
    }
    // An error has occurred, return the url but don't save the image.
    elseif (!$aResponse['exists'] && $aResponse['thumbnail'] != '') {
      $aImage = array('status' => 'nosave', 'url' => $aResponse['thumbnail']);
    }
    // Otherwise return error because we don't know the situation.
    else {
      $aImage = array('status' => 'error');
    }

    // Add the request to the database if debug is enabled.
    if (DEBUG) {

      if (isset($aArgs['stwsize'])) {
        switch ($aArgs['stwsize']) {
          case 'mcr':
            $stwxmax = 75;
            $stwymax = 57;
            break;

          case 'tny':
            $stwxmax = 90;
            $stwymax = 68;
            break;

          case 'vsm':
            $stwxmax = 100;
            $stwymax = 75;
            break;

          case 'sm':
            $stwxmax = 120;
            $stwymax = 90;
            break;

          case 'lg':
            $stwxmax = 200;
            $stwymax = 150;
            break;

          case 'xlg':
            $stwxmax = 320;
            $stwymax = 240;
            break;
        }
      }

      $aArgs['stwq'] = isset($aArgs['stwq']) ? $aArgs['stwq'] : 95;
      $aArgs['stwfull'] = isset($aArgs['stwfull']) ? $aArgs['stwfull'] : 0;
      $aArgs['stwxmax'] = isset($aArgs['stwxmax']) ? $aArgs['stwxmax'] : (isset($stwxmax) ? $stwxmax : 0);
      $aArgs['stwymax'] = isset($aArgs['stwymax']) ? $aArgs['stwymax'] : (isset($stwymax) ? $stwymax : 0);
      $aArgs['stwnrx'] = isset($aArgs['stwnrx']) ? $aArgs['stwnrx'] : 1024;
      $aArgs['stwnry'] = isset($aArgs['stwnry']) ? $aArgs['stwnry'] : 768;
      $sHash = shrinktheweb_generateHash($aArgs);
      $aFields = array(
        'stw_domain' => $aArgs['stwurl'],
        'stw_timestamp' => time(),
        'stw_capturedon' => $aResponse['stw_last_captured'],
        'stw_quality' => $aArgs['stwq'],
        'stw_full' => $aArgs['stwfull'],
        'stw_xmax' => $aArgs['stwxmax'],
        'stw_ymax' => $aArgs['stwymax'],
        'stw_nrx' => $aArgs['stwnrx'],
        'stw_nry' => $aArgs['stwnry'],
        'stw_invalid' => $aResponse['invalid'] != '' ? $aResponse['invalid'] : 0,
        'stw_stwerrcode' => $aResponse['stw_response_code'],
        'stw_error' => $aResponse['error'] != '' ? $aResponse['error'] : 0,
        'stw_errcode' => $aResponse['stw_response_status'],
        'stw_hash' => $sHash,
      );
      \Drupal::database()->merge('shrinktheweb_log')
        ->key(array('stw_hash' => $sHash))
        ->fields($aFields)
        ->updateFields($aFields)
        ->execute();
    }
  }

  return $aImage;
}

/**
 * Method to get image at the specified remote Url and attempt to save it to the specified local path.
 * @param $sRemoteUrl
 * @param $sFile
 * @return bool
 */
function shrinktheweb_downloadRemoteImageToLocalPath($sRemoteUrl, $sFile) {
  $sRemoteData = shrinktheweb_fileGetContent($sRemoteUrl, array());

  // Only save data if we managed to get the file content.
  if ($sRemoteData) {
    $rFile = fopen($sFile, "w+");
    fputs($rFile, $sRemoteData);
    fclose($rFile);
  }
  else {
    // Try to delete file if download failed.
    if (file_exists($sFile)) {
      @unlink($sFile);
    }
    return FALSE;
  }
  return TRUE;
}

/**
 * Gets the account problem image and returns the relative path to the cached image.
 * @param $sUrl
 * @param $aOptions
 * @return bool|string
 */
function shrinktheweb_getNoResponseImage($sUrl, $aOptions) {
  // Create cache directory if it doesn't exist.
  shrinktheweb_createCacheDirectory();

  $aOptions = shrinktheweb_generateOptions($aOptions);

  $aArgs['stwaccesskeyid'] = 'accountproblem';

  if ($aOptions['SizeCustom']) {
    $aArgs['stwxmax'] = $aOptions['SizeCustom'];
  }
  else {
    $aArgs['stwsize'] = $aOptions['Size'];
  }

  $sRequestUrl = PROTOCOL . 'images.shrinktheweb.com/xino.php';
  $sRemoteData = shrinktheweb_fileGetContent($sRequestUrl, $aArgs);

  if ($sRemoteData != '') {
    $aResponse = shrinktheweb_getXMLResponse($sRemoteData);

    if (!$aResponse['exists'] && $aResponse['thumbnail'] != '') {
      $sImageUrl = $aResponse['thumbnail'];

      $sFilename = NO_RESPONSE_IMAGE;
      $sFile = THUMBNAIL_DIR . $sFilename;
      $isDownloaded = shrinktheweb_downloadRemoteImageToLocalPath($sImageUrl, $sFile);

      if ($isDownloaded == TRUE) {
        return THUMBNAIL_URI . $sFilename;
      }
    }
  }

  return FALSE;
}

/**
 * Check if the limit reached image is existing, if so return true
 * return false if there is no image existing or the limit reached file is
 * older then 6 hours.
 * @param $sFile
 * @return bool
 */
function shrinktheweb_checkLimitReached($sFile) {
  // File is not existing.
  if (!file_exists($sFile)) {
    return FALSE;
  }

  // Is file older then 6 hours?
  $iCutoff = time() - (3600 * 6);
  if (filemtime($sFile) <= $iCutoff) {
    @unlink($sFile);
    return FALSE;
  }

  // File is existing and not expired!
  return TRUE;
}

/**
 * Create cache directory if it doesn't exist.
 */
function shrinktheweb_createCacheDirectory() {
  // Create cache directory if it doesn't exist.
  if (!file_exists(THUMBNAIL_DIR)) {
    @mkdir(THUMBNAIL_DIR, 0777, TRUE);
  }
  else {
    // Try to make the directory writable.
    @chmod(THUMBNAIL_DIR, 0777);
  }
}

/**
 * Generate the hash for the thumbnail, this is used as filename also.
 * @param $aArgs
 * @return string
 */
function shrinktheweb_generateHash($aArgs) {
  $sPrehash = $aArgs['stwfull'] ? 'a' : 'c';
  $sPrehash .= $aArgs['stwxmax'] . 'x' . $aArgs['stwymax'];
  if ($aArgs['stwnrx']) {
    $sPrehash .= 'b' . $aArgs['stwnrx'] . 'x' . $aArgs['stwnry'];
  }
  // For backward compatibility with existing filenames.
  $sUrl = urldecode($aArgs['stwurl']);

  return md5($sPrehash . $aArgs['stwq'] . $sUrl);
}

/**
 * Store the XML response in an array and generate status bits.
 * @param $sResponse
 * @return mixed
 */
function shrinktheweb_getXMLResponse($sResponse) {
  // If simplexml is available, we can do more stuff!
  if (extension_loaded('simplexml')) {
    $oDOM = new DOMDocument();
    $oDOM->loadXML($sResponse);
    $sXML = simplexml_import_dom($oDOM);
    $sXMLLayout = 'http://www.shrinktheweb.com/doc/stwresponse.xsd';

    // Pull response codes from XML feed.
    $thumbnail = (array) $sXML->children($sXMLLayout)->Response->ThumbnailResult->Thumbnail;
    $aResponse['thumbnail'] = $thumbnail[0];
    $aResponse['stw_action'] = $thumbnail[1];
    // HTTP Response Code.
    $aResponse['stw_response_status'] = $sXML->children($sXMLLayout)->Response->ResponseStatus->StatusCode;
    // STW Error Response.
    $aResponse['stw_response_code'] = $sXML->children($sXMLLayout)->Response->ResponseCode->StatusCode;
    // Last Captured.
    $aResponse['stw_last_captured'] = $sXML->children($sXMLLayout)->Response->ResponseTimestamp->StatusCode;
    // New Reqs left for today.
    $aResponse['stw_quota_remaining'] = $sXML->children($sXMLLayout)->Response->Quota_Remaining->StatusCode;
    // New Reqs left for today.
    $aResponse['stw_bandwidth_remaining'] = $sXML->children($sXMLLayout)->Response->Bandwidth_Remaining->StatusCode;
    // Not yet implemented.
    $aResponse['stw_category_code'] = $sXML->children($sXMLLayout)->Response->CategoryCode->StatusCode;
  }
  else {
    // LEGACY SUPPORT.
    $aResponse['stw_response_status'] = shrinktheweb_getLegacyResponse('ResponseStatus', $sResponse);
    $aResponse['stw_response_code'] = shrinktheweb_getLegacyResponse('ResponseCode', $sResponse);

    // Check remaining quota.
    $aResponse['stw_quota_remaining'] = shrinktheweb_getLegacyResponse('Quota_Remaining', $sResponse);
    // Check remaining bandwidth.
    $aResponse['stw_bandwidth_remaining'] = shrinktheweb_getLegacyResponse('Bandwidth_Remaining', $sResponse);

    // Get thumbnail and status.
    $aThumbnail = shrinktheweb_getThumbnailStatus($sResponse);
    $aResponse = array_merge($aResponse, $aThumbnail);
  }

  if ($aResponse['stw_action'] == 'delivered') {
    $aResponse['exists'] = TRUE;
  }
  else {
    $aResponse['exists'] = FALSE;
  }

  if ($aResponse['stw_action'] == 'fix_and_retry') {
    $aResponse['problem'] = TRUE;
  }
  else {
    $aResponse['problem'] = FALSE;
  }

  if ($aResponse['stw_action'] == 'noretry') {
    $aResponse['error'] = TRUE;
  }
  else {
    $aResponse['error'] = FALSE;
  }

  // If we use the advanced api for free account we get an invalid request.
  if ($aResponse['stw_response_code'] == 'INVALID_REQUEST') {
    $aResponse['invalid'] = TRUE;
  }
  else {
    $aResponse['invalid'] = FALSE;
  }

  // If our domain or IP is not listed in the account's "Allowed Referrers" AND "Lock to Account" is enabled, then we get this error.
  if ($aResponse['stw_response_code'] == 'LOCK_TO_ACCOUNT') {
    $aResponse['locked'] = TRUE;
  }
  else {
    $aResponse['locked'] = FALSE;
  }

  return $aResponse;
}

/**
 * Get status code in case of legacy response
 * @param $sSearch
 * @param $s
 * @return bool
 */
function shrinktheweb_getLegacyResponse($sSearch, $s) {
  $sRegex = '/<[^:]*:' . $sSearch . '[^>]*>[^<]*<[^:]*:StatusCode[^>]*>([^<]*)<\//';
  if (preg_match($sRegex, $s, $sMatches)) {
    return $sMatches[1];
  }
  return FALSE;
}

/**
 * Get thumbnail status
 * @param $s
 * @return array|bool
 */
function shrinktheweb_getThumbnailStatus($s) {
  $sRegex = '/<[^:]*:ThumbnailResult?[^>]*>[^<]*<[^:]*:Thumbnail\s*(?:Exists=\"((?:true)|(?:false))\")+[^>]*>([^<]*)<\//';
  if (preg_match($sRegex, $s, $sMatches)) {
    return array(
      'stw_action' => $sMatches[1],
      'thumbnail' => $sMatches[2],
    );
  }
  return FALSE;
}

/**
 * Determine if specified file has expired from the cache.
 * @param $sFile
 * @return bool
 */
function shrinktheweb_cacheFileExpired($sFile) {
  // Use setting to check age of files.
  $iCacheDays = CACHE_DAYS + 0;

  // Don't update image once it is cached.
  if ($iCacheDays == 0 && file_exists($sFile)) {
    return FALSE;
    // Check age of file and if file exists return false, otherwise cache file again.
  }
  else {
    $iCutoff = time() - (3600 * 24 * $iCacheDays);
    return (!file_exists($sFile) || filemtime($sFile) <= $iCutoff);
  }
}

/**
 * Safe method to get the value from an array using the specified key.
 * @param $aArray
 * @param $sKey
 * @param bool $isReturnSpace
 * @return bool|string
 */
function shrinktheweb_getArrayValue($aArray, $sKey, $isReturnSpace = FALSE) {
  if ($aArray && isset($aArray[$sKey])) {
    return $aArray[$sKey];
  }

  // If returnSpace is true, then return a space rather than nothing at all.
  if ($isReturnSpace) {
    return '&nbsp;';
  }
  else {
    return FALSE;
  }
}

/**
 * Gets file content by URL.
 * @param $sFileUrl
 * @param array $aParams
 * @return mixed|string
 */
function shrinktheweb_fileGetContent($sFileUrl, $aParams = array()) {
  $sParams = '?';
  foreach ($aParams as $sKey => $sValue) {
    $sParams .= $sKey . '=' . $sValue . '&';
  }
  $sParams = substr($sParams, 0, -1);

  $sResult = '';
  if (function_exists('curl_init')) {
    $rConnect = curl_init();

    curl_setopt($rConnect, CURLOPT_URL, $sFileUrl . $sParams);
    curl_setopt($rConnect, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($rConnect, CURLOPT_FOLLOWLOCATION, 0);
    // Must be 0 or else headers will break SimpleXML parsing.
    curl_setopt($rConnect, CURLOPT_HEADER, 0);
    $sResult = curl_exec($rConnect);
    curl_close($rConnect);
  }
  else {
    $sResult = @file_get_contents($sFileUrl . $sParams);
  }

  return $sResult;
}

/**
 * Get Account Info and return it as array.
 * @param array|mixed|null $accessKey
 * @param array|mixed|null $secretKey
 * @return mixed
 */
function shrinktheweb_getAccountInfo($accessKey = ACCESS_KEY, $secretKey = SECRET_KEY) {
  $aArgs['stwaccesskeyid'] = $accessKey;
  $aArgs['stwu'] = $secretKey;

  $sRequestUrl = PROTOCOL . 'images.shrinktheweb.com/account.php';
  $sRemoteData = shrinktheweb_fileGetContent($sRequestUrl, $aArgs);
  // Check if we get no response or the maintenance string.
  if ($sRemoteData == '' || $sRemoteData == 'offline') {
    $aImage = array('stw_status' => 'no_response');
    if ($sRemoteData != '') {
      $aImage['message'] = MAINTENANCE;
    }
  }
  else {
    $aResponse = shrinktheweb_getAccXMLResponse($sRemoteData);
  }

  return $aResponse;
}

/**
 * Store the Account XML response in an array.
 * @param $sResponse
 * @return mixed
 */
function shrinktheweb_getAccXMLResponse($sResponse) {
  // If simplexml is available, we can do more stuff!
  if (extension_loaded('simplexml')) {
    $oDOM = new DOMDocument();
    $oDOM->loadXML($sResponse);
    $sXML = simplexml_import_dom($oDOM);
    $sXMLLayout = 'http://www.shrinktheweb.com/doc/stwacctresponse.xsd';

    // Pull response codes from XML feed.
    $aResponse['stw_response_status'] = $sXML->children($sXMLLayout)->Response->Status->StatusCode;
    // Response Code.
    $aResponse['stw_account_level'] = $sXML->children($sXMLLayout)->Response->Account_Level->StatusCode;
    // Check for enabled upgrades.
    // Inside Pages.
    $aResponse['stw_inside_pages'] = $sXML->children($sXMLLayout)->Response->Inside_Pages->StatusCode;
    // Custom Size.
    $aResponse['stw_custom_size'] = $sXML->children($sXMLLayout)->Response->Custom_Size->StatusCode;
    // Full Length.
    $aResponse['stw_full_length'] = $sXML->children($sXMLLayout)->Response->Full_Length->StatusCode;
    // Refresh OnDemand.
    $aResponse['stw_refresh_ondemand'] = $sXML->children($sXMLLayout)->Response->Refresh_OnDemand->StatusCode;
    // Custom Delay.
    $aResponse['stw_custom_delay'] = $sXML->children($sXMLLayout)->Response->Custom_Delay->StatusCode;
    // Custom Quality.
    $aResponse['stw_custom_quality'] = $sXML->children($sXMLLayout)->Response->Custom_Quality->StatusCode;
    // Custom Resolution.
    $aResponse['stw_custom_resolution'] = $sXML->children($sXMLLayout)->Response->Custom_Resolution->StatusCode;
    // Custom Messages.
    $aResponse['stw_custom_messages'] = $sXML->children($sXMLLayout)->Response->Custom_Messages->StatusCode;
  }
  else {
    // LEGACY SUPPORT.
    $aResponse['stw_response_status'] = shrinktheweb_getLegacyResponse('Status', $sResponse);
    // Account level
    $aResponse['stw_account_level'] = shrinktheweb_getLegacyResponse('Account_Level', $sResponse);
    // Check for enabled upgrades.
    // Inside Pages.
    $aResponse['stw_inside_pages'] = shrinktheweb_getLegacyResponse('Inside_Pages', $sResponse);
    // Custom Size.
    $aResponse['stw_custom_size'] = shrinktheweb_getLegacyResponse('Custom_Size', $sResponse);
    // Full Length.
    $aResponse['stw_full_length'] = shrinktheweb_getLegacyResponse('Full_Length', $sResponse);
    // Refresh OnDemand.
    $aResponse['stw_refresh_ondemand'] = shrinktheweb_getLegacyResponse('Refresh_OnDemand', $sResponse);
    // Custom Delay.
    $aResponse['stw_custom_delay'] = shrinktheweb_getLegacyResponse('Custom_Delay', $sResponse);
    // Custom Quality.
    $aResponse['stw_custom_quality'] = shrinktheweb_getLegacyResponse('Custom_Quality', $sResponse);
    // Custom Resolution.
    $aResponse['stw_custom_resolution'] = shrinktheweb_getLegacyResponse('Custom_Resolution', $sResponse);
    // Custom Messages.
    $aResponse['stw_custom_messages'] = shrinktheweb_getLegacyResponse('Custom_Messages', $sResponse);
  }

  return $aResponse;
}
