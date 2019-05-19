<?php

namespace Drupal\tome_sync\Commands;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\tome_sync\AccountSwitcherTrait;
use Drupal\tome_sync\ExporterInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Contains the tome:export-content command.
 *
 * @internal
 */
class ExportContentCommand extends ExportCommand {

  use AccountSwitcherTrait;

  /**
   * The account switcher.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * Constructs an ExportContentCommand instance.
   *
   * @param \Drupal\tome_sync\ExporterInterface $exporter
   *   The exporter.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Session\AccountSwitcherInterface $account_switcher
   *   The account switcher.
   */
  public function __construct(ExporterInterface $exporter, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, AccountSwitcherInterface $account_switcher) {
    parent::__construct($exporter, $entity_type_manager, $event_dispatcher);
    $this->accountSwitcher = $account_switcher;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('tome:export-content')
      ->setDescription('Exports given content.')
      ->addArgument('chunk', InputArgument::REQUIRED, 'A comma separated list of ID pairs in the format entity_type_id:id.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    $chunk = $input->getArgument('chunk');
    $this->switchToAdmin();
    $id_pairs = explode(',', $chunk);
    $storages = [];
    foreach ($id_pairs as $id_pair) {
      list($entity_type_id, $id) = explode(':', $id_pair);
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      if (!$entity_type) {
        $this->io()->error("The entity type $entity_type_id does not exist.");
        return 1;
      }
      if (!isset($storages[$entity_type_id])) {
        $storages[$entity_type_id] = $this->entityTypeManager->getStorage($entity_type_id);
      }
      $entity = $storages[$entity_type_id]->load($id);
      if (!$entity) {
        $this->io()->error("No entity found for $id_pair.");
        return 1;
      }
      if (!($entity instanceof ContentEntityInterface)) {
        $this->io()->error("$id_pair is not a content entity.");
        return 1;
      }
      foreach ($entity->getTranslationLanguages() as $language) {
        $this->exporter->exportContent($entity->getTranslation($language->getId()));
      }
    }
    $this->switchBack();
  }

}
