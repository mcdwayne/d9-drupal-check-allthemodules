<?php

namespace Drupal\update_runner\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the job update runner entity.
 *
 * @ingroup update_runner
 *
 * @ContentEntityType(
 *   id = "update_runner_job",
 *   label = @Translation("Update runner job"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\update_runner\UpdateRunnerJobListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\update_runner\Form\UpdateRunnerJobForm",
 *       "add" = "Drupal\update_runner\Form\UpdateRunnerJobForm",
 *       "edit" = "Drupal\update_runner\Form\UpdateRunnerJobForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\update_runner\UpdateRunnerJobAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "update_runner_job",
 *   admin_permission = "administer update runner jobs",
 *   entity_keys = {
 *     "id" = "id",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/update_runner/jobs/{update_runner_job}",
 *     "edit-form" = "/admin/config/update_runner/jobs/{update_runner_job}/edit",
 *     "delete-form" = "/admin/config/update_runner/jobs/{update_runner_job}/delete",
 *     "collection" = "/admin/config/update_runner/jobs"
 *   }
 * )
 */
class UpdateRunnerJob extends ContentEntityBase implements ContentEntityInterface, EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Scheduled site update is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash'))
      ->setDescription(t('Hash'))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setRequired(TRUE);

    $fields['processor'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Processor'))
      ->setDescription(t('Processor'))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setRequired(TRUE);

    $fields['data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Data'))
      ->setDescription(t('Data'))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE);

    return $fields;
  }

}
