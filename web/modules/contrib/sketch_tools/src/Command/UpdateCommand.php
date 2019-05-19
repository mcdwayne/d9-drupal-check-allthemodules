<?php

namespace Drupal\sketch_tools\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Question\Question;

/**
 * Class UpdateCommand.
 *
 * @DrupalCommand (
 *     extension="sketch_tools",
 *     extensionType="module"
 * )
 */
class UpdateCommand extends Command {

  use SketchCommandTrait;

  /**
   * Drupal\Core\Extension\ThemeHandler definition.
   *
   * @var \Drupal\Core\Extension\ThemeHandler
   */
  protected $themeHandler;

  /**
   * Drupal\Core\File\FileSystemInterface definition.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new UpdateCommand object.
   */
  public function __construct(ThemeHandler $theme_handler, FileSystemInterface $file_system) {
    $this->themeHandler = $theme_handler;
    $this->fileSystem = $file_system;
    parent::__construct();
  }
  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('sketch_tools:update')
      ->setDescription($this->trans('commands.sketch_tools.update.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    $io->info($this->trans('commands.sketch_tools.update.messages.success'));
  }

 /**
  * {@inheritdoc}
  */
 protected function interact(InputInterface $input, OutputInterface $output) {
   $io = new DrupalStyle($input, $output);

 }

}
