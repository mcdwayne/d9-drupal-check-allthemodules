<?php

namespace Drupal\simple_twilio;

use Twilio\Rest\Client;
use Twilio\Exceptions\RestException;

define('TWILIO_USER_PENDING', 1);
define('TWILIO_USER_CONFIRMED', 2);

/**
 * @file
 * Contains \Drupal\simple_twilio\Utility.
 */

/**
 * Contains static helper functions for Simple Twilio module.
 */
class Utility {

  /**
   * Returns an array of E.164 international country calling codes.
   *
   * @return array
   *   Associative array of country calling codes and country names.
   */
  public function simpleTwilioCountryCodes() {
    $codes = [
      1 => "USA / Canada / Dominican Rep. / Puerto Rico (1)",
      93 => "Afghanistan (93)",
      355 => "Albania (355)",
      213 => "Algeria (213)",
      376 => "Andorra (376)",
      244 => "Angola (244)",
      1264 => "Anguilla (1264)",
      1268 => "Antigua & Barbuda (1268)",
      54 => "Argentina (54)",
      374 => "Armenia (374)",
      297 => "Aruba (297)",
      61 => "Australia (61)",
      43 => "Austria (43)",
      994 => "Azerbaijan (994)",
      1242 => "Bahamas (1242)",
      973 => "Bahrain (973)",
      880 => "Bangladesh (880)",
      1246 => "Barbados (1246)",
      375 => "Belarus (375)",
      32 => "Belgium (32)",
      501 => "Belize (501)",
      229 => "Benin (229)",
      1441 => "Bermuda (1441)",
      975 => "Bhutan (975)",
      591 => "Bolivia (591)",
      387 => "Bosnia-Herzegovina (387)",
      267 => "Botswana (267)",
      55 => "Brazil (55)",
      1284 => "British Virgin Islands (1284)",
      673 => "Brunei (673)",
      359 => "Bulgaria (359)",
      226 => "Burkina Faso (226)",
      257 => "Burundi (257)",
      855 => "Cambodia (855)",
      237 => "Cameroon (237)",
      34 => "Canary Islands (34)",
      238 => "Cape Verde (238)",
      1345 => "Cayman Islands (1345)",
      236 => "Central African Republic (236)",
      235 => "Chad (235)",
      56 => "Chile (56)",
      86 => "China (86)",
      57 => "Colombia (57)",
      269 => "Comoros (269)",
      242 => "Congo (242)",
      243 => "Democratic Republic Congo (243)",
      682 => "Cook Islands (682)",
      385 => "Croatia (385)",
      53 => "Cuba (53)",
      357 => "Cyprus (357)",
      420 => "Czech Republic (420)",
      45 => "Denmark (45)",
      253 => "Djibouti (253)",
      1767 => "Dominica (1767)",
      670 => "East Timor (670)",
      593 => "Ecuador (593)",
      20 => "Egypt (20)",
      503 => "El Salvador (503)",
      240 => "Equatorial Guinea (240)",
      372 => "Estonia (372)",
      251 => "Ethiopia (251)",
      500 => "Falkland Islands (500)",
      298 => "Faroe Islands (298)",
      679 => "Fiji (679)",
      358 => "Finland (358)",
      33 => "France (33)",
      594 => "French Guiana (594)",
      689 => "French Polynesia (689)",
      241 => "Gabon (241)",
      220 => "Gambia (220)",
      995 => "Georgia (995)",
      49 => "Germany (49)",
      233 => "Ghana (233)",
      350 => "Gibraltar (350)",
      881 => "Global Mobile Satellite (881)",
      30 => "Greece (30)",
      299 => "Greenland (299)",
      1473 => "Grenada (1473)",
      590 => "Guadeloupe (590)",
      1671 => "Guam (1671)",
      502 => "Guatemala (502)",
      224 => "Guinea (224)",
      592 => "Guyana (592)",
      509 => "Haiti (509)",
      504 => "Honduras (504)",
      852 => "HongKong (852)",
      36 => "Hungary (36)",
      354 => "Iceland (354)",
      91 => "India (91)",
      62 => "Indonesia (62)",
      98 => "Iran (98)",
      964 => "Iraq (964)",
      353 => "Ireland (353)",
      972 => "Israel (972)",
      39 => "Italy / Vatican City State (39)",
      225 => "Ivory Coast (225)",
      1876 => "Jamaica (1876)",
      81 => "Japan (81)",
      962 => "Jordan (962)",
      254 => "Kenya (254)",
      82 => "Korea (South) (82)",
      965 => "Kuwait (965)",
      996 => "Kyrgyzstan (996)",
      856 => "Lao (856)",
      371 => "Latvia (371)",
      961 => "Lebanon (961)",
      266 => "Lesotho (266)",
      231 => "Liberia (231)",
      218 => "Libya (218)",
      423 => "Liechtenstein (423)",
      370 => "Lithuania (370)",
      352 => "Luxembourg (352)",
      853 => "Macau (853)",
      389 => "Macedonia (389)",
      261 => "Madagascar (261)",
      265 => "Malawi (265)",
      60 => "Malaysia (60)",
      960 => "Maldives (960)",
      223 => "Mali (223)",
      356 => "Malta (356)",
      596 => "Martinique (596)",
      222 => "Mauritania (222)",
      230 => "Mauritius (230)",
      269 => "Mayotte Island (Comoros) (269)",
      52 => "Mexico (52)",
      373 => "Moldova (373)",
      377 => "Monaco (Kosovo) (377)",
      976 => "Mongolia (976)",
      382 => "Montenegro (382)",
      1664 => "Montserrat (1664)",
      212 => "Morocco (212)",
      258 => "Mozambique (258)",
      95 => "Myanmar (95)",
      264 => "Namibia (264)",
      977 => "Nepal (977)",
      31 => "Netherlands (31)",
      599 => "Netherlands Antilles (599)",
      687 => "New Caledonia (687)",
      64 => "New Zealand (64)",
      505 => "Nicaragua (505)",
      227 => "Niger (227)",
      234 => "Nigeria (234)",
      47 => "Norway (47)",
      968 => "Oman (968)",
      92 => "Pakistan (92)",
      970 => "Palestine (+970)",
      9725 => "Palestine (+9725)",
      507 => "Panama (507)",
      675 => "Papua New Guinea (675)",
      595 => "Paraguay (595)",
      51 => "Peru (51)",
      63 => "Philippines (63)",
      48 => "Poland (48)",
      351 => "Portugal (351)",
      974 => "Qatar (974)",
      262 => "Reunion (262)",
      40 => "Romania (40)",
      7 => "Russia / Kazakhstan (7)",
      250 => "Rwanda (250)",
      1670 => "Saipan (1670)",
      1684 => "Samoa (American) (1684)",
      685 => "Samoa (Western) (685)",
      378 => "San Marino (378)",
      882 => "Satellite-Thuraya (882)",
      966 => "Saudi Arabia (966)",
      221 => "Senegal (221)",
      381 => "Serbia (381)",
      248 => "Seychelles (248)",
      232 => "Sierra Leone (232)",
      65 => "Singapore (65)",
      421 => "Slovakia (421)",
      386 => "Slovenia (386)",
      252 => "Somalia (252)",
      27 => "South Africa (27)",
      34 => "Spain (34)",
      94 => "Sri Lanka (94)",
      1869 => "St. Kitts And Nevis (1869)",
      1758 => "St. Lucia (1758)",
      1784 => "St. Vincent (1784)",
      249 => "Sudan (249)",
      597 => "Suriname (597)",
      268 => "Swaziland (268)",
      46 => "Sweden (46)",
      41 => "Switzerland (41)",
      963 => "Syria (963)",
      886 => "Taiwan (886)",
      992 => "Tajikistan (992)",
      255 => "Tanzania (255)",
      66 => "Thailand (66)",
      228 => "Togo (228)",
      676 => "Tonga Islands (676)",
      1868 => "Trinidad and Tobago (1868)",
      216 => "Tunisia (216)",
      90 => "Turkey (90)",
      993 => "Turkmenistan (993)",
      1649 => "Turks and Caicos Islands (1649)",
      256 => "Uganda (256)",
      44 => "UK / Isle of Man / Jersey / Guernsey (44)",
      380 => "Ukraine (380)",
      971 => "United Arab Emirates (971)",
      598 => "Uruguay (598)",
      998 => "Uzbekistan (998)",
      678 => "Vanuatu (678)",
      58 => "Venezuela (58)",
      84 => "Vietnam (84)",
      967 => "Yemen (967)",
      260 => "Zambia (260)",
      255 => "Zanzibar (255)",
      263 => "Zimbabwe (263)",
    ];
    return $codes;
  }

