<?php

/**
 * @file
 * Contains the map of ISO country codes to USPS Individual Country Listings.
 */

/**
 * Returns the country name formatted according to the USPS requirements.
 *
 * USPS uses the ISO 3166 English short names in most cases. This function
 * handles the exceptions.
 *
 * @param string $code
 *   ISO 3166-1 2-character country code.
 *
 * @return string
 *   Country name string for use by the USPS International Rate API.
 *
 * @see http://pe.usps.gov/text/imm/immctry.htm
 */
function uc_usps_country_map($code = NULL) {
  $countries = [
    'AX' => 'Aland Island (Finland)',
    // Heard Island and McDonald Islands.
    'AU' => 'Australia',
    'BO' => 'Bolivia',
    'BQ' => 'Bonaire (Netherlands Antilles)',
    'VG' => 'British Virgin Islands',
    'CC' => 'Cocos Island (Australia)',
    'CD' => 'Congo, Democratic Republic of the',
    'CG' => 'Congo, Republic of the',
    'CW' => 'Curacao (Netherlands Antilles)',
    'CI' => "Cote d'Ivoire",
    'TL' => 'East Timor (Indonesia)',
    'FK' => 'Falkland Islands',
    // French Southern Territories.
    'TF' => 'France',
    'GE' => 'Georgia, Republic of',
    'GB' => 'Great Britain and Northern Ireland',
    // British Indian Ocean Territory.
    'IO' => 'Great Britain and Northern Ireland',
    // South Georgia and the South Sandwich Islands.
    'GS' => 'Great Britain and Northern Ireland',
    'IR' => 'Iran',
    // Palestinian Territory, Occupied.
    'IL' => 'Israel',
    'IM' => 'Isle of Man (Great Britain and Northern Ireland)',
    'MD' => 'Moldova',
    // Western Sahara.
    'MA' => 'Morocco',
    'KP' => "Korea, Democratic People's Republic of (North Korea)",
    // Bouvet Island.
    'BV' => 'Norway',
    // Svalbard and Jan Mayen.
    'SJ' => 'Norway',
    'KR' => 'Korea, Republic of (South Korea)',
    'LA' => 'Laos',
    'MC' => 'Monaco (France)',
    'MM' => 'Myanmar (Burma)',
    'PN' => 'Pitcairn Island',
    'RE' => 'Reunion',
    'RU' => 'Russia',
    'RS' => 'Serbia, Republic of',
    'BL' => 'Saint Barthelemy (Guadeloupe)',
    'SH' => 'Saint Helena',
    'SX' => 'Saint Maarten (Dutch) (Netherlands Antilles)',
    'MF' => 'Saint Martin (French) (Guadeloupe)',
    'SK' => 'Slovak Republic',
    'TW' => 'Taiwan',
    'TZ' => 'Tanzania',
    'TR' => 'Turkey',
    'UA' => 'Ukraine',
    'VA' => 'Vatican City',
    'VE' => 'Venezuela',
    'WF' => 'Wallis and Futuna Islands',
  ];

  if ($code) {
    if (isset($countries[$code])) {
      return $countries[$code];
    }
    else {
      $country = \Drupal::service('country_manager')->getCountry($code);
      return $country->name;
    }
  }

  return $countries;
}
