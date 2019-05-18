<?php

namespace Drupal\contacts_dbs\Plugin\Block;

use Drupal\contacts_dbs\DBSManager;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an add dbs status block admin.
 *
 * @Block(
 *   id = "contacts_dbs_add_dbs_modal",
 *   admin_label = @Translation("Add workforce modal links"),
 *   category = @Translation("Contacts DBS"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user", required = TRUE, label = @Translation("User"))
 *   }
 * )
 */
class DBSStatusAddBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The DBS workforce storage handler.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $dbsWorkforceStorage;

  /**
   * The DBS manager.
   *
   * @var \Drupal\contacts_dbs\DBSManager
   */
  protected $dbsManager;

  /**
   * Constructs a new StatusArchiveWorker object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\contacts_dbs\DBSManager $dbs_manager
   *   The DBS manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, DBSManager $dbs_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dbsWorkforceStorage = $entity_type_manager->getStorage('dbs_workforce');
    $this->dbsManager = $dbs_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('contacts_dbs.dbs_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [];

    $user = $this->getContextValue('user');
    $workforces = $this->dbsWorkforceStorage->loadMultiple();
    foreach ($workforces as $workforce) {
      // Ignore existing dbs workforces.
      if ($this->dbsManager->getDbs($user->id(), $workforce->id(), FALSE)) {
        continue;
      }

      $block[$workforce->id()] = [
        '#type' => 'link',
        '#title' => $this->t('+ Add %workforce', [
          '%workforce' => $workforce->label(),
        ]),
        '#url' => Url::fromRoute('entity.dbs_status.add_form', [
          'user' => $user->id(),
          'dbs_workforce' => $workforce->id(),
        ], [
          'query' => \Drupal::destination()->getAsArray(),
        ]),
        '#attributes' => ['class' => ['clearfix']],
      ];
    }

    return $block;
  }

}
