<?php

namespace Drupal\broken_link\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the Broken link redirect entity entity.
 *
 * @ContentEntityType(
 *   id = "broken_link_redirect",
 *   label = @Translation("Broken link redirect entity"),
 *   handlers = {
 *     "list_builder" = "Drupal\broken_link\BrokenLinkRedirectListBuilder",
 *     "form" = {
 *       "add" = "Drupal\broken_link\Form\BrokenLinkRedirectForm",
 *       "edit" = "Drupal\broken_link\Form\BrokenLinkRedirectForm",
 *       "delete" = "Drupal\broken_link\Form\BrokenLinkRedirectDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\broken_link\BrokenLinkHtmlRouteProvider",
 *     },
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *   },
 *   base_table = "broken_link_redirect",
 *   admin_permission = "manage broken link redirect",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/broken_link_redirect/{broken_link_redirect}",
 *     "add-form" = "/admin/config/broken_link_redirect/add",
 *     "edit-form" = "/admin/config/broken_link_redirect/{broken_link_redirect}/edit",
 *     "delete-form" = "/admin/config/broken_link_redirect/{broken_link_redirect}/delete",
 *     "collection" = "/admin/config/broken_link_redirect"
 *   }
 * )
 */
class BrokenLinkRedirect extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The entity ID for this broken link redirect content entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The content broken link redirect UUID.'))
      ->setReadOnly(TRUE);

    $fields['pattern'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Pattern'))
      ->setSettings(array(
        'default_value' => '.*',
      ))
      ->setDescription(t('Broken link pattern.'));

    $fields['redirect_path'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Redirect path'))
      ->setSettings(array(
        'default_value' => '.*',
      ))
      ->setDescription(t('Redirect path for broken link.'));

    $fields['enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Enabled'))
      ->setDescription(t('Broken link pattern enabled/disabled.'))
      ->setSettings(array(
        'default_value' => TRUE,
      ));

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity weight'))
      ->setDescription(t('Broken link pattern weightage.'))
      ->setSetting('unsigned', FALSE);

    return $fields;
  }

  /**
   * Method to get redirect path based on broken link.
   *
   * @param string $broken_link
   *   Broken link.
   *
   * @return string
   *   Redirect path for the broken link.
   */
  public function getRedirectLink($broken_link) {
    $db = \Drupal::database();
    $broken_link_redirect = $db->select('broken_link_redirect', 'blr')
      ->fields('blr', array('pattern', 'redirect_path'))
      ->condition('enabled', 1)
      ->orderBy('weight')
      ->execute();
    foreach ($broken_link_redirect as $value) {
      $pattern = ltrim($value->pattern, '\/');
      if (preg_match("/$pattern/", $broken_link)) {
        return $value->redirect_path;
      }
    }

    return NULL;
  }

}
