<?php

namespace Drupal\console_extras\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Utils\Validator;
use Drupal\console_extras\Generator\BatchGenerator;

/**
 * Class BatchCommand.
 *
 * @Drupal\Console\Annotations\DrupalCommand (
 *     extension="console_extras",
 *     extensionType="module"
 * )
 */
class BatchCommand extends ContainerAwareCommand {

  use ModuleTrait;

  /**
   * A BatchGenerator service.
   *
   * @var Drupal\console_extras\Generator\BatchGenerator
   */
  protected $generator;

  /**
   * A Manager service.
   *
   * @var Drupal\Console\Extension\Manager
   */
  protected $extensionManager;

  /**
   * A Validator service.
   *
   * @var Drupal\Console\Utils\Validator
   */
  protected $validator;

  /**
   * A StringConverter service.
   *
   * @var Drupal\Console\Core\Utils\StringConverter
   */
  protected $stringConverter;

  /**
   * A ChainQueue service.
   *
   * @var Drupal\Console\Core\Utils\ChainQueue
   */
  protected $chainQueue;

  /**
   * A app root path string.
   *
   * @var string
   */
  protected $appRoot;

  /**
   * BatchCommand constructor.
   *
   * @param Drupal\console_extras\Generator\BatchGenerator $generator
   *   A BatchGenerator service.
   * @param Drupal\Console\Extension\Manager $extensionManager
   *   A Manager service.
   * @param Drupal\Console\Utils\Validator $validator
   *   A Validator service.
   * @param Drupal\Console\Core\Utils\StringConverter $stringConverter
   *   A StringConverter service.
   * @param Drupal\Console\Core\Utils\ChainQueue $chainQueue
   *   A ChainQueue service.
   * @param string $appRoot
   *   A app root path string.
   */
  public function __construct(
    BatchGenerator $generator,
    Manager $extensionManager,
    Validator $validator,
    StringConverter $stringConverter,
    ChainQueue $chainQueue,
    $appRoot
  ) {
    $this->generator = $generator;
    $this->extensionManager = $extensionManager;
    $this->validator = $validator;
    $this->stringConverter = $stringConverter;
    $this->chainQueue = $chainQueue;
    $this->appRoot = $appRoot;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('generate:extra:batch')
      ->setDescription($this->trans('commands.generate.extra.batch.description'))
      ->addOption(
        'module',
        NULL,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.common.options.module')
      )
      ->addOption(
        'form_class',
        NULL,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.form.options.class')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    // --module option.
    $module = $this->getModuleOption();

    // --form_class option.
    $className = $input->getOption('form_class');
    if (!$className) {
      $className = $this->getIo()->ask(
        $this->trans('commands.generate.form.questions.class'),
        'DefaultBatchForm',
        function ($className) use ($module) {
          // Gets the destination module's path.
          $modulePath = drupal_get_path('module', $module);

          // Checks if the route exist.
          $routeName = $module . '.batch.' . $this->stringConverter->camelCaseToMachineName($className);
          $route = \Drupal::service('router.route_provider')
            ->getRoutesByNames([$routeName]);
          if (count($route) > 0) {
            throw new \InvalidArgumentException(
              sprintf(
                $this->trans('commands.generate.extra.batch.messages.errors.route-exists'),
                $routeName
              )
            );
          }

          // Checks if the form class files exist.
          $fullPath = $modulePath . '/src/Form/' . $className . '.php';
          if (file_exists($fullPath)) {
            throw new \InvalidArgumentException(
              sprintf(
                $this->trans('commands.generate.module.errors.directory-exists'),
                $fullPath
              )
            );
          }

          return $this->validator->validateClassName($className);
        }
      );
      $input->setOption('form_class', $className);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $module = $input->getOption('module');
    $form_class = $input->getOption('form_class');
    $form_class_underscore = $this->stringConverter->camelCaseToMachineName($form_class);

    $this->generator->generate(['module' => $module, 'form_class' => $form_class]);
    $this->getIo()->info($this->trans('commands.generate.extra.batch.messages.success.generated'));
    $url = \Drupal::request()->getSchemeAndHttpHost() . '/admin/content/batch/' . $form_class_underscore;
    $this->getIo()->info(
      sprintf(
        $this->trans('commands.generate.extra.batch.messages.success.help-info'),
        $url
      )
    );

    $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
  }

}
