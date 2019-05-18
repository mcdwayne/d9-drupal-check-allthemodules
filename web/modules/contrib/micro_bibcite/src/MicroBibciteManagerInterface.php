<?php

namespace Drupal\micro_bibcite;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\micro_site\SiteUsers;
use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\micro_taxonomy\MicroTaxonomyFields;
use Drupal\taxonomy\TermInterface;

/**
 * Helpful Manager for bibcite entities.
 */
interface MicroBibciteManagerInterface {

  /**
   * The site field of bibcite entities.
   */
  const BIBCITE_SITE = 'site_id';

  /**
   * Determines the current site id.
   *
   * @param array
   *   The current site Id or NULL if not site context found.
   */
  public static function getCurrentSiteId();

  /**
   * Get the main site from a node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The node to check.
   *
   * @return \Drupal\micro_site\Entity\SiteInterface|NULL
   *   The site entity or NULL.
   */
  public function getSite(EntityInterface $entity);

  /**
   * Get the users who can update terms.
   *
   * @param \Drupal\micro_site\Entity\SiteInterface $site
   * @param bool $return_entity
   *
   * @return mixed
   */
  public function getUsersCanUpdate(SiteInterface $site, $return_entity = FALSE);

  /**
   * Get the users who can update terms.
   *
   * @param \Drupal\micro_site\Entity\SiteInterface $site
   * @param bool $return_entity
   *
   * @return mixed
   */
  public function getUsersCanDelete(SiteInterface $site, $return_entity = FALSE);

  /**
   * Get the users who can create terms.
   *
   * @param \Drupal\micro_site\Entity\SiteInterface $site
   * @param bool $return_entity
   *
   * @return mixed
   */
  public function getUsersCanCreate(SiteInterface $site, $return_entity = FALSE);


  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Drupal\micro_site\Entity\SiteInterface|NULL $site
   * @param $operation
   * @return mixed
   */
  public function userCanAccessBibciteOverview(AccountInterface $account, SiteInterface $site = NULL, $operation = '');

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Drupal\micro_site\Entity\SiteInterface|NULL $site
   * @param $operation
   * @return mixed
   */
  public function userCanDoOperation(AccountInterface $account, SiteInterface $site = NULL, $operation = '');

  /**
   * Alter the form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param string $form_id
   */
  public function alterForm(&$form, FormStateInterface $form_state, $form_id);

  /**
   * Gets the object entity of the form if available.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Entity\Entity|false
   *   Entity or FALSE if non-existent or if form operation is
   *   'delete'.
   */
  public function getFormEntity(FormStateInterface $form_state);

}
