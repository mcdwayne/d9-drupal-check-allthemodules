<?php

/**
 * @file
 * Builds placeholder replacement tokens for values specific to Contest nodes.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\contest\ContestStorage;
use Drupal\contest\ContestUser;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Implements hook_token_info().
 */
function contest_token_info() {
  $contest['country'] = [
    'name'        => t("Contest Country"),
    'description' => t("The country where all contests are held."),
  ];
  $contest['date-end'] = [
    'name'        => t("Contest End"),
    'description' => t("This contest's end date."),
  ];
  $contest['date-notify'] = [
    'name'        => t("Contest Notification Date"),
    'description' => t("This contest's notification date."),
  ];
  $contest['date-start'] = [
    'name'        => t("Contest Start"),
    'description' => t("This contest's start date."),
  ];
  $contest['host-address'] = [
    'name'        => t("Contest Host Address"),
    'description' => t("The address of the contest host."),
  ];
  $contest['host-business'] = [
    'name'        => t("Contest Host Business"),
    'description' => t("The host's business name."),
  ];
  $contest['host-city'] = [
    'name'        => t("Contest Host City"),
    'description' => t("The host's city."),
  ];
  $contest['host-full-name'] = [
    'name'        => t("Contest Host Name"),
    'description' => t("The host's full name."),
  ];
  $contest['host-state'] = [
    'name'        => t("Contest Host State"),
    'description' => t("The state of the contest host."),
  ];
  $contest['host-title'] = [
    'name'        => t("Contest Host Title"),
    'description' => t("The contest host title."),
  ];
  $contest['host-zip'] = [
    'name'        => t("Contest Host Zipcode"),
    'description' => t("The zipcode of the contest host."),
  ];
  $contest['places'] = [
    'name'        => t("Contest Places"),
    'description' => t("The number of Places in this contest."),
  ];
  $contest['sponsor-title'] = [
    'name'        => t("Contest Sponsor Title"),
    'description' => t("This contest sponsor's title."),
  ];
  $contest['sponsor-url'] = [
    'name'        => t("Contest Sponsor URL"),
    'description' => t("This contest sponsor's URL."),
  ];
  $contest['timezone'] = [
    'name'        => t("Contest Time Zone"),
    'description' => t("The time zone where the contest is being held."),
  ];
  $info = [
    'tokens' => ['contest' => $contest],
    'types'  => [
      'contest' => [
        'name'        => t('Contest'),
        'description' => t('Tokens related to a contest.'),
        'needs-data'  => 'contest',
      ],
    ],
  ];
  return $info;
}

/**
 * Implements hook_tokens().
 */
function contest_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  if ($type != 'contest') {
    return [];
  }
  $cfg = (object) \Drupal::config('contest.config')->get();
  $cfghost = (object) \Drupal::config('contest.host')->get();
  $cid = \Drupal::request()->get('contest')->id();
  $dateFrmt = Drupal::service('date.formatter');
  $x = [];

  $contest = ContestStorage::getTokenData($cid);

  $host = new ContestUser($cfghost->uid);
  $sponsor = new ContestUser($contest->sponsor_uid, ['url' => $contest->sponsor_url]);

  foreach ($tokens as $key => $token) {
    switch ($key) {
      case 'country':
        $x[$token] = \Drupal::config('system.date')->get('country.default');
        break;

      case 'date-end':
        $x[$token] = $dateFrmt->format($contest->end, 'contest_date');
        break;

      case 'date-notify':
        $x[$token] = $dateFrmt->format($contest->end + (ContestStorage::DAY * $cfg->notify), 'contest_date');
        break;

      case 'date-start':
        $x[$token] = $dateFrmt->format($contest->start, 'contest_date');
        break;

      case 'host-address':
        $x[$token] = SafeMarkup::checkPlain($host->address);
        break;

      case 'host-business':
        $x[$token] = SafeMarkup::checkPlain($host->business);
        break;

      case 'host-city':
        $x[$token] = SafeMarkup::checkPlain($host->city);
        break;

      case 'host-full-name':
        $x[$token] = SafeMarkup::checkPlain($host->full_name);
        break;

      case 'host-state':
        $x[$token] = SafeMarkup::checkPlain($host->state);
        break;

      case 'host-title':
        $x[$token] = $cfghost->title;
        break;

      case 'host-zip':
        $x[$token] = SafeMarkup::checkPlain($host->zip);
        break;

      case 'places':
        $x[$token] = SafeMarkup::checkPlain($contest->places);
        break;

      case 'sponsor-title':
        $x[$token] = _contest_token_sponsor_title($sponsor);
        break;

      case 'sponsor-url':
        $x[$token] = SafeMarkup::checkPlain($sponsor->url);
        break;

      case 'timezone':
        $x[$token] = date('T');
        break;
    }
  }
  return $x;
}

/**
 * Build a title for the contest sponsor.
 *
 * @param Drupal\contest\ContestUser $sponsor
 *   The sponsor's ContestUser object.
 *
 * @return string
 *   A title for the contest sponsor.
 */
function _contest_token_sponsor_title(ContestUser $sponsor) {
  if ($sponsor->business) {
    $title = $sponsor->business;
  }
  elseif ($sponsor->fullName) {
    $title = $sponsor->fullName;
  }
  elseif ($sponsor->url) {
    $title = preg_replace('/^https?:\/\/([^\/]*)/', "$1", $sponsor->url);
  }
  else {
    $title = t('Contest Sponsor');
  }
  return $title;
}
