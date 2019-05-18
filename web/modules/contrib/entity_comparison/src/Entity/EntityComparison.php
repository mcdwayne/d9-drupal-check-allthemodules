<?php

namespace Drupal\entity_comparison\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;
use Drupal\field_ui\FieldUI;

/**
 * Defines the Entity comparison entity.
 *
 * @ConfigEntityType(
 *   id = "entity_comparison",
 *   label = @Translation("Entity comparison"),
 *   label_singular = @Translation("entity comparison"),
 *   label_plural = @Translation("entity comparisons"),
 *   handlers = {
 *     "list_builder" = "Drupal\entity_comparison\EntityComparisonListBuilder",
 *     "form" = {
 *       "add" = "Drupal\entity_comparison\Form\EntityComparisonForm",
 *       "edit" = "Drupal\entity_comparison\Form\EntityComparisonForm",
 *       "delete" = "Drupal\entity_comparison\Form\EntityComparisonDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\entity_comparison\EntityComparisonHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "entity_comparison",
 *   admin_permission = "administer entity comparison",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/entity_comparison/{entity_comparison}",
 *     "add-form" = "/admin/structure/entity_comparison/add",
 *     "edit-form" = "/admin/structure/entity_comparison/{entity_comparison}/edit",
 *     "delete-form" = "/admin/structure/entity_comparison/{entity_comparison}/delete",
 *     "collection" = "/admin/structure/entity_comparison"
 *   }
 * )
 */
class EntityComparison extends ConfigEntityBase implements EntityComparisonInterface {

  /**
   * The Entity comparison ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Entity comparison label.
   *
   * @var string
   */
  protected $label;

  /**
   * Add link's text
   *
   * @var string
   */
  protected $add_link_text;

  /**
   * Remove link's text
   *
   * @var string
   */
  protected $remove_link_text;

  /**
   * Limit
   *
   * @var string
   */
  protected $limit;

  /**
   * The selected entity type.
   *
   * @var string
   */
  protected $entity_type;

  /**
   * The selected bundle type.
   *
   * @var string
   */
  protected $bundle_type;

  /**
   * {@inheritdoc}
   */
  public function getAddLinkText(){
    return $this->add_link_text;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoveLinkText(){
    return $this->remove_link_text;
  }

  /**
   * {@inheritdoc}
   */
  public function getLimit(){
    return $this->limit;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityType(){
    return $this->entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundleType(){
    return $this->bundle_type;
  }

  public static function loadByEntityTypeAndBundleType($entity_type, $bundle_type) {

    $entity_comparison_list = array();

    $entity_comparisons = self::loadMultiple();

    foreach($entity_comparisons as $entity_comparison) {
      if ($entity_type == $entity_comparison->getTargetEntityType() && $bundle_type == $entity_comparison->getTargetBundleType()) {
        $entity_comparison_list[] = $entity_comparison;
      }
    }

    return $entity_comparison_list;
  }

  /**
   * {@inheritdoc}
   */
  public function getLink($entity_id) {

    // Get session service
    $session = \Drupal::service('session');

    // Get vurrent user's id
    $uid = \Drupal::currentUser()->id();

    // Get entity type and bundle type
    $entity_type = $this->getTargetEntityType();
    $bundle_type = $this->getTargetBundleType();

    // Get current entity comparison list
    $entity_comparison_list = $session->get('entity_comparison_' . $uid);

    if ( empty($entity_comparison_list) ) {
      $add_link = TRUE;
    } else {
      if ( !empty($entity_comparison_list[$entity_type][$bundle_type][$this->id()]) && in_array($entity_id, $entity_comparison_list[$entity_type][$bundle_type][$this->id()]) ) {
        $add_link = FALSE;
      } else {
        $add_link = TRUE;
      }
    }

    // Get the url object from route
    $url = Url::fromRoute('entity_comparison.action', array(
      'entity_comparison_id' => $this->id(),
      'entity_id' => $entity_id,
    ), array(
      'query' => \Drupal::service('redirect.destination')->getAsArray(),
      'attributes' => array(
        'id' => 'entity-comparison-' . $this->id() . '-' . $entity_id,
      ),
    ));

    // Set link text
    $link_text = ($add_link)? $this->getAddLinkText() : $this->getRemoveLinkText();

    // Return with the link
    return \Drupal::l($link_text, $url);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {

    if (empty($this->getOriginalId())) {
      $this->createViewMode();

      // Flush all cache
      drupal_flush_all_caches();
      drupal_set_message(t('All caches cleared.'));
    }
  }

  /**
   * Create and enable custom view mode
   */
  protected function createViewMode() {
    // Generate an id for the view mode
    $view_mode_id = $this->getTargetEntityType() . '.' . $this->getTargetBundleType() . '_' . $this->id();
    $display_id = $this->getTargetBundleType() . '_' . $this->id();

    // Create new entity view mode
    $entity_view_mode = EntityViewMode::create(array(
      'id' => $view_mode_id,
      'label' => $this->label(),
      'targetEntityType' => $this->getTargetEntityType(),
    ));

    // Save the entity view mode
    $entity_view_mode->save();

    // Rebuild routes if needed
    \Drupal::service('router.builder')->rebuildIfNeeded();

    // Load target bundle's default display
    $default_display = entity_get_display($this->getTargetEntityType(), $this->getTargetBundleType(), 'default');

    // Clone it for our new view mode
    $new_display = $default_display->createCopy($display_id);

    // Save the display settings
    $new_display->save();

    // Get url to the view mode page
    $url = $this->getOverviewUrl($display_id);

    // Show success message
    drupal_set_message(t('The %display_mode mode now uses custom display settings. You might want to <a href=":url">configure them</a>.', ['%display_mode' => $this->label(), ':url' => $url->toString()]));

    // Enable the created view mode on the target bundle's manage display page
    $new_display->set('status', TRUE);
    $new_display->save();
  }

  /**
   * Get overview Url
   */
  protected function getOverviewUrl($mode) {
    $entity_type = \Drupal::entityManager()->getDefinition($this->getTargetEntityType());
    return Url::fromRoute('entity.entity_view_display.' . $this->getTargetEntityType() . '.view_mode', [
        'view_mode_name' => $mode,
      ] + FieldUI::getRouteBundleParameter($entity_type, $this->getTargetBundleType()));
  }

}
