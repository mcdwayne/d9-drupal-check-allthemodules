<?php

namespace Drupal\email_token\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;
use Drupal\Core\Url;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Drupal\Core\Routing;
use Drupal\node\Entity\Node;
use Drupal\Core\Cache\Cache;

/**
 *  @Filter(
 *    id = "email_token",
 *    title = @Translation("Email Token Filter"),
 *    description = @Translation("Filter to replace email tokens"),
 *    type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *  )
 */

class EmailToken extends FilterBase {
  public function process($text, $langcode) {
    $url_options = array('absolute' => TRUE);
      if(isset($url_options['langcode'])) {
        $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
        $langcode = $options['langcode'];
      }
      else {
        $langcode = NULL;
      }
    $request = \Drupal::request();
    $route_match = \Drupal::routeMatch();
    $title = \Drupal::service('title_resolver')->getTitle($request, $route_match->getRouteObject());
    $node_url = Url::fromRoute('<current>', [], $url_options)->toString();
    $body_line = 'We appreciate your spreading the word.';
    $mid_mail_link = t('<div class = "mid-email-token"><a href=":url">mail me</a></div>', array(':url' => 'mailto:?subject='.$title . '&body=' . $body_line .' '. $node_url));
    $token_replacement = str_replace(array('[etf:gin-title]','[etf:gin-url]','[etf:gin-email]'), array($title,$node_url,$mid_mail_link), $text);
    return new FilterProcessResult($token_replacement);
    }
}
