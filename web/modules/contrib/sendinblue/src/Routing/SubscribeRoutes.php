<?php

namespace Drupal\sendinblue\Routing;

use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes.
 */
class SubscribeRoutes {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = [];

    $entity_manager = \Drupal::entityTypeManager();
    $signups = $entity_manager->getStorage(SENDINBLUE_SIGNUP_ENTITY)
      ->loadMultiple();

    foreach ($signups as $signup) {
      if (intval($signup->mode->value) == SENDINBLUE_SIGNUP_PAGE || intval($signup->mode->value) == SENDINBLUE_SIGNUP_BOTH) {
        $settings = (!$signup->settings->first()) ? [] : $signup->settings->first()
          ->getValue();

        $routes['sendinblue.subscribe.' . $signup->name->value] = new Route('/' . $settings['path'],
          [
            '_form' => '\Drupal\sendinblue\Form\SubscribeForm',
            '_title' => $signup->title->value,
            'mcsId' => $signup->mcsId->value,
          ],
          [
            '_permission' => 'access content',
          ]
        );
      }
    }

    return $routes;
  }

}
