<?php

namespace Drupal\discussions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\discussions\DiscussionTypeInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Defines the Discussion Type entity.
 *
 * @ConfigEntityType(
 *   id = "discussion_type",
 *   label = @Translation("Discussion Type"),
 *   handlers = {
 *     "list_builder" = "Drupal\discussions\Controller\DiscussionTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\discussions\Form\DiscussionTypeForm",
 *       "edit" = "Drupal\discussions\Form\DiscussionTypeForm",
 *       "delete" = "Drupal\discussions\Form\DiscussionTypeDeleteForm"
 *     }
 *   },
 *   config_prefix = "discussion_type",
 *   admin_permission = "administer discussions",
 *   bundle_of = "discussion",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/discussions/discussion_type/{discussion_type}",
 *     "add-form" = "/admin/structure/discussions/discussion_type/add",
 *     "edit-form" = "/admin/structure/discussions/discussion_type/{discussion_type}/edit",
 *     "delete-form" = "/admin/structure/discussions/discussion_type/{discussion_type}/delete",
 *     "collection" = "/admin/structure/discussions/discussion_type"
 *   }
 * )
 */
class DiscussionType extends ConfigEntityBundleBase implements DiscussionTypeInterface {

  /**
   * The Discussion Type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Discussion Type label.
   *
   * @var string
   */
  protected $label;

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if ($update == FALSE) {
      $this->addCommentsField($this->id);
    }
  }

  /**
   * Adds a comments field to a discussion type.
   *
   * @param string $discussion_type_id
   *   The discussion type ID.
   */
  private function addCommentsField($discussion_type_id) {
    if (!FieldConfig::loadByName('discussion', $discussion_type_id, 'discussions_comments')) {
      // Attach the comments field by default.
      $field = \Drupal::entityManager()->getStorage('field_config')->create([
        'label' => 'Replies',
        'bundle' => $discussion_type_id,
        'field_storage' => FieldStorageConfig::loadByName('discussion', 'discussions_comments'),
      ]);
      $field->save();

      // Assign widget settings for the 'default' form mode.
      entity_get_form_display('discussion', $discussion_type_id, 'default')
        ->setComponent('discussions_comments', [
          'type' => 'comment_default',
          'weight' => 100,
        ])
        ->save();

      // Assign display settings for the 'default' view mode.
      entity_get_display('discussion', $discussion_type_id, 'default')
        ->setComponent('discussions_comments', [
          'type' => 'comment_default',
          'weight' => 0,
          'label' => 'hidden',
        ])
        ->save();
    }
  }

}
