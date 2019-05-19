<?php

/**
 * @file
 * Contains \Drupal\smartling\Plugin\Action\TranslateNode.
 */

namespace Drupal\smartling_config_translation\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\smartling\Entity\SmartlingSubmission;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Translate entity.
 *
 * @Action(
 *   id = "smartling_download_config_entity_action",
 *   label = @Translation("Download translation for config to all configured languages"),
 *   type = "smartling_config_translation",
 *   confirm_form_route_name = "smartling_config_translation.download_multiple_lang"
 * )
 */
class DownloadConfigEntity extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Creates TranslateNode action.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PrivateTempStoreFactory $temp_store_factory, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $current_user;
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('user.private_tempstore'),
      $container->get('current_user')
    );
  }

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
    $this->tempStoreFactory->get('smartling_config_translation_operations_download')
      ->set($this->currentUser->id(), $ids);
    return;
//    foreach(['system.maintenance', 'block.block.bartik_account_menu', 'block.block.bartik_branding', 'block.block.bartik_breadcrumbs', 'block.block.bartik_content'] as $name) {
//      $config_name = $name;//$entity->get("name")->value;
//
//      $srv = \Drupal::getContainer()->get('smartling_config_translation.config_translation');
//      $file = $srv->downloadConfig($config_name . '.xml', 'nl-NL');
//      $encoder = \Drupal::getContainer()->get('serializer.encoder.smartling_config_xml');// 'serializer.encoder.smartling_xml');
//      $data = $encoder->decode($file, 'smartling_xml');
//
//
//      $srv->saveConfig($config_name, 'nl', $data);
//
//    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ContentEntityInterface $entity = NULL) {
    $this->executeMultiple([$entity]);
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

}
