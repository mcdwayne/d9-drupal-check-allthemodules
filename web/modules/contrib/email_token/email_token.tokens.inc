<?php

/**
 * @file
 * 
 * Builds placeholder replacement tokens system-wide data.
 *
 * This file handles tokens for the global 'etf' tokens.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Drupal\Core\Url;

/**
 * Implements hook_token_info().
 */

function email_token_token_info() 
{
    $types['etf'] = array(
    'name' => t("Site information"),
    'description' => t("Tokens for etf-wide settings and other global information."),
    );
    $etf['gin-title'] = array(
    'name' => t("Title"),
    'description' => t("The name of the etf."),
    );
    $etf['gin-url'] = array(
    'name' => t("Url"),
    'description' => t("The slogan of the etf."),
    );
    $etf['gin-email'] = array(
    'name' => t("Url"),
    'description' => t("The slogan of the etf."),
    );
    return array(
    'types' => $types,
    'tokens' => array(
      'etf' => $etf,
    ),
    );
}

/**
 * Implements hook_tokens().
 */
function email_token_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) 
{
    $token_service = \Drupal::token();
    $url_options = array('absolute' => true);
    if (isset($options['langcode'])) {
        $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
        $langcode = $options['langcode'];
    }
    else {
        $langcode = null;
    }
    $request = \Drupal::request();
    $route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT);
    $title = !empty(\Drupal::service('title_resolver')->getTitle($request, $route)) ? \Drupal::service('title_resolver')->getTitle($request, $route) : 'Home' ;
    $node_url = Url::fromRoute('<current>', [], $url_options)->toString();
    $body_line = 'We appreciate your spreading the word.';
    $replacements = array();
    if ($type == 'etf') {
        foreach ($tokens as $name => $original) {
            switch ($name) {
            case 'gin-title':
                $bubbleable_metadata->addCacheableDependency($title);
                $replacements[$original] = $title;
                break;
      
            case 'gin-url':
                $bubbleable_metadata->addCacheableDependency($node_url);
                $replacements[$original] = $node_url;
                break;
    
            case 'gin-email':
                $bubbleable_metadata->addCacheableDependency($title);
                $bubbleable_metadata->addCacheableDependency($node_url);
                $replacements[$original] = t('<div class = "gin-email-token"><a href=":url">mail me</a></div>', array(':url' => 'mailto:?subject=' . $title . '&body=' . $body_line . ' ' . $node_url));
                break;
            }
        }
    }
    return $replacements;
}
