<?php

/**
 * @group pocket_api
 * @{
 */

/**
 * Controller function that redirects the user to Pocket.
 *
 * @return \Symfony\Component\HttpFoundation\RedirectResponse
 */
function _pocket_example_authorize(): \Symfony\Component\HttpFoundation\RedirectResponse {
  /** @var \Drupal\pocket\PocketClientFactoryInterface $factory */
  $factory = \Drupal::service('pocket.client');
  $user = \Drupal::currentUser()->id();
  return new \Drupal\Core\Routing\TrustedRedirectResponse(
    $factory->getAuthClient()->authorize(
      '_pocket_example_callback',
      ['user' => $user]
    )
  );
}

/**
 * Callback function that stores the access token and returns to the user page.
 *
 * @param \Drupal\pocket\AccessToken $access
 *
 * @return \Drupal\Core\Url
 * @throws \Drupal\Core\Entity\EntityMalformedException
 */
function _pocket_example_callback(\Drupal\pocket\AccessToken $access): \Drupal\Core\Url {
  $id = $access->getState()['user'];
  /** @var \Drupal\user\Entity\User $user */
  $user = \Drupal\user\Entity\User::load($id)
    ->set('pocket_username', $access->getUsername())
    ->set('pocket_access', $access->getToken())
    ->save();
  drupal_set_message('You have successfully connected your "%user" Pocket account.', [
    '%user' => $user->pocket_username
  ]);
  return $user->toUrl();
}

/**
 * Connect to the Pocket service as a specific user, and push a page.
 *
 * @param \Drupal\node\NodeInterface $node
 * @param \Drupal\user\UserInterface $user
 *
 * @throws \Drupal\Core\Entity\EntityMalformedException
 */
function _pocket_example_add_node(\Drupal\node\NodeInterface $node, \Drupal\user\UserInterface $user) {
  /** @var \Drupal\pocket\PocketClientFactoryInterface $factory */
  $factory = \Drupal::service('pocket.client');
  $factory->getUserClient($user->pocket_access)
    ->add($node->toUrl());
}

/**
 * @}
 */
