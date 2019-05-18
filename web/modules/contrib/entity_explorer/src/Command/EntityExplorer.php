<?php

namespace Drupal\entity_explorer\Command;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;
use Drupal\node\NodeStorageInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;

// @codingStandardsIgnoreLine
use Drupal\Console\Annotations\DrupalCommand;


/**
 * Class EntityExplorer.
 *
 * @DrupalCommand (
 *     extension="entity_explorer",
 *     extensionType="module"
 * )
 */
class EntityExplorer extends ContainerAwareCommand {

  protected $entityTypeManager;
  protected $entityRepository;
  protected $allFields;
  protected $startId;

  /**
   * EntityExplorer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   Entity repository.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository) {
    parent::__construct(NULL);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('entity_explorer')
      ->setDescription($this->trans('commands.entity_explorer.default.description'))
      ->addArgument(
        'entity-type',
        InputArgument::REQUIRED,
        $this->trans('commands.entity_explorer.entity.arguments.entity-type'))
      ->addArgument(
        'entity-id',
        InputArgument::REQUIRED,
        $this->trans('commands.entity_explorer.entity.arguments.entity-id'))
      ->addArgument(
        'revision-id',
        InputArgument::OPTIONAL,
      $this->trans('commands.entity_explorer.entity.arguments.revision-id'))
      ->addOption('all-fields');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $type = $input->getArgument('entity-type');
    $id = $input->getArgument('entity-id');
    $this->startId = $id;
    $this->allFields = $input->getOption('all-fields');

    /** @var \Drupal\node\Entity\Node $entity */
    $entity = $this->entityTypeManager->getStorage($type)->load($id);
    if (!$entity) {
      $this->getIo()->error(sprintf($this->trans('Missing entity %s'), $id));
      return;
    }
    $languages = $entity->getTranslationLanguages();

    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $revision_ids */
    $storage = $this->entityTypeManager->getStorage($type);
    if ($storage instanceof NodeStorageInterface) {
      $revision_ids = $storage->revisionIds($entity);
    }
    else {
      $this->getIo()->info($this->trans('Entity without revision interface'));
      $revision_ids = [$entity->getRevisionId()];
    }
    $this->getIo()->info(sprintf($this->trans('commands.entity_explorer.default.messages.entity_summary'), count($revision_ids), $id));

    if ($input->getArgument('revision-id')) {
      $revision_ids = [$input->getArgument('revision-id')];
    }

    foreach ($revision_ids as $revision_id) {
      $this->processRevision($type, $revision_id, $languages);
    }

  }

  /**
   * Loop through fields and find reference fields.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $source
   *   Entity.
   * @param int $level
   *   Depth.
   */
  protected function processFields(ContentEntityInterface $source, $level = 0) {
    foreach ($source->getFields() as $field) {
      $offset = $this->getOffset($level);
      if ($field instanceof EntityReferenceRevisionsFieldItemList) {
        $this->getIo()
          ->writeln('<options=bold>' . $offset . 'Field ' . $field->getFieldDefinition()
            ->getLabel() . ':</>');

        foreach ($field->referencedEntities() as $entity) {
          /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
          $this->getIo()
            ->writeln($offset . '  - <fg=blue>' . $entity->getEntityType()
              ->getLabel() . '</> of type <fg=green>' . $entity->type->entity->label() . '</> (ID: ' . $entity->id() . ', Revision: ' . $entity->getRevisionId() . ')');
          $this->processFields($entity, $level + 1);
        }
      }
      else {
        if ($this->allFields || $source->id() == $this->startId) {
          $this->getIo()
            ->write('<options=bold>' . $offset . 'Field ' . $field->getFieldDefinition()->getLabel() . ': </>');
          $this->getIo()->writeln('<fg=yellow>' . mb_strimwidth($field->getString(), 0, 40, "...") . '</>');
        }
      }
    }
  }

  /**
   * Process individual revision.
   *
   * For some embedded entities (such as Paragraphs), the
   * isRevisionTranslationAffected() response is incorrect.
   *
   * @param string $type
   *   Entity storage.
   * @param int $revision_id
   *   Revision.
   * @param \Drupal\Core\Language\Language[] $languages
   *   Available translation languages.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @see \Drupal\node\Controller\NodeController::revisionOverview
   * @see https://www.drupal.org/project/paragraphs/issues/2904705
   */
  protected function processRevision($type, $revision_id, array $languages) {
    $this->getIo()->writeln('');
    $revision = $this->entityTypeManager
      ->getStorage($type)
      ->loadRevision($revision_id);
    /** @var \Drupal\node\Entity\Node $revision */
    foreach ($languages as $language) {
      /** @var \Drupal\Core\Language\Language $language */
      $revision = $this->entityRepository
        ->getTranslationFromContext($revision, $language->getId());
      if ($revision->hasTranslation($language->getId()) &&
          $revision->getTranslation($language->getId())->isRevisionTranslationAffected()) {
        $this->getIo()->writeln('');
        if ($revision->hasField('title')) {
          $this->getIo()->info(sprintf($this->trans('commands.entity_explorer.default.messages.revision_summary'), $revision_id, $language->getName(), $revision->get('title')->value));
        }
        else {
          $this->getIo()->info(sprintf($this->trans('commands.entity_explorer.default.messages.revision_summary'), $revision_id, $language->getName(), 'No title'));

        }
        $this->processFields($revision);

      }
    }
  }

  /**
   * Get offset.
   *
   * @param int $level
   *   Depth of item.
   *
   * @return string
   *   Formatted horizontal offset.
   */
  protected function getOffset($level) {
    /** @var \Drupal\paragraphs\Entity\Paragraph $entity */
    $offset = "    ";
    if ($level > 0) {
      for ($i = 0; $i <= $level; $i++) {
        // Empty offset to simulate indentation.
        $offset .= "    ";
      }
    }
    return $offset;
  }

}
