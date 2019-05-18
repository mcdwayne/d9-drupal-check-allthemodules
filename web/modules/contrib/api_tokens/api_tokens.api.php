<?php

/**
 * @file
 * Hooks provided by the API Tokens module.
 */

/**
 * @defgroup api_tokens API Tokens
 * @{
 * Information about the classes and interfaces that make up the API Tokens.
 *
 * To define an API token in a module you need to:
 * - Define an ApiToken plugin by creating a new class that implements the
 *   \Drupal\api_tokens\ApiTokenPluginInterface, in namespace Plugin\ApiToken
 *   under your module namespace.
 * - Usually you will want to extend the \Drupal\api_tokens\ApiTokenBase class,
 *   which provides a common processing functionality for API tokens.
 * - ApiToken plugins use the annotations defined by
 *   \Drupal\api_tokens\Annotation\ApiToken.
 *
 * There are also several API token related hooks, which allow you to affect the
 * plugin definitions and result of the build method:
 * - hook_api_tokens_info_alter()
 * - hook_api_token_build_alter()
 *
 * See api_tokens_example module for example implementations.
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the API token plugin definitions.
 *
 * @param array &$info
 *   An array of all API token plugin definitions (empty array if no definitions
 *   were found). Keys are plugin IDs.
 *
 * @ingroup api_tokens
 */
function hook_api_tokens_info_alter(array &$info) {
  // Alters the description of the "some" API token.
  if (isset($info['some'])) {
    $info['some']['description'] = t('Some API token description.');
  }
}

/**
 * Alter the result of \Drupal\api_tokens\ApiTokenPluginInterface::build()
 * method.
 *
 * @param array &$build
 *   A renderable array of data, as returned from the build method of the plugin
 *   that defined the API token.
 * @param \Drupal\api_tokens\ApiTokenPluginInterface $plugin
 *   The API token plugin instance.
 *
 * @ingroup api_tokens
 */
function hook_api_token_build_alter(array &$build, \Drupal\api_tokens\ApiTokenPluginInterface $plugin) {
  // Wraps the "some" API token output in a div with "some-class" CSS class.
  if ('some' === $plugin->getId()) {
    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'some-class',
      ],
      $build,
    ];
  }
}

/**
 * @} End of "addtogroup hooks".
 */
