<?php

/**
 * @file
 * Contains \Drupal\tarpit\Event\InsideEvent.
 */
namespace Drupal\tarpit\Event;

use Symfony\Component\EventDispatcher\GenericEvent;
use Drupal\Core\Link;
use Drupal\Core\Url;

class InsideEvent extends GenericEvent {
  const EVENT_NAME = 'tarpit.inside';

  public static function generateRandomTextAndLinks($path = '') {
    if (empty($path)) {
      $path = trim(\Drupal::request()->getPathInfo(), '/');
    } else {
      $path = trim($path, '/');
    }

    $file = \Drupal::config('tarpit.config')->get('wordlist');
    $links = \Drupal::config('tarpit.config')->get('links');
    $words = \Drupal::config('tarpit.config')->get('size');

    $f_contents = file($file);

    $text = [];
    for($i=0; $i < $links; $i++) {
      $random = trim($f_contents[array_rand($f_contents)]);

      if (Url::fromUri('internal:/' . $path . '/' . $random)->isRouted()) {
        $url = Url::fromUri('internal:/' . $path . '/' . $random);
      } else {
        $path_exploded = array_reverse(explode('/', $path));
        $path_exploded[0] = $random;
        $path = implode('/', array_reverse($path_exploded));
        $url = Url::fromUri('internal:/' . $path);
      }

      $text[] = Link::fromTextAndUrl($random, $url)->toRenderable();
    }

    for($i=0; $i < $words; $i++) {
      $text[] = array(
        '#markup' => trim($f_contents[array_rand($f_contents)])
      );
    }

    shuffle($text);

    $html = [];
    foreach($text as $data) {
      $html[] = \Drupal::service('renderer')->render($data)->__toString();
    }

    return implode(' ', $html);
  }

}
