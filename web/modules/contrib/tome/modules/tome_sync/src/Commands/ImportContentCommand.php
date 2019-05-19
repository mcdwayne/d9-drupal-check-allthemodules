<?php

namespace Drupal\tome_sync\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\tome_sync\AccountSwitcherTrait;
use Drupal\tome_sync\ImporterInterface;
use Drupal\tome_sync\TomeSyncHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Contains the tome:import-content command.
 *
 * @internal
 */
class ImportContentCommand extends ImportCommand {

  use AccountSwitcherTrait;

  /**
   * The account switcher.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * Constructs an ImportContentCommand instance.
   *
   * @param \Drupal\tome_sync\ImporterInterface $importer
   *   The importer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountSwitcherInterface $account_switcher
   *   The account switcher.
   */
  public function __construct(ImporterInterface $importer, EntityTypeManagerInterface $entity_type_manager, AccountSwitcherInterface $account_switcher) {
    parent::__construct($importer, $entity_type_manager);
    $this->accountSwitcher = $account_switcher;
  }

  /**
   * {@inheritdoc}
   */
  protected  function configure() {
    $this->setName('tome:import-content')
      ->setDescription('Imports given content.')
      ->addArgument('names', InputArgument::REQUIRED, 'A comma separated list of IDs in the format entity_type_id:uuid:langcode.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $names = $input->getArgument('names');
    $this->switchToAdmin();
    $names = explode(',', $names);
    foreach ($names as $name) {
      list($entity_type_id, $uuid, $langcode) = TomeSyncHelper::getPartsFromContentName($name);
      $this->importer->importContent($entity_type_id, $uuid, $langcode);
    }
    $this->switchBack();
  }

}
