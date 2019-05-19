<?php

namespace Drupal\views_restricted;

use Drupal\Component\Utility\Html;
use Drupal\Core\GeneratedLink;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

class Urlifyer {

  public static function urlify(array &$build) {
    array_walk_recursive($build, function (&$element) {
      if ($element instanceof GeneratedLink) {
        $link = $element->getGeneratedLink();
        $dom = Html::load($link);
        $aTags = $dom->getElementsByTagName('a');
        /** @var \DOMElement $element */
        $element = $aTags[0];
        $href = $element->getAttribute('href');
        $request = Request::create($href);
        $url = Url::createFromRequest($request);
        $link = Link::fromTextAndUrl($element->textContent, $url);
        $renderArray = $link->toRenderable();
        /** @var  \DOMAttr $attribute */
        foreach ($element->attributes as $name => $attribute) {
          if ($name !== 'href') {
            $renderArray['#attributes'][$name] = $attribute->value;
          }
        }
        $element = $renderArray;
      }
      elseif ($element instanceof GeneratedUrl) {
        $href = $element->getGeneratedUrl();
        $request = Request::create($href);
        $url = Url::createFromRequest($request);
        $element = $url;
      }
    });

  }
}
