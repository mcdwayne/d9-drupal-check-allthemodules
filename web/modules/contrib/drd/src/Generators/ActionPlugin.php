<?php

namespace Drupal\drd\Generators;

use DrupalCodeGenerator\Command\BaseGenerator;
use DrupalCodeGenerator\Utils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class ActionPlugin.
 *
 * @package Drupal\drd\Generators
 */
class ActionPlugin extends BaseGenerator {

  protected $name = 'drd-action-plugin';
  protected $description = 'Generates an action plugin for DRD.';
  protected $alias = 'drdap';
  protected $moduleHandler;
  protected $vars = [];

  /**
   * ActionPlugin constructor.
   *
   * @param ModuleHandler $moduleHandler
   *   The containers modules handler.
   * @param string $name
   *   TODO.
   */
  public function __construct(ModuleHandler $moduleHandler = NULL, $name = NULL) {
    $this->templatePath = __DIR__ . '/templates';
    parent::__construct($name);
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Get extra vars depending on the selected action plugin type.
   *
   * @return array
   *   The extra vars.
   */
  private function typeVars() {
    $vars = [
      'drd' => [
        'dc_base_class' => 'BaseSystem',
      ],
      'drd_host' => [
        'dc_base_class' => 'BaseHost',
        'drush_callback' => 'hosts',
      ],
      'drd_core' => [
        'dc_base_class' => 'BaseCore',
        'drush_callback' => 'cores',
      ],
      'drd_domain' => [
        'dc_base_class' => 'BaseDomain',
        'drush_callback' => 'domains',
      ],
    ];
    return $vars[$this->vars['type']];
  }

  /**
   * Set a file for rendering.
   *
   * @param string $path
   *   The sub-path within the templates directory.
   * @param string $filename
   *   The destination filename.
   * @param bool $append
   *   Whether to append (TRUE) or replace (FALSE) the destination file.
   * @param string|null $twigfile
   *   If null, the source filename will be the destination filename with the
   *   appended ".twig" extension, and if the source filename is different, this
   *   should be stated here.
   */
  private function setOrAppendFile($path, $filename, $append = TRUE, $twigfile = NULL) {
    if (!isset($twigfile)) {
      $twigfile = $filename . '.twig';
    }
    $callback = $append ? 'setServicesFile' : 'setFile';
    $this->{$callback}(
      trim($path . '/' . $filename, '/'),
      trim($path . '/' . $twigfile, '/'),
      $this->vars
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $questions = Utils::defaultQuestions();
    $questions['id'] = new Question('Action ID');
    $questions['id']->setValidator([Utils::class, 'validateRequired']);
    $questions['label'] = new Question('Action Label');
    $questions['label']->setValidator([Utils::class, 'validateRequired']);
    $questions['type'] = new ChoiceQuestion('Action Type', [
      'drd',
      'drd_host',
      'drd_core',
      'drd_domain',
    ], 'drd_domain');
    $questions['type']->setValidator([Utils::class, 'validateRequired']);

    $this->vars = $this->collectVars($input, $output, $questions);

    $this->vars['class'] = Utils::camelize($this->vars['id']);
    $this->vars += $this->typeVars();

    $this->setOrAppendFile('', 'console.services.yml');
    $this->setOrAppendFile('', 'drush.services.yml');
    $this->setOrAppendFile('config/optional', 'system.action.' . $this->vars['machine_name'] . '_drd_action_' . $this->vars['id'] . '.yml', FALSE, 'system.action.drd_action.yml.twig');
    foreach (['6', '7', '8'] as $version) {
      $this->setOrAppendFile('src/Agent/Action/V' . $version, $this->vars['class'] . '.php', FALSE, 'Class.php.twig');
    }
    $this->setOrAppendFile('src/Command', $this->vars['class'] . '.php', FALSE, 'Class.php.twig');
    $this->setOrAppendFile('src/Commands', $this->vars['class'] . 'Commands.php', FALSE, 'ClassCommands.php.twig');
    $this->setOrAppendFile('src/Plugin/Action', $this->vars['class'] . '.php', FALSE, $this->vars['type'] . '.php.twig');
  }

}
