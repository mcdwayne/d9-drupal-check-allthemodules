<?php

namespace Drupal\social_simple;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface SocialSimpleGeneratorInterface.
 *
 * @package Drupal\social_simple
 */
interface SocialSimpleGeneratorInterface {

  /**
   * Return an array of social networks supported.
   *
   * @return array $networks
   *   An array of Label social networks supported kyed by their id.
   */
  public function getNetworks();

  /**
   * Build the render array of social share links.
   *
   * @param array $networks
   *   An array of social network name keyed with network id.
   * @param string $title
   *   The title to use for the share links.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity if provided.
   * @param array $options
   *   Additional options to pass as a query for the url built.
   *   The array must be keyed by network_id.
   *   Example.
   *   $options = [
   *     'twitter' => [
   *       'hastags' => 'hashtag1, hashtag2',
   *     ],
   *   ];.
   *
   * @return array $links
   *   An array of social share links.
   */
  public function buildSocialLinks(array $networks, $title, EntityInterface $entity = NULL, array $options = []);

  /**
   * Build the social share links.
   *
   * @param array $networks
   *   An array of social network name keyed with network id.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity if provided.
   * @param array $options
   *   Additional options to pass as a query for the url built.
   *   The array must be keyed by network_id.
   *   Example.
   *   $options = [
   *     'twitter' => [
   *       'hastags' => 'hashtag1, hashtag2',
   *     ],
   *   ];.
   *
   * @return array $links
   *   An array of social share links.
   */
  public function generateSocialLinks(array $networks, EntityInterface $entity = NULL, array $options = []);

  /**
   * Get the title to share.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity if provided.
   *
   * @return string $title
   *   The entity title or page title to share.
   */
  public function getTitle(EntityInterface $entity = NULL);

  /**
   * Get the share url.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity if provided.
   *
   * @return string $url
   *   The url to share.
   */
  public function getShareUrl(EntityInterface $entity = NULL);

}
