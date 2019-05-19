<?php

namespace Drupal\smart_glossary\Routing;
use Drupal\smart_glossary\Entity\SmartGlossaryConfig;
use Symfony\Component\Routing\Route;

class SmartGlossaryRoutes {
  public function routes() {
    $routes = [];
    $smart_glossary_configs = SmartGlossaryConfig::loadMultiple();

    /** @var SmartGlossaryConfig $smart_glossary_config */
    foreach ($smart_glossary_configs as $smart_glossary_config) {
      // Glossary Start page.
      $routes['smart_glossary.display.' . $smart_glossary_config->id()] = new Route(
        // Path definition
        $smart_glossary_config->getBasePath(),
        // Route defaults
        [
          '_controller' => '\Drupal\smart_glossary\Controller\SmartGlossaryController::showGlossary',
          '_title' => 'Smart Glossary ' . $smart_glossary_config->getTitle(),
          'smart_glossary_config' => $smart_glossary_config->id(),
        ],
        // Route requirements
        [
          '_permission' => 'access content',
        ],
        // Route options
        [
          'parameters' => [
            'smart_glossary_config' => [
              'type' =>  'entity:smart_glossary'
            ]
          ]
        ]
      );

      // Glossary Start page with selected language.
      $routes['smart_glossary.display_lang.' . $smart_glossary_config->id()] = new Route(
      // Path definition
        $smart_glossary_config->getBasePath() . '/{glossary_language}',
        // Route defaults
        [
          '_controller' => '\Drupal\smart_glossary\Controller\SmartGlossaryController::showGlossary',
          '_title' => 'Smart Glossary ' . $smart_glossary_config->getTitle(),
          'smart_glossary_config' => $smart_glossary_config->id(),
        ],
        // Route requirements
        [
          '_permission' => 'access content',
        ],
        // Route options
        [
          'parameters' => [
            'smart_glossary_config' => [
              'type' =>  'entity:smart_glossary'
            ]
          ]
        ]
      );

      // Glossary Concept list by character.
      $routes['smart_glossary.list.' . $smart_glossary_config->id()] = new Route(
      // Path definition
        $smart_glossary_config->getBasePath() . '/{glossary_language}/list/{character}',
        // Route defaults
        [
          '_controller' => '\Drupal\smart_glossary\Controller\SmartGlossaryController::showConceptList',
          '_title' => 'Smart Glossary ' . $smart_glossary_config->getTitle(),
          'smart_glossary_config' => $smart_glossary_config->id(),
        ],
        // Route requirements
        [
          '_permission' => 'access content',
        ],
        // Route options
        [
          'parameters' => [
            'smart_glossary_config' => [
              'type' =>  'entity:smart_glossary'
            ]
          ]
        ]
      );

      // Glossary detail view.
      $routes['smart_glossary.detail.' . $smart_glossary_config->id()] = new Route(
      // Path definition
        $smart_glossary_config->getBasePath() . '/{glossary_language}/{concept_title}',
        // Route defaults
        [
          '_controller' => '\Drupal\smart_glossary\Controller\SmartGlossaryController::showGlossaryDetails',
          '_title' => 'Smart Glossary ' . $smart_glossary_config->getTitle(),
          'smart_glossary_config' => $smart_glossary_config->id(),
        ],
        // Route requirements
        [
          '_permission' => 'access content',
        ],
        // Route options
        [
          'parameters' => [
            'smart_glossary_config' => [
              'type' =>  'entity:smart_glossary'
            ]
          ]
        ]
      );
    }

    return $routes;
  }
}