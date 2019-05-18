<?php
/**
 * Created by PhpStorm.
 * User: bartv
 * Date: 07/05/2018
 * Time: 17:52
 */

namespace Drupal\efap;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\ServicesTrait;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Validator;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GenerateCommand
 *
 * @package Drupal\efap
 */
class GenerateCommand extends ContainerAwareCommand {

  use ModuleTrait;
  use ServicesTrait;

  /**
   * @var \Drupal\Console\Core\Utils\ChainQueue
   */
  protected $chainQueue;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Console\Extension\Manager
   */
  protected $extensionManager;

  /**
   * @var \Drupal\efap\Generator
   */
  protected $generator;

  /**
   * @var \Drupal\Console\Core\Utils\StringConverter
   */
  protected $stringConverter;

  /**
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * @var \Drupal\Console\Utils\Validator
   */
  protected $validator;

  /**
   * GenerateCommand constructor.
   *
   * @param \Drupal\Console\Core\Utils\ChainQueue $chainQueue
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Console\Extension\Manager $extensionManager
   * @param \Drupal\efap\Generator $generator
   * @param \Drupal\Console\Core\Utils\StringConverter $stringConverter
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   * @param \Drupal\Console\Utils\Validator $validator
   */
  public function __construct(
    ChainQueue $chainQueue,
    EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    EntityTypeManagerInterface $entityTypeManager,
    Manager $extensionManager,
    Generator $generator,
    StringConverter $stringConverter,
    TransliterationInterface $transliteration,
    Validator $validator
  ) {
    $this->chainQueue = $chainQueue;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->entityTypeManager = $entityTypeManager;
    $this->extensionManager = $extensionManager;
    $this->generator = $generator;
    $this->stringConverter = $stringConverter;
    $this->transliteration = $transliteration;
    $this->validator = $validator;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('generate:efap');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = $this->getIo();

    $module = $this->moduleQuestion(FALSE);
    $module = $this->extensionManager->getModule($module);

    $class = $this->getIo()->ask(
      $this->trans('commands.generate.plugin.block.questions.class'),
      'DefaultExtraField',
      function ($class) {
        return $this->validator->validateClassName($class);
      }
    );

    $entityType = $io->choiceNoList('On what entity would you like to display the field?', $this->getEntityTypes(), 'node');
    $bundle = $io->choiceNoList('On what bundle would you like to display the field?', $this->getBundles($entityType));
    $label = $io->ask('Enter the field\'s label');
    $id = $this->stringConverter->createMachineName($module->getName() . '__' . $label);
    $id = $io->ask('Enter the field\'s id', $id);
    $description = $io->ask('Enter the field\'s description');

    $services = $this->servicesQuestion();
    $services = $this->buildServices($services);

    $this->generator->generate(
      $module,
      $class,
      $entityType,
      $bundle,
      $id,
      $label,
      $description,
      $services
    );

    $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);
  }

  /**
   * @return array
   */
  protected function getEntityTypes(): array {
    foreach ($this->entityTypeManager->getDefinitions() as $definition) {
      $types[] = $definition->id();
    }
    sort($types);

    return $types;
  }

  /**
   * @param $entityType
   *
   * @return array
   */
  protected function getBundles($entityType): array {
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entityType);
    return array_keys($bundles);
  }

}
