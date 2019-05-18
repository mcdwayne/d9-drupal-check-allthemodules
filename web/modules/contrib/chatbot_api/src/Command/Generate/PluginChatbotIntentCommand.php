<?php

namespace Drupal\chatbot_api\Command\Generate;

use Drupal\chatbot_api\Generator\PluginChatbotIntentGenerator;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Extension\Manager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Style\DrupalStyle;
// @codingStandardsIgnoreStart
use Drupal\Console\Annotations\DrupalCommand;
// @codingStandardsIgnoreEnd
use Drupal\Console\Core\Command\Command;

/**
 * Class PluginChatbotIntentCommand.
 *
 * @package Drupal\chatbot_api\Command\Generate
 *
 * @DrupalCommand (
 *     extension="chatbot_api",
 *     extensionType="module"
 * )
 */
class PluginChatbotIntentCommand extends Command {

  use ModuleTrait;
  use ConfirmationTrait;
  use CommandTrait;

  /**
   * Drupal console extension manager.
   *
   * @var \Drupal\Console\Extension\Manager
   */
  protected $extensionManager;

  /**
   * Chatbot API Intent generator class.
   *
   * @var \Drupal\chatbot_api\Generator\PluginChatbotIntentGenerator
   */
  protected $generator;

  /**
   * Drupal console string converter.
   *
   * @var \Drupal\Console\Core\Utils\StringConverter
   */
  protected $stringConverter;

  /**
   * Drupal console chain queue.
   *
   * @var \Drupal\Console\Core\Utils\ChainQueue
   */
  protected $chainQueue;

  /**
   * PluginFieldTypeCommand constructor.
   *
   * @param \Drupal\Console\Extension\Manager $extensionManager
   *   Drupal console extension manager.
   * @param \Drupal\chatbot_api\Generator\PluginChatbotIntentGenerator $generator
   *   Chatbot API Intent generator.
   * @param \Drupal\Console\Core\Utils\StringConverter $stringConverter
   *   Drupal console string converter.
   * @param \Drupal\Console\Core\Utils\ChainQueue $chainQueue
   *   Drupal console chain queue.
   */
  public function __construct(Manager $extensionManager, PluginChatbotIntentGenerator $generator, StringConverter $stringConverter, ChainQueue $chainQueue) {
    $this->extensionManager = $extensionManager;
    $this->generator = $generator;
    $this->stringConverter = $stringConverter;
    $this->chainQueue = $chainQueue;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('generate:plugin:chatbotintent')
      ->setDescription($this->trans('commands.generate.plugin.chatbotintent.description'))
      ->addOption('module', NULL, InputOption::VALUE_REQUIRED, $this->trans('commands.common.options.module'))
      ->addOption(
        'class',
        NULL,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.plugin.chatbotintent.options.class')
      )
      ->addOption(
        'label',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.generate.plugin.chatbotintent.options.label')
      )
      ->addOption(
        'plugin-id',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.generate.plugin.chatbotintent.options.plugin-id')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    // @see use Drupal\Console\Command\Shared\ConfirmationTrait::confirmGeneration
    if (!$this->confirmOperation()) {
      return 1;
    }

    $module = $input->getOption('module');
    $class_name = $input->getOption('class');
    $label = $input->getOption('label');
    $plugin_id = $input->getOption('plugin-id');

    $this->generator
      ->generate($module, $class_name, $label, $plugin_id);

    $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery'], FALSE);

    $io->info($this->trans('commands.generate.plugin.chatbotintent.messages.success'));

    return 0;
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    // --module option.
    $module = $input->getOption('module');
    if (!$module) {
      // @see Drupal\Console\Command\Shared\ModuleTrait::moduleQuestion
      $module = $this->moduleQuestion($io);
      $input->setOption('module', $module);
    }

    // --class option.
    $class_name = $input->getOption('class');
    if (!$class_name) {
      $class_name = $io->ask(
        $this->trans('commands.generate.plugin.chatbotintent.questions.class'),
        'ExampleIntent'
      );
      $input->setOption('class', $class_name);
    }

    // --label option.
    $label = $input->getOption('label');
    if (!$label) {
      $label = $io->ask(
        $this->trans('commands.generate.plugin.chatbotintent.questions.label'),
        $this->stringConverter->camelCaseToHuman($class_name)
      );
      $input->setOption('label', $label);
    }

    // --plugin-id option.
    $plugin_id = $input->getOption('plugin-id');
    if (!$plugin_id) {
      $plugin_id = $io->ask(
        $this->trans('commands.generate.plugin.chatbotintent.questions.plugin-id'),
        $this->stringConverter->camelCaseToUnderscore($class_name)
      );
      $input->setOption('plugin-id', $plugin_id);
    }
  }

}
