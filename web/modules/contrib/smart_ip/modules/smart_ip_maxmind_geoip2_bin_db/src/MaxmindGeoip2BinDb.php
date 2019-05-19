<?php
/**
 * @file
 * Contains \Drupal\smart_ip_maxmind_geoip2_bin_db\MaxmindGeoip2BinDb.
 */

namespace Drupal\smart_ip_maxmind_geoip2_bin_db;


class MaxmindGeoip2BinDb {

  /**
   * MaxMind GeoIP2 licensed version.
   */
  const LINCENSED_VERSION = 'licensed';

  /**
   * MaxMind GeoIP2 lite or free version.
   */
  const LITE_VERSION = 'lite';

  /**
   * MaxMind GeoIP2 "City" edition.
   */
  const CITY_EDITION = 'city';

  /**
   * MaxMind GeoIP2 "Coutry" edition.
   */
  const COUNTRY_EDITION = 'country';

  /**
   * MaxMind GeoIP2 licensed version download URL.
   */
  const LINCENSED_DL_URL = 'http://download.maxmind.com/app/geoip_download';

  /**
   * MaxMind GeoIP2 lite or free version download URL.
   */
  const LITE_DL_URL = 'http://geolite.maxmind.com/download/geoip/database';

  /**
   * MaxMind GeoIP2 licensed version city edition binary database filename.
   * Can be verified at:
   * http://updates.maxmind.com/app/update_getfilename?product_id=GeoIP2-City
   */
  const FILENAME_LINCENSED_CITY = 'GeoIP2-City';

  /**
   * MaxMind GeoIP2 lite or free version city edition binary database filename.
   * Can be verified at:
   * http://updates.maxmind.com/app/update_getfilename?product_id=GeoLite2-City
   */
  const FILENAME_LITE_CITY = 'GeoLite2-City';

  /**
   * MaxMind GeoIP2 licensed version country edition binary database filename.
   * Can be verified at:
   * http://updates.maxmind.com/app/update_getfilename?product_id=GeoIP2-Country
   */
  const FILENAME_LINCENSED_COUNTRY = 'GeoIP2-Country';

  /**
   * MaxMind GeoIP2 lite or free version country edition binary database
   * filename. Can be verified at:
   * http://updates.maxmind.com/app/update_getfilename?product_id=GeoLite2-Country
   */
  const FILENAME_LITE_COUNTRY = 'GeoLite2-Country';

  /**
   * MaxMind GeoIP2 binary database file extension name.
   */
  const FILE_EXTENSION = '.mmdb';

}
