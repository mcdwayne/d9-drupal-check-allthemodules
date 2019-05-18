<?php

namespace Drupal\hidden_tab\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\hidden_tab\Entity\HiddenTabPlacementInterface;

/**
 * Helper method to work with hidden tab entities.
 *
 * @see \Drupal\hidden_tab\Entity\HiddenTabPageInterface;
 */
interface HiddenTabEntityHelperInterface {

  /**
   * Check a Uri and see if it contains a page and find that page's entity.
   *
   * @param string $tab_uri
   *   Uri to check.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabPageInterface|null
   *   Found page, if any.
   */
  public function pageByTabUri($tab_uri): ?HiddenTabPageInterface;

  /**
   * Check a Uri and see if it contains a page and find that page's entity.
   *
   * @param string $secret_uri
   *   Uri to check.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabPageInterface|null
   *   Found page, if any.
   */
  public function pageBySecretUri($secret_uri): ?HiddenTabPageInterface;

  /**
   * Load a page by it's id.
   *
   * @param string $id
   *   Entity id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabPageInterface|null
   *   Loaded entity, if any.
   */
  public function page(string $id): ?HiddenTabPageInterface;

  /**
   * Load all pages.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabPageInterface[]
   *   All entities loaded.
   */
  public function pages(): array;

  /**
   * Id to label array of all pages suitable for select element options.
   *
   * @return array
   *   Id to label array of all pages suitable for select element options.
   */
  public function allPagesForSelectElement(): array;

  // ==========================================================================

  /**
   * Given a page, load all it's placements.
   *
   * @param string $page_id
   *   The page in question.
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabPlacementInterface[]
   *   All the placements in the page.
   */
  public function placementsOfPage(string $page_id): array;

  /**
   * Load a placement by it's id.
   *
   * @param string $id
   *   Entity id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabPlacementInterface|null
   *   Loaded entity, if any.
   */
  public function placement(string $id): ?HiddenTabPlacementInterface;

  // ==========================================================================

  /**
   * Load mailers suitable for an entity (but not having target user).
   *
   * @param \Drupal\hidden_tab\Entity\HiddenTabPageInterface $page
   *   The page to look for it's mailers.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity in question.
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabMailerInterface[]
   *   Found mailers.
   */
  public function entityMailers(HiddenTabPageInterface $page, EntityInterface $entity): array;


}
