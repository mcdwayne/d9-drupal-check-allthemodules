<?php

namespace Drupal\commerce_usps;

/**
 * Class that sets the shipment details needed for the USPS request.
 *
 * @package Drupal\commerce_usps
 */
class USPSShipmentInternational extends USPSShipmentBase implements USPSShipmentInterface {

  /**
   * Returns an initialized rate package object.
   *
   * @return \USPS\RatePackage
   *   The rate package entity.
   */
  public function buildPackage() {
    parent::buildPackage();

    // Object has to be created in exact order defined
    // by IntlRateV2 API.
    // See:  https://www.usps.com/business/web-tools-apis/rate-calculator-api.pdf
    $this->setWeight();
    $this->uspsPackage->setField('Machinable', 'True');
    $this->uspsPackage->setField('MailType', 'Package');
    // Todo: Add shipment value for insurance.
    $this->uspsPackage->setField('ValueOfContents', 0);
    $this->setCountry();
    $this->uspsPackage->setField('Container', 'RECTANGULAR');
    $this->setDimensions();
    $this->setOriginZip();

    return $this->uspsPackage;
  }

  /**
   * Sets the origin zip property.
   */
  protected function setOriginZip() {
    /** @var \CommerceGuys\Addressing\Address $address */
    $address = $this->commerceShipment->getOrder()->getStore()->getAddress();
    $this->uspsPackage->setField('OriginZip', (int) $address->getPostalCode());
  }

  /**
   * Sets the country property.
   */
  protected function setCountry() {
    /** @var \CommerceGuys\Addressing\Address $address */
    $address = $this->commerceShipment->getShippingProfile()->get('address')->first();
    $country = $this->getCountryName($address->getCountryCode());
    $this->uspsPackage->setField('Country', $country);
  }

  /**
   * Sets the destination postal code property.
   */
  protected function setDestinationPostalCode() {
    /** @var \CommerceGuys\Addressing\Address $address */
    $address = $this->commerceShipment->getShippingProfile()->get('address')->first();
    $this->uspsPackage->setField('DestinationPostalCode', $address->getPostalCode());
  }

