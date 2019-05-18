<?php

/**
 * @file
 * Builds placeholder replacement tokens micr site data.
 *
 * This file handles tokens for micro site tokens.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\Core\Url;

/**
 * Implements hook_token_info().
 */
function micro_site_token_info() {
  $types['micro_site'] = [
    'name' => t("Micro Site information"),
    'description' => t("Tokens for micro site settings and other global information."),
  ];

  // Site-wide global tokens.
  $micro_site['name'] = [
    'name' => t("Name"),
    'description' => t("The name of the micro site."),
  ];
  $micro_site['slogan'] = [
    'name' => t("Slogan"),
    'description' => t("The slogan of the micro site."),
  ];
  $micro_site['mail'] = [
    'name' => t("Email"),
    'description' => t("The email address for the micro site."),
  ];
  $micro_site['url'] = [
    'name' => t("URL"),
    'description' => t("The URL of the micro site's front page."),
  ];
  $micro_site['url-brief'] = [
    'name' => t("URL (brief)"),
    'description' => t("The URL of the micro site's front page without the protocol."),
  ];
  $micro_site['login-url'] = [
    'name' => t("Login page"),
    'description' => t("The URL of the micro site's login page."),
  ];


  return [
    'types' => $types,
    'tokens' => [
      'micro_site' => $micro_site,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function micro_site_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }
  $replacements = [];

  /** @var \Drupal\micro_site\SiteNegotiatorInterface $negotiator */
  $negotiator = \Drupal::service('micro_site.negotiator');
  /** @var \Drupal\micro_site\Entity\SiteInterface $site */
  $site = $negotiator->getActiveSite();

  if ($type == 'micro_site' && $site instanceof SiteInterface) {
    $bubbleable_metadata->addCacheableDependency($site);
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'name':
          $site_name = $site->getName();
          $replacements[$original] = $site_name;
          break;

        case 'slogan':
          $slogan = $site->getSlogan();
//          $build = [
//            '#markup' => $slogan,
//          ];
//          // @todo Fix in https://www.drupal.org/node/2577827
//          $replacements[$original] = \Drupal::service('renderer')->renderPlain($build);
          $replacements[$original] = $slogan;
          break;

        case 'mail':
          $replacements[$original] = $site->getEmail();
          break;

        case 'url':
          $replacements[$original] = $site->getSitePath();
          break;

        case 'url-brief':
          /** @var \Drupal\Core\GeneratedUrl $result */
          $replacements[$original] = $site->getSiteUrl();
          break;

        case 'login-url':
          $url = Url::fromRoute('user.page', [], [])->toString();
          $replacements[$original] = $site->getSitePath() . $url;
          break;
      }
    }
  }

  return $replacements;
}

/**
 * Implements hook_tokens_alter().
 *
 * Override the global site-wide tokens if we are on a micro site.
 */
function micro_site_tokens_alter(array &$replacements, array $context, BubbleableMetadata $bubbleable_metadata) {

  if ($context['type'] == 'site') {

    if (isset($context['data']['micro_site'])) {
      $site = $context['data']['micro_site'];
    }
    else {
      /** @var \Drupal\micro_site\SiteNegotiatorInterface $negotiator */
      $negotiator = \Drupal::service('micro_site.negotiator');
      /** @var \Drupal\micro_site\Entity\SiteInterface $site */
      $site = $negotiator->getActiveSite();
    }

    if ($site instanceof SiteInterface) {
      $bubbleable_metadata->addCacheableDependency($site);

      if (isset($context['tokens']['name'])) {
        $replacements[$context['tokens']['name']] = $site->getName();
      }

      if (isset($context['tokens']['slogan'])) {
        $replacements[$context['tokens']['slogan']] = $site->getSlogan();
      }

      if (isset($context['tokens']['mail'])) {
        $replacements[$context['tokens']['mail']] = $site->getEmail();
      }

      if (isset($context['tokens']['url'])) {
        $replacements[$context['tokens']['url']] = $site->getSitePath();
      }

      if (isset($context['tokens']['url-brief'])) {
        $replacements[$context['tokens']['url-brief']] = $site->getSiteUrl();
      }

      if (isset($context['tokens']['url-login'])) {
        $url = Url::fromRoute('user.page', [], [])->toString();
        $replacements[$context['tokens']['url-login']] = $site->getSitePath() . $url;
      }
    }

  }
}