  /**
   * Checks if a given phone number already exists in database.
   *
   * @param string $number
   *   The sender's mobile number.
   * @param string $country
   *   Country code for the number.
   *
   * @result boolean
   *   TRUE if it exists, FALSE otherwise
   */
  public function simpleTwilioVerifyDuplicateNumber($number, $country) {
    $query = \Drupal::database()->select('simple_twilio_user', 't');
    $query->fields('t', ['number']);
    $query->condition('t.number', $number);
    $query->condition('t.country', $country);
    $result = $query->execute()->fetchAssoc();
    if ($result['number'] == $number) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Send SMS to the Users.
   *
   * @param int $recipient
   *   Recipient number.
   * @param string $message
   *   Message to the user.
   */
  public function simpleTwilioSendMessage($recipient, $message) {
    $simple_twilio_settings = \Drupal::config('simple_twilio.settings');
    $account_sid = $simple_twilio_settings->get('account_sid');
    $auth_token = $simple_twilio_settings->get('auth_token');
    $from = $simple_twilio_settings->get('from');

    $client = new Client($account_sid, $auth_token);
    $options = [
      'from' => $from,
      'body' => $message,
    ];

    if (substr($recipient, 0, 1) !== '+') {
      $recipientNumbers[] = '+' . $recipient;
    }
    else {
      $recipientNumbers[] = $recipient;
    }

    try {
      $message = $client->messages->create($recipientNumbers, $options);
      drupal_set_message(t('Message sent Successfully.'));
    }
    catch (RestException $e) {
      $code = $e->getCode();
      $message = $e->getMessage();

      if (in_array($code, [21211, 21612, 21610, 21614])) {
        // 21211: Recipient is invalid. (Test recipient: +15005550001)
        // 21612: Cannot route to this recipient. (Test recipient: +15005550002)
        // 21610: Recipient is blacklisted. (Test recipient: +15005550004)
        // 21614: Recipient is incapable of receiving SMS.
        // INVALID RECEIPT.
        \Drupal::logger('simple_twilio')->error('Invalid Recipient. Insure your recipient details at configuration.');
      }
      elseif ($code == 21408) {
        // 21408: Account doesn't have the international permission.
        // ACCOUNT ERROR.
        \Drupal::logger('simple_twilio')->error('Account Error. The doesn\'t have permission for xent SMS.');
      }
      else {
        // UNKNOWN ERROR.
        \Drupal::logger('simple_twilio')->error('Unable to sent SMS, Please verify your API dedtails.');
      }
      drupal_set_message(t('Unable to sent message.'), 'error');
    }
  }

  /**
   * Send confirmation message.
   *
   * @param object $account
   *   The user object of the account to message.
   * @param string $number
   *   The phone number to send the message.
   * @param string $country
   *   The country code for the number.
   *
   * @todo Please document this function.
   * @see http://drupal.org/node/1354
   */
  public function simpleTwiliUserSendConfirmation($account, $number, $country) {
    $code = rand(1000, 9999);

    \Drupal::database()->merge('simple_twilio_user')
      ->key(['uid' => $account->id()])
      ->insertFields([
        'uid' => $account->id(),
        'number' => $number,
        'country' => $country,
        'status' => TWILIO_USER_PENDING,
        'code' => $code,
      ])
      ->updateFields([
        'number' => $number,
        'country' => $country,
        'status' => TWILIO_USER_PENDING,
        'code' => $code,
      ])->execute();

    $message = "Confirmation code: $code";
    Utility::simpleTwilioSendMessage(($country . $number), $message);
    return TRUE;
  }

  /**
   * Implements hook_user_delete().
   */
  public function simpleTwilioUserDelete($account) {
    \Drupal::database()->delete('simple_twilio_user')
      ->condition('uid', $account->id())
      ->execute();
  }

}