  /**
   * Match country codes to their country name.
   *
   * See: https://pe.usps.com/text/imm/immctry.htm.
   *
   * @param string $country_code
   *   The country code.
   *
   * @return mixed
   *   The name of the country if defined.
   */
  protected function getCountryName($country_code) {
    $countries = [
      'AD' => 'Andorra',
      'AE' => 'Abu Dhabi (United Arab Emirates)',
      'AF' => 'Afghanistan',
      'AG' => 'Antigua and Barbuda',
      'AI' => 'Anguilla',
      'AL' => 'Albania',
      'AM' => 'Armenia',
      'AN' => 'Netherlands Antilles',
      'AO' => 'Angola',
      'AQ' => 'Antarctica',
      'AR' => 'Argentina',
      'AS' => 'Samoa, American, United States ',
      'AT' => 'Austria',
      'AU' => 'Australia',
      'AW' => 'Aruba',
      'AX' => 'Aland Island (Findland)',
      'AZ' => 'Azerbaijan',
      'BA' => 'Bosnia-Herzegovina',
      'BB' => 'Barbados',
      'BD' => 'Bangladesh',
      'BE' => 'Belgium',
      'BF' => 'Burkina Faso',
      'BG' => 'Bulgaria',
      'BH' => 'Bahrain',
      'BI' => 'Burundi',
      'BJ' => 'Benin',
      'BL' => 'Saint Barthélemy (Guadeloupe)',
      'BM' => 'Bermuda',
      'BN' => 'Brunei Darussalam',
      'BO' => 'Bolivia',
      'BR' => 'Brazil',
      'BS' => 'Bahamas',
      'BT' => 'Bhutan',
      'BV' => 'Bouvet Island',
      'BW' => 'Botswana',
      'BY' => 'Belarus',
      'BZ' => 'Belize',
      'CA' => 'Canada',
      'CC' => 'Cocos Island (Australia)',
      'CD' => 'Congo, Republic of the',
      'CF' => 'Central African Republic',
      'CG' => 'Congo, Democratic Republic of the',
      'CH' => 'Switzerland',
      'CI' => 'Ivory Coast (Cote d’Ivoire)',
      'CK' => 'Cook Islands (New Zealand)',
      'CL' => 'Chile',
      'CM' => 'Cameroon',
      'CN' => 'China',
      'CO' => 'Colombia',
      'CR' => 'Costa Rica',
      'CU' => 'Cuba',
      'CW' => 'Curaçao',
      'CV' => 'Cape Verde',
      'CX' => 'Christmas Island',
      'CY' => 'Cyprus',
      'CZ' => 'Czech Republic',
      'DE' => 'Germany',
      'DJ' => 'Djibouti',
      'DK' => 'Denmark',
      'DM' => 'Dominica',
      'DO' => 'Dominican Republic',
      'DZ' => 'Algeria',
      'EC' => 'Ecuador',
      'EE' => 'Estonia',
      'EG' => 'Egypt',
      'EH' => 'Western Sahara',
      'ER' => 'Eritrea',
      'ES' => 'Spain',
      'ET' => 'Ethiopia',
      'FI' => 'Finland',
      'FJ' => 'Fiji',
      'FK' => 'Falkland Islands',
      'FM' => 'Micronesia',
      'FO' => 'Faroe Islands',
      'FR' => 'France',
      'GA' => 'Gabon',
      'GB' => 'Great Britain and Northern Ireland',
      'GD' => 'Grenada',
      'GE' => 'Georgia, Republic of',
      'GF' => 'French Guiana',
      'GG' => 'Guernsey (Channel Islands) (Great Britain and Northern Ireland)',
      'GH' => 'Ghana',
      'GI' => 'Gibraltar',
      'GL' => 'Greenland',
      'GM' => 'Gambia',
      'GN' => 'Guinea',
      'GP' => 'Guadeloupe',
      'GQ' => 'Equatorial Guinea',
      'GR' => 'Greece',
      'GS' => 'South Georgia (Falkland Islands)',
      'GT' => 'Guatemala',
      'GU' => 'Guam',
      'GW' => 'Guinea-Bissau',
      'GY' => 'Guyana',
      'HK' => 'Hong Kong',
      'HM' => 'Heard Island and McDonald Islands',
      'HN' => 'Honduras',
      'HR' => 'Croatia',
      'HT' => 'Haiti',
      'HU' => 'Hungary',
      'ID' => 'Indonesia',
      'IE' => 'Ireland',
      'IL' => 'Israel',
      'IM' => 'Isle of Man (Great Britain and Northern Ireland)',
      'IN' => 'India',
      'IO' => 'British Indian Ocean Territory',
      'IQ' => 'Iraq',
      'IR' => 'Iran',
      'IS' => 'Iceland',
      'IT' => 'Italy',
      'JE' => 'Jersey (Channel Islands) (Great Britain and Northern Ireland)',
      'JM' => 'Jamaica',
      'JO' => 'Jordan',
      'JP' => 'Japan',
      'KE' => 'Kenya',
      'KG' => 'Kyrgyzstan',
      'KH' => 'Cambodia',
      'KI' => 'Kiribati',
      'KM' => 'Comoros',
      'KN' => 'Saint Kitts (Saint Christopher and Nevis)',
      'KP' => 'North Korea (Korea, Democratic People’s Republic of)',
      'KR' => 'South Korea (Korea, Republic of)',
      'KW' => 'Kuwait',
      'KY' => 'Cayman Islands',
      'KZ' => 'Kazakhstan',
      'LA' => 'Laos',
      'LB' => 'Lebanon',
      'LC' => 'Saint Lucia',
      'LI' => 'Liechtenstein',
      'LK' => 'Sri Lanka',
      'LR' => 'Liberia',
      'LS' => 'Lesotho',
      'LT' => 'Lithuania',
      'LU' => 'Luxembourg',
      'LV' => 'Latvia',
      'LY' => 'Libya',
      'MA' => 'Morocco',
      'MC' => 'Monaco',
      'MD' => 'Moldova',
      'ME' => 'Montenegro',
      'MF' => 'Saint Martin (French)',
      'MG' => 'Madagascar',
      'MH' => 'Marshall Islands, Republic of the',
      'MK' => 'Macedonia, Republic of',
      'ML' => 'Mali',
      'MM' => 'Myanmar (Burma)',
      'MN' => 'Mongolia',
      'MO' => 'Macao',
      'MP' => 'Northern Mariana Islands, Commonwealth of',
      'MQ' => 'Martinique',
      'MR' => 'Mauritania',
      'MS' => 'Montserrat',
      'MT' => 'Malta',
      'MU' => 'Mauritius',
      'MV' => 'Maldives',
      'MW' => 'Malawi',
      'MX' => 'Mexico',
      'MY' => 'Malaysia',
      'MZ' => 'Mozambique',
      'NA' => 'Namibia',
      'NC' => 'New Caledonia',
      'NE' => 'Niger',
      'NF' => 'Norfolk Island (Australia)',
      'NG' => 'Nigeria',
      'NI' => 'Nicaragua',
      'NL' => 'Netherlands',
      'NO' => 'Norway',
      'NP' => 'Nepal',
      'NR' => 'Nauru',
      'NU' => 'Niue (New Zealand)',
      'NZ' => 'New Zealand',
      'OM' => 'Oman',
      'PA' => 'Panama',
      'PE' => 'Peru',
      'PF' => 'French Polynesia',
      'PG' => 'Papua New Guinea',
      'PH' => 'Philippines',
      'PK' => 'Pakistan',
      'PL' => 'Poland',
      'PM' => 'Saint Pierre and Miquelon',
      'PN' => 'Pitcairn Island',
      'PR' => 'Puerto Rico',
      'PS' => 'Palestinian Territory',
      'PT' => 'Portugal',
      'PW' => 'Palau',
      'PY' => 'Paraguay',
      'QA' => 'Qatar',
      'RE' => 'Bourbon (Reunion)',
      'RO' => 'Romania',
      'RS' => 'Serbia, Republic of',
      'RU' => 'Russia',
      'RW' => 'Rwanda',
      'SA' => 'Saudi Arabia',
      'SB' => 'Solomon Islands',
      'SC' => 'Seychelles',
      'SD' => 'Sudan',
      'SE' => 'Sweden',
      'SG' => 'Singapore',
      'SH' => 'Saint Helena',
      'SI' => 'Slovenia',
      'SJ' => 'Svalbard and Jan Mayen',
      'SK' => 'Slovak Republic (Slovakia)',
      'SL' => 'Sierra Leone',
      'SM' => 'San Marino',
      'SN' => 'Senegal',
      'SO' => 'Somalia',
      'SR' => 'Suriname',
      'ST' => 'Sao Tome and Principe',
      'SV' => 'El Salvador',
      'SY' => 'Syria',
      'SZ' => 'Swaziland',
      'TC' => 'Turks and Caicos Islands',
      'TD' => 'Chad',
      'TF' => 'French Southern Territories',
      'TG' => 'Togo',
      'TH' => 'Thailand',
      'TJ' => 'Tajikistan',
      'TK' => 'Tokelau (Union Group) (Western Samoa)',
      'TL' => 'Timor-Leste, Democratic Republic of',
      'TM' => 'Turkmenistan',
      'TN' => 'Tunisia',
      'TO' => 'Tonga',
      'TR' => 'Turkey',
      'TT' => 'Trinidad and Tobago',
      'TV' => 'Tuvalu',
      'TW' => 'Taiwan',
      'TZ' => 'Tanzania',
      'UA' => 'Ukraine',
      'UG' => 'Uganda',
      'UM' => 'United States Minor Outlying Islands',
      'US' => 'United States',
      'UY' => 'Uruguay',
      'UZ' => 'Uzbekistan',
      'VA' => 'Vatican City',
      'VC' => 'Saint Vincent and the Grenadines',
      'VE' => 'Venezuela',
      'VG' => 'Virgin Islands (British)',
      'VI' => 'Virgin Islands (US)',
      'VN' => 'Vietnam',
      'VU' => 'Vanuatu',
      'WF' => 'Wallis and Futuna Islands',
      'WS' => 'Samoa',
      'YE' => 'Yemen',
      'YT' => 'Mayotte (France)',
      'ZA' => 'South Africa',
      'ZM' => 'Zambia',
      'ZW' => 'Zimbabwe',
    ];

    if (isset($countries[$country_code])) {
      return $countries[$country_code];
    }
    else {
      return FALSE;
    }
  }

}
