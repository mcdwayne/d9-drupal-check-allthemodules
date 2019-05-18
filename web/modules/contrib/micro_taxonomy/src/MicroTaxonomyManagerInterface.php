<?php

namespace Drupal\micro_taxonomy;

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
 * Handles the negotiation of the active domain record.
 */
interface MicroTaxonomyManagerInterface {

  /**
   * The update term operation.
   */
  const UPDATE_TERM = 'update_term';

  /**
   * The delete term operation.
   */
  const DELETE_TERM = 'delete_term';

  /**
   * The create term operation.
   */
  const CREATE_TERM = 'create_term';

  /**
   * The access tab term operation.
   */
  const ACCESS_TAB_TERM = 'access_tab_other_term';

  /**
   * The access overview term operation.
   */
  const ACCESS_OVERVIEW_TERM = 'access_overview_term';

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
   * Is the entity is available on all sites.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to retrieve field data from.
   *
   * @return boolean
   *   TRUE, if the entity is published on all sites. Otherwise FALSE.
   */
  public function isAvailableOnAllSites(EntityInterface $entity);

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
  public function userCanAccessTermOverview(AccountInterface $account, SiteInterface $site = NULL, $operation = '');

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Drupal\micro_site\Entity\SiteInterface|NULL $site
   * @param $operation
   * @return mixed
   */
  public function userCanUpdateTerm(AccountInterface $account, SiteInterface $site = NULL, $operation = '');

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Drupal\micro_site\Entity\SiteInterface|NULL $site
   * @param $operation
   * @return mixed
   */
  public function userCanDeleteTerm(AccountInterface $account, SiteInterface $site = NULL, $operation = '');

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Drupal\micro_site\Entity\SiteInterface|NULL $site
   * @param $operation
   * @return mixed
   */
  public function userCanCreateTerm(AccountInterface $account, SiteInterface $site = NULL, $operation = '');

  /**
   * Alter the taxonomy term form.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $form_id
   * @param \Drupal\taxonomy\TermInterface $entity
   * @return mixed
   */
  public function alterTaxonomyTermForm(&$form, FormStateInterface $form_state, $form_id, TermInterface $entity);

  /**
   * Alter the form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param string $form_id
   */
  public function alterForm(&$form, FormStateInterface $form_state, $form_id);


  /**
   * Alter the content entity form.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $form_id
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @return mixed
   */
  public function alterContentForm(&$form, FormStateInterface $form_state, $form_id, ContentEntityInterface $entity);

  /**
   * Create a dedicated vocabulary for site if set.
   *
   * @param \Drupal\micro_site\Entity\SiteInterface $entity
   * @return mixed
   */
  public function checkCreateSiteVocabulary(SiteInterface $entity);

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
