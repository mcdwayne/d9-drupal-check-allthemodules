<?php

namespace Drupal\quick_clone\Controller;

use DateTime;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CloneController.
 */
class CloneController extends ControllerBase {

  /**
   * Quick_clone.
   *
   * @param int $id
   *   Id of the current node.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @return string
   *   Return Hello string.
   */
  public function quickClone($id) {
    $original_entity = $this->entityTypeManager()->getStorage('node')->load($id);
    /** @var \Drupal\node\NodeInterface $original_entity */
    if (!$original_entity instanceof NodeInterface) {
      throw new EntityStorageException(sprintf('Node with id %d does not exist', $id));
    }

    $new_node = $original_entity->createDuplicate();

    // Check for paragraph fields which need to be duplicated as well.
    foreach ($new_node->getTranslationLanguages() as $langcode => $language) {
      $translated_node = $new_node->getTranslation($langcode);

      foreach ($translated_node->getFieldDefinitions() as $field_definition) {
        $field_storage_definition = $field_definition->getFieldStorageDefinition();
        $field_settings = $field_storage_definition->getSettings();
        if (isset($field_settings['target_type']) && $field_settings['target_type'] === 'paragraph') {

          // Each paragraph entity will be duplicated,
          // so we won't be editing the same as the parent in every clone.
          $field_name = $field_storage_definition->getName();
          if (!$translated_node->get($field_name)->isEmpty()) {
            foreach ($translated_node->get($field_name) as $value) {
              if ($value->entity) {
                $value->entity = $value->entity->createDuplicate();
              }
            }
          }
        }
      }
      $this->processNewNode($translated_node, $original_entity, $langcode);
      drupal_set_message(
        $this->t("Node @title has been created. <a href='/node/@id/edit' target='_blank'>Edit now</a>", [
          '@id' => $translated_node->id(),
          '@title' => $translated_node->getTitle(),
        ]
        ), 'status');
    }

    $url = URL::fromRoute('system.admin_content')->toString(TRUE);
    return new TrustedRedirectResponse($url->getGeneratedUrl());

  }

  /**
   * @param \Drupal\node\NodeInterface $translated_node
   * @param \Drupal\node\NodeInterface $original_entity
   * @param                            $langcode
   *
   * @return int
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function processNewNode(NodeInterface $translated_node, NodeInterface $original_entity, $langcode) {
    $translated_node->setTitle($this->t('Clone of @title', ['@title' => $original_entity->getTitle()], ['langcode' => $langcode]));
    $now = new DrupalDateTime('now');
    $now->setTimezone(new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $translated_node->setCreatedTime($now->getTimestamp());
    return $translated_node->save();
  }

}
