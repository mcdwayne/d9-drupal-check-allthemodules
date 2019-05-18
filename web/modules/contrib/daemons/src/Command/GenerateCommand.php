<?php

namespace Drupal\daemons\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Core\Generator\GeneratorInterface;
use Symfony\Component\Console\Input\InputOption;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Command\Shared\FormTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Validator;

/**
 * Class GenerateCommand.
 *
 * @DrupalCommand (
 *     extension="daemons",
 *     extensionType="module"
 * )
 */
class GenerateCommand extends ContainerAwareCommand {
  use ModuleTrait;
  use FormTrait;
  use ConfirmationTrait;

  protected $generator;
  protected $extensionManager;
  protected $validator;
  protected $stringConverter;
  protected $chainQueue;

  /**
   * GenerateCommand constructor.
   *
   * @param \Drupal\Console\Core\Generator\GeneratorInterface $daemon_generator
   *   Generator object.
   * @param \Drupal\Console\Extension\Manager $extensionManager
   *   Console Manager object.
   * @param \Drupal\Console\Utils\Validator $validator
   *   Console Validator object.
   * @param \Drupal\Console\Core\Utils\StringConverter $stringConverter
   *   Console StringConverter object.
   * @param \Drupal\Console\Core\Utils\ChainQueue $chainQueue
   *   Console ChainQueue object.
   */
  public function __construct(
    GeneratorInterface $daemon_generator,
    Manager $extensionManager,
    Validator $validator,
    StringConverter $stringConverter,
    ChainQueue $chainQueue
  ) {
    $this->generator = $daemon_generator;
    $this->extensionManager = $extensionManager;
    $this->validator = $validator;
    $this->stringConverter = $stringConverter;
    $this->chainQueue = $chainQueue;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('daemons:generate')
      ->setDescription($this->trans('commands.daemons.generate.description'))
      ->addOption(
        'name',
        NULL,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.daemons.generate.options.name')
      )
      ->addOption(
        'module',
        NULL,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.daemons.generate.options.module')
      )
      ->addOption(
        'class',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.generate.plugin.block.options.class')
      )
      ->addOption(
        'label',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.generate.plugin.block.options.label')
      )
      ->addOption(
        'plugin-id',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.generate.plugin.block.options.plugin-id')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    // Handling --module option.
    $this->getModuleOption();

    // Handling --class option.
    $class = $input->getOption('class');
    if (!$class) {
      $class = $this->getIo()->ask(
        $this->trans('commands.generate.plugin.block.questions.class'),
        'ExampleDaemon',
        function ($class) {
          return $this->validator->validateClassName($class);
        }
      );
      $input->setOption('class', $class);
    }

    // Handling --plugin-id option.
    $pluginId = $input->getOption('plugin-id');
    if (!$pluginId) {
      $pluginId = $this->getIo()->ask(
        $this->trans('commands.generate.plugin.block.questions.plugin-id'),
        $this->stringConverter->camelCaseToUnderscore($class)
      );
      $input->setOption('plugin-id', $pluginId);
    }

    // Handling --label option.
    $label = $input->getOption('label');
    if (!$label) {
      $label = $this->getIo()->ask(
        $this->trans('commands.generate.plugin.block.questions.label'),
        $this->stringConverter->camelCaseToHuman($class)
      );
      $input->setOption('label', $label);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmOperation
    if (!$this->confirmOperation()) {
      return 1;
    }

    $this->getIo()->info('Generating daemon plugin');

    $module = $input->getOption('module');
    $class_name = $this->validator->validateClassName($input->getOption('class'));
    $label = $input->getOption('label');
    $plugin_id = $input->getOption('plugin-id');

    $this->generator->generate([
      'module' => $module,
      'class_name' => $class_name,
      'label' => $label,
      'plugin_id' => $plugin_id,
    ]);

    $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);

    $this->getIo()->info($this->trans('commands.daemons.generate.messages.success'));
  }

}
