<?php

/**
 * @file
 * Contains \Drupal\smartling\Plugin\Action\TranslateNode.
 */

namespace Drupal\smartling_config_translation\Plugin\Action;

use Drupal\smartling\Plugin\Action\SmartlingBaseTranslationAction;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Translate entity.
 *
 * @Action(
 *   id = "smartling_translate_config_entity_action",
 *   label = @Translation("Translate config to selected languages"),
 *   type = "smartling_config_translation",
 *   confirm_form_route_name = "smartling_config_translation.upload_multiple_lang"
 * )
 */
class TranslateConfigEntity extends SmartlingBaseTranslationAction {


  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $ids = [];

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    foreach ($entities as $entity) {
      $config_name = $entity->get("name")->value;
      $bundle = $entity->get("bundle")->value;
      $ids[$config_name] = $bundle;
    }

    $this->tempStoreFactory->get('smartling_config_translation_operations_send')
      ->set($this->currentUser->id(), $ids);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    // @todo Move the check to own service.
    $result = content_translation_translate_access($object);

    return TRUE;//$return_as_object ? $result : $result->isAllowed();
  }
//  /**
//   * {@inheritdoc}
//   */
//  public function executeMultiple(array $entities) {
//
//
//    $ids = [];
//    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
//    foreach ($entities as $entity) {
//      $ids[$entity->id()] = $entity->id();
//    }
//    $this->tempStoreFactory->get('smartling_operations_send')
//      ->set($this->currentUser->id(), $ids);
//    return;
//
//    //print_r($entities);die();
//    $strings = [];
//    foreach($entities as $entity) {
////    foreach(['system.action.node_unpublish_action', 'system.maintenance', 'block.block.bartik_account_menu', 'block.block.bartik_branding', 'block.block.bartik_breadcrumbs', 'block.block.bartik_content'] as $name) {
////      $config_name = $name;
//      $config_name = $entity->get("name")->value;
//      $bundle = $entity->get("bundle")->value;
//
//      $typed_config = \Drupal::service('config.typed');
//      $schema = $typed_config->get($config_name);
//      $data_def = $schema->getDataDefinition();
//
//      if ($data_def['type'] == 'config_object') {
//        die('hi');
//      }
//      $srv = \Drupal::getContainer()->get('smartling_config_translation.config_translation');
//      $str = $srv->getConfigSourceData([$config_name]);
//      if (empty($str)) {
//        $entity = entity_load($bundle, $config_name);
//        $str = $srv->getSourceData($entity);
//      }
//      //$strings[] = $str;
//      $encoder = \Drupal::getContainer()->get('serializer.encoder.smartling_config_xml');// 'serializer.encoder.smartling_xml');
//      $xml = $encoder->encode($str, 'smartling_xml');
//
//      //$config = \Drupal::config($config_name);
//      $srv->uploadConfig($xml, $config_name . '.xml', ['nl']);
//    }
//
//    print_r($strings);
//    //\Drupal::config('lingotek.settings');
//    //$config = \Drupal::configFactory()->getEditable('lingotek.settings');
////    $ids = [];
////    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
////    foreach ($entities as $entity) {
////      $ids[$entity->id()] = $entity->id();
////    }
////    $this->tempStoreFactory->get('smartling_operations_send')
////      ->set($this->currentUser->id(), $ids);
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function execute(ContentEntityInterface $entity = NULL) {
//    $this->executeMultiple([$entity]);
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
//    /** @var \Drupal\node\NodeInterface $object */
//    // @todo Move the check to own service.
//    $result = content_translation_translate_access($object);
//
//    return TRUE;//$return_as_object ? $result : $result->isAllowed();
//  }

}
