<?php

namespace Drupal\wrappers_delight\Command;

use Doctrine\Common\Annotations\AnnotationReader;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Generator\ModuleGenerator;
use Drupal\Console\Utils\Validator;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\wrappers_delight\Annotation\WrappersDelight;
use Drupal\wrappers_delight\Annotation\WrappersDelightMethod;
use Drupal\wrappers_delight\Generator\BundleWrapperGenerator;
use Drupal\wrappers_delight\Generator\EntityWrapperGenerator;
use Drupal\wrappers_delight\Generator\BundleWrapperQueryGenerator;
use Drupal\wrappers_delight\Generator\MethodGenerator;
use Drupal\wrappers_delight\Generator\QueryWrapperGenerator;
use Drupal\wrappers_delight\WrappersDelightPluginManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Annotations\DrupalCommand;

/**
 * Class BundleWrapperCommand.
 *
 * @DrupalCommand (
 *     extension="wrappers_delight",
 *     extensionType="module"
 * )
 */
class BundleWrapperCommand extends Command {

  use ContainerAwareCommandTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\wrappers_delight\WrappersDelightPluginManager
   */
  protected $pluginManager;

  /**
   * @var \Drupal\wrappers_delight\Generator\MethodGenerator
   */
  protected $methodGenerator;

  /**
   * @var \Drupal\wrappers_delight\Generator\EntityWrapperGenerator
   */
  protected $entityWrapperGenerator;

  /**
   * @var \Drupal\wrappers_delight\Generator\QueryWrapperGenerator
   */
  protected $queryWrapperGenerator;

  /**
   * @var \Drupal\wrappers_delight\Generator\BundleWrapperGenerator
   */
  protected $bundleWrapperGenerator;

  /**
   * @var \Drupal\wrappers_delight\Generator\BundleWrapperQueryGenerator
   */
  protected $bundleWrapperQueryGenerator;

  /**
   * @var \Drupal\Console\Generator\ModuleGenerator
   */
  protected $moduleGenerator;

  /**
   * @var \Drupal\Console\Utils\Validator
   */
  protected $validator;

  /**
   * @var \Drupal\Console\Core\Utils\ChainQueue
   */
  protected $chainQueue;

  /**
   * @var string
   */
  protected $appRoot;

  /**
   * @var array
   */
  protected $classes;

  /**
   * BundleWrapperCommand constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\wrappers_delight\WrappersDelightPluginManager $plugin_manager
   * @param \Drupal\wrappers_delight\Generator\MethodGenerator $method_generator
   * @param \Drupal\wrappers_delight\Generator\EntityWrapperGenerator $entity_wrapper_generator
   * @param \Drupal\wrappers_delight\Generator\QueryWrapperGenerator $query_wrapper_generator
   * @param \Drupal\wrappers_delight\Generator\BundleWrapperGenerator $bundle_wrapper_generator
   * @param \Drupal\wrappers_delight\Generator\BundleWrapperQueryGenerator $bundle_wrapper_query_generator
   * @param \Drupal\Console\Generator\ModuleGenerator $module_generator
   * @param \Drupal\Console\Utils\Validator $validator
   * @param \Drupal\Console\Core\Utils\ChainQueue $chain_queue
   * @param string $appRoot
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    ModuleHandlerInterface $module_handler,
    WrappersDelightPluginManager $plugin_manager,
    MethodGenerator $method_generator,
    EntityWrapperGenerator $entity_wrapper_generator,
    QueryWrapperGenerator $query_wrapper_generator,
    BundleWrapperGenerator $bundle_wrapper_generator,
    BundleWrapperQueryGenerator $bundle_wrapper_query_generator,
    ModuleGenerator $module_generator,
    Validator $validator,
    ChainQueue $chain_queue,
    $appRoot) {

    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->moduleHandler = $module_handler;
    $this->pluginManager = $plugin_manager;
    $this->methodGenerator = $method_generator;
    $this->entityWrapperGenerator = $entity_wrapper_generator;
    $this->queryWrapperGenerator = $query_wrapper_generator;
    $this->bundleWrapperGenerator = $bundle_wrapper_generator;
    $this->bundleWrapperQueryGenerator = $bundle_wrapper_query_generator;
    $this->moduleGenerator = $module_generator;
    $this->validator = $validator;
    $this->chainQueue = $chain_queue;
    $this->appRoot = $appRoot;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('generate:bundle_wrapper')
      ->setDescription($this->trans('commands.generate.bundle_wrapper.description'))
      ->addArgument(
        'entity_type',
        InputArgument::REQUIRED,
        $this->trans('commands.generate.bundle_wrapper.arguments.entity_type'),
        NULL
      )
      ->addArgument(
        'bundles',
        InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
        $this->trans('commands.generate.bundle_wrapper.arguments.bundles'),
        NULL
      )
      ->addOption(
        'extension',
        NULL,
        InputArgument::OPTIONAL,
        $this->trans('commands.generate.bundle_wrapper.options.extension'),
        'wrappers_custom'
      )
      ->addOption(
        'destination',
        NULL,
        InputArgument::OPTIONAL,
        $this->trans('commands.generate.bundle_wrapper.options.destination'),
        'modules/custom'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    $entity_type = $input->getArgument('entity_type');
    $bundles = $input->getArgument('bundles');
    $destination = $input->getOption('destination');
    
    try {
      $extension = $this->validator->validateModuleName($input->getOption('extension'));
    } catch (\Exception $e) {
      $io->error($this->trans('commands.generate.bundle_wrapper.messages.invalid_extension_name'));
      return;
    }

    // Validate Entity Type
    if (!$this->validEntityType($entity_type)) {
      $io->error($this->trans('commands.generate.bundle_wrapper.messages.invalid_entity_type'));
      return;
    }

    // Validate bundles
    if (empty($bundles)) {
      $bundles = $this->getBundles($entity_type);
    }
    else {
      foreach ($bundles as $bundle) {
        if (!$this->validBundle($entity_type, $bundle)) {
          $io->error($this->trans('commands.generate.bundle_wrapper.messages.invalid_bundle'));
          return;
        }
      }
    }
    
    // @todo: Method annotation won't auto-load after a cache clear unless we explicitly call it.
    new \Drupal\wrappers_delight\Annotation\WrappersDelightMethod();

    // Get bundle information
    $wrapper_classes = $this->getExistingWrapperClasses();
    $wrapper_info = [];
    if (!empty($wrapper_classes[$entity_type])) {
      $wrapper_info[$entity_type] = $wrapper_classes[$entity_type];
      if (empty($wrapper_info[$entity_type]['extension'])) {
        $wrapper_info[$entity_type]['extension'] = $extension;
      }
    }
    else {
      $wrapper_info[$entity_type] = [
        'extension' => $extension,
        'class' => $this->getDefaultWrapperClassName($extension, $entity_type),
        'bundles' => [],
        'query' => [
          'extension' => $extension,
          'class' => $this->getDefaultQueryClassName($extension, $entity_type),
        ],
      ];
    }
    $wrapper_info[$entity_type]['fields'] = $this->getFields($entity_type);

    // Ensure that class files are loaded, so that methods work for disabled modules.
    $this->loadClassFile($wrapper_info[$entity_type]['class'], $wrapper_info[$entity_type]['extension']);
    $this->loadClassFile($wrapper_info[$entity_type]['query']['class'], $wrapper_info[$entity_type]['query']['extension']);

    // Load existing methods for classes
    $wrapper_info[$entity_type]['methods'] = $this->getMethods(
      $wrapper_info[$entity_type]['class'], 
      $this->getEntityClass($entity_type)->getName()
    );
    $wrapper_info[$entity_type]['query']['methods'] = $this->getMethods(
      $wrapper_info[$entity_type]['query']['class'],
      'Drupal\wrappers_delight\QueryWrapperBase'
    );

    // Generate module if necessary
    $module_data = system_rebuild_module_data();
    if (empty($module_data[$wrapper_info[$entity_type]['extension']])) {
      $this->createModule($wrapper_info[$entity_type]['extension'], $destination);
    }
    else {
      // For existing modules, check if they have wrappers_delight as a dependency
      /** @var \Drupal\Core\Extension\Extension $extension_obj */
      $extension_obj = $module_data[$wrapper_info[$entity_type]['extension']];
      $dependencies = $extension_obj->info['dependencies'];
      if (!in_array('wrappers_delight', $dependencies)) {
        $io->warningLite($this->trans('commands.generate.bundle_wrapper.messages.add_wd_dependency') . ':' . $extension_obj->getName());
      }
    }
    
    foreach ($bundles as $bundle) {
      if (!empty($wrapper_classes[$entity_type]['bundles'][$bundle])) {
        $wrapper_info[$entity_type]['bundles'][$bundle] = $wrapper_classes[$entity_type]['bundles'][$bundle];
      }
      else {
        $wrapper_info[$entity_type]['bundles'][$bundle] = [
          'extension' => $extension,
          'class' => $this->getDefaultWrapperClassName($extension, $entity_type, $bundle),
          'query' => [
            'class' => $this->getDefaultQueryClassName($extension, $entity_type, $bundle),
            'extension' => $extension,
          ],
        ];
      }
      $wrapper_info[$entity_type]['bundles'][$bundle]['fields'] = $this->getFields($entity_type, $bundle);

      // Ensure that class files are loaded, so that methods work for disabled modules.
      $this->loadClassFile($wrapper_info[$entity_type]['bundles'][$bundle]['class'], $wrapper_info[$entity_type]['bundles'][$bundle]['extension']);
      $this->loadClassFile($wrapper_info[$entity_type]['bundles'][$bundle]['query']['class'], $wrapper_info[$entity_type]['bundles'][$bundle]['query']['extension']);
      
      // Load existing methods for classes
      $wrapper_info[$entity_type]['bundles'][$bundle]['methods'] = $this->getMethods(
        $wrapper_info[$entity_type]['bundles'][$bundle]['class'],
        $wrapper_info[$entity_type]['class'],
        $this->getEntityClass($entity_type)->getName()
      );
      $wrapper_info[$entity_type]['bundles'][$bundle]['query']['methods'] = $this->getMethods(
        $wrapper_info[$entity_type]['bundles'][$bundle]['query']['class'],
        $wrapper_info[$entity_type]['query']['class'],
        'Drupal\wrappers_delight\QueryWrapperBase'
      );
      
      // We already generated the module above, but if the module exists, check if it has
      // wrappers delight as a dependency
      $module_data = system_rebuild_module_data();
      if (!empty($module_data[$wrapper_info[$entity_type]['bundles'][$bundle]['extension']])) {
        /** @var \Drupal\Core\Extension\Extension $extension_obj */
        $extension_obj = $module_data[$wrapper_info[$entity_type]['bundles'][$bundle]['extension']];
        $dependencies = $extension_obj->info['dependencies'];
        if (!in_array('wrappers_delight', $dependencies)) {
          $io->warningLite($this->trans('commands.generate.bundle_wrapper.messages.add_wd_dependency') . ':' . $extension_obj->getName());
        }
      }
    }
    
    // Generate methods for the entity wrapper class
    $methods = [];

    $fields_to_generate = [
      WrappersDelightMethod::GETTER => $wrapper_info[$entity_type]['fields'],
      WrappersDelightMethod::SETTER => $wrapper_info[$entity_type]['fields']
    ];
    foreach ($wrapper_info[$entity_type]['methods'] as $method => $method_info) {
      // Remove fields that already have a method defined.
      if (!empty($method_info['field']) && !empty($method_info['type'])) {
        unset($fields_to_generate[$method_info['type']][$method_info['field']]);
      }
    }
    foreach ($fields_to_generate as $type => $field_names) {
      foreach ($field_names as $field_name => $field_config) {
        $methods[] = $this->methodGenerator->generate($field_config, $type, $wrapper_info[$entity_type]['methods']);
      }
    }
    $methods = array_filter($methods);
    // Create or update the entity wrapper class.
    if (class_exists($wrapper_info[$entity_type]['class'])) {
      $this->entityWrapperGenerator->update($entity_type, $wrapper_info[$entity_type]['class'], $wrapper_info[$entity_type]['extension'], !empty($wrapper_info[$entity_type]['bundles']), $methods);
    }
    else {
      $this->entityWrapperGenerator->generate($entity_type, $wrapper_info[$entity_type]['class'], $wrapper_info[$entity_type]['extension'], !empty($wrapper_info[$entity_type]['bundles']), $methods);
    }
    

    
    // Generate methods for the entity query wrapper class.
    $methods = [];

    // Determine which fields don't already have setters and getters
    $fields_to_generate = [
      WrappersDelightMethod::CONDITION => $wrapper_info[$entity_type]['fields'],
      WrappersDelightMethod::SORT => $wrapper_info[$entity_type]['fields'],
      WrappersDelightMethod::EXISTS => $wrapper_info[$entity_type]['fields'],
      WrappersDelightMethod::NOT_EXISTS => $wrapper_info[$entity_type]['fields'],
    ];
    foreach ($wrapper_info[$entity_type]['query']['methods'] as $method => $method_info) {
      if (!empty($method_info['field']) && !empty($method_info['type'])) {
        unset($fields_to_generate[$method_info['type']][$method_info['field']]);
      }
    }

    foreach ($fields_to_generate as $type => $field_names) {
      foreach ($field_names as $field_name => $field_config) {
        $methods[] = $this->methodGenerator->generate($field_config, $type, $wrapper_info[$entity_type]['methods']);
      }
    }
    $methods = array_filter($methods);

    // Create or update the query wrapper class.
    if (class_exists($wrapper_info[$entity_type]['query']['class'])) {
      $this->queryWrapperGenerator->update($entity_type, $wrapper_info[$entity_type]['query']['class'], $wrapper_info[$entity_type]['class'], $wrapper_info[$entity_type]['query']['extension'], $methods);
    }
    else {
      $this->queryWrapperGenerator->generate($entity_type, $wrapper_info[$entity_type]['query']['class'], $wrapper_info[$entity_type]['class'], $wrapper_info[$entity_type]['query']['extension'], $methods);
    }
    
    foreach ($bundles as $bundle) {
      $bundle_info = $wrapper_info[$entity_type]['bundles'][$bundle];
      
      // Generate methods for entity class
      $methods = [];
      
      // Determine which fields don't already have setters and getters
      $fields_to_generate = [
        WrappersDelightMethod::GETTER => $bundle_info['fields'],
        WrappersDelightMethod::SETTER => $bundle_info['fields']
      ];
      foreach ($bundle_info['methods'] as $method => $method_info) {
        if (!empty($method_info['field']) && !empty($method_info['type'])) {
          unset($fields_to_generate[$method_info['type']][$method_info['field']]);
        }
      }
      
      foreach ($fields_to_generate as $type => $field_names) {
        foreach ($field_names as $field_name => $field_config) {
          $methods[] = $this->methodGenerator->generate($field_config, $type, $bundle_info['methods']);
        }
      }
      // Create or update the entity wrapper class.
      if (class_exists($bundle_info['class'])) {
        $this->bundleWrapperGenerator->update($entity_type, $bundle, $bundle_info['class'], $wrapper_info[$entity_type]['class'], $bundle_info['extension'], $methods);
      }
      else {
        $this->bundleWrapperGenerator->generate($entity_type, $bundle, $bundle_info['class'], $wrapper_info[$entity_type]['class'], $bundle_info['extension'], $methods);
      }
      
      // Generate methods for query class
      $methods = [];

      // Determine which fields don't already have setters and getters
      $fields_to_generate = [
        WrappersDelightMethod::CONDITION => $bundle_info['fields'],
        WrappersDelightMethod::SORT => $bundle_info['fields'],
        WrappersDelightMethod::EXISTS => $wrapper_info[$entity_type]['fields'],
        WrappersDelightMethod::NOT_EXISTS => $wrapper_info[$entity_type]['fields'],
      ];
      foreach ($bundle_info['query']['methods'] as $method => $method_info) {
        if (!empty($method_info['field']) && !empty($method_info['type'])) {
          unset($fields_to_generate[$method_info['type']][$method_info['field']]);
        }
      }

      foreach ($fields_to_generate as $type => $field_names) {
        foreach ($field_names as $field_name => $field_config) {
          $methods[] = $this->methodGenerator->generate($field_config, $type, $bundle_info['methods']);
        }
      }
      
      // Create or update the query wrapper class.
      if (class_exists($bundle_info['query']['class'])) {
        $this->bundleWrapperQueryGenerator->update($entity_type, $bundle, $bundle_info['query']['class'], $bundle_info['class'], $wrapper_info[$entity_type]['class'], $wrapper_info[$entity_type]['query']['class'], $bundle_info['query']['extension'], $methods);
      }
      else {
        $this->bundleWrapperQueryGenerator->generate($entity_type, $bundle, $bundle_info['query']['class'], $bundle_info['class'], $wrapper_info[$entity_type]['class'], $wrapper_info[$entity_type]['query']['class'], $bundle_info['query']['extension'], $methods);
      }
    }
    
    // Clear caches
    $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'all']);
  }

  /**
   * @param string $entity_type
   *
   * @return bool
   */
  protected function validEntityType($entity_type) {
    $definitions = $this->entityTypeManager->getDefinitions();
    return !empty($definitions[$entity_type]);
  }

  /**
   * @param string $entity_type
   * @param string $bundle
   *
   * @return bool
   */
  protected function validBundle($entity_type, $bundle) {
    return in_array($bundle, $this->getBundles($entity_type));
  }

  /**
   * @param string $entity_type
   *
   * @return string[]
   */
  protected function getBundles($entity_type) {
    $definitions = $this->entityTypeManager->getDefinitions();
    if (is_null($definitions[$entity_type]->getBundleEntityType())) {
      return [];
    }
    return array_keys($this->entityTypeManager->getStorage(
      $definitions[$entity_type]->getBundleEntityType())->loadMultiple()
    );
  }

  /**
   * @param string $extension
   * @param string $extension_path
   */
  protected function createModule($extension, $extension_path) {
    $extension_path = $this->validator->validateModulePath($extension_path, TRUE);
    $this->moduleGenerator->generate(
      'Wrappers Delight: Custom Bundle Wrappers',
      $extension,
      $extension_path,
      'Custom wrapper classes for entity bundles on this site.',
      '8.x',
      'Wrappers Delight',
      FALSE,
      FALSE,
      FALSE,
      ['wrappers_delight'],
      FALSE,
      NULL
    );
  }

  /**
   * @param string $class_name
   * @param string $extension
   */
  protected function loadClassFile($class_name, $extension) {
    if (class_exists($class_name)) {
      return;
    }
    // Look for disable class at target location
    $module_data = system_rebuild_module_data();
    if (!empty($module_data[$extension])) {
      $class_filename = str_replace('\\', '/', preg_replace('/^Drupal\\\\' . $extension . '/', $module_data[$extension]->subpath . '/src', $class_name)) . '.php';
      include_once $class_filename;
    }
  }

  /**
   * @param $dir
   * @param int $options
   *
   * @return bool
   */
  protected function prepareDirectory($dir, $options = FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS) {
    return file_prepare_directory($dir, $options);
  }

  /**
   * @return array
   */
  protected function getExistingWrapperClasses() {
    $classes = [];
    $plugins = $this->pluginManager->getDefintionsByType(WrappersDelight::TYPE_ENTITY);
    foreach ($plugins as $plugin) {
      $classes[$plugin['entity_type']] = [
        'class' => $plugin['class'],
        'extension' => $plugin['provider'],
        'bundles' => [],
        'query' => [],
      ];
    }
    $plugins = $this->pluginManager->getDefintionsByType(WrappersDelight::TYPE_BUNDLE);
    foreach ($plugins as $plugin) {
      $classes[$plugin['entity_type']]['bundles'][$plugin['bundle']] = [
        'class' => $plugin['class'],
        'extension' => $plugin['provider'],
        'query' => [],
      ];
    }
    $plugins = $this->pluginManager->getDefintionsByType(WrappersDelight::TYPE_QUERY_ENTITY);
    foreach ($plugins as $plugin) {
      $classes[$plugin['entity_type']]['query'] = [
        'class' => $plugin['class'],
        'extension' => $plugin['provider'],
      ];
    }
    $plugins = $this->pluginManager->getDefintionsByType(WrappersDelight::TYPE_QUERY_BUNDLE);
    foreach ($plugins as $plugin) {
      $classes[$plugin['entity_type']]['bundles'][$plugin['bundle']]['query'] = [
        'class' => $plugin['class'],
        'extension' => $plugin['provider'],
      ];
    }
    return $classes;
  }

  /**
   * @param string $extension
   * @param string $entity_type
   * @param string|NULL $bundle
   *
   * @return string
   */
  protected function getDefaultWrapperClassName($extension, $entity_type, $bundle = NULL) {
    $entity_class = $this->getEntityClass($entity_type);
    $name = [];
    if (!is_null($bundle)) {
      $name[] = implode('', array_map('ucfirst', explode('_', $bundle)));
    }
    $name[] = $entity_class->getShortName();
    $name[] = 'Wrapper';
    return 'Drupal\\' . $extension . '\\Plugin\\WrappersDelight\\' . $entity_class->getShortName() . '\\' . implode('', $name);
  }

  /**
   * @param string $extension
   * @param string $entity_type
   * @param string $bundle
   *
   * @return string
   */
  protected function getDefaultQueryClassName($extension, $entity_type, $bundle = NULL) {
    $entity_class = $this->getEntityClass($entity_type);
    $name = [];
    if (!is_null($bundle)) {
      $name[] = implode('', array_map('ucfirst', explode('_', $bundle)));
    }
    $name[] = $entity_class->getShortName();
    $name[] = 'Query';
    return 'Drupal\\' . $extension . '\\Plugin\\WrappersDelight\\' . $entity_class->getShortName() . '\\' . implode('', $name);
  }
  
  /**
   * @param string $extension
   * @param string $entity_type
   * @param string $bundle
   *
   * @return string
   */
  protected function getDefaultBundleWrapperClassName($extension, $entity_type, $bundle) {
    $entity_class = $this->getEntityClass($entity_type);
    $camelBundle = implode('', array_map('ucfirst', explode('_', $bundle))) . $entity_class->getShortName();
    return 'Drupal\\' . $extension . '\\Plugin\\WrappersDelight\\' . $entity_class->getShortName() . '\\' . $camelBundle;
  }

  /**
   * @param string $extension
   * @param string $entity_type
   * @param string $bundle
   *
   * @return string
   */
  protected function getDefaultQueryWrapperClassName($extension, $entity_type, $bundle) {
    $entity_class = $this->getEntityClass($entity_type);
    $camelBundle = implode('', array_map('ucfirst', explode('_', $bundle))) . $entity_class->getShortName() . 'Query';
    return 'Drupal\\' . $extension . '\\Plugin\\WrappersDelight\\' . $entity_class->getShortName() . '\\' . $camelBundle;
  }


  /**
   * @param $entity_type
   *
   * @return \ReflectionClass
   */
  protected function getEntityClass($entity_type) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_info */
    $entity_info = $this->entityTypeManager->getDefinition($entity_type);
    return new \ReflectionClass($entity_info->getClass());
  }

  /**
   * @param string $class_name
   * @param string|null $parent_class_name
   * @param string}null $grandparent_class_name
   *
   * @return array
   */
  protected function getMethods($class_name, $parent_class_name = NULL, $grandparent_class_name = NULL) {
    $methods = [];
    if (class_exists($class_name)) {
      $reader = new AnnotationReader();
      $class = new \ReflectionClass($class_name);
      foreach ($class->getMethods() as $method) {
        $methods[$method->getName()] = [
          'class' => $method->getDeclaringClass()->getName(),
        ];
        if ($method->getDeclaringClass()->getName() == $class_name) {
          $annotation = $reader->getMethodAnnotation($method, 'Drupal\wrappers_delight\Annotation\WrappersDelightMethod');
          if (!empty($annotation)) {
            $methods[$method->getName()]['field'] = $annotation->field;
            $methods[$method->getName()]['type'] = $annotation->type;
          };
        }
      }
    }
    elseif (!is_null($parent_class_name)) {
      return $this->getMethods($parent_class_name, $grandparent_class_name);
    }
    return $methods;
  }

  /**
   * @param string $entity_type
   * @param string|NULL $bundle
   *
   * @return \Drupal\field\Entity\FieldConfig[]
   */
  protected function getFields($entity_type, $bundle = NULL) {
    $fields = [];
    if (!is_null($bundle)) {
      // Get the bundle's field
      foreach ($this->entityFieldManager->getFieldDefinitions($entity_type, $bundle) as $definition) {
        $fields[$definition->getName()] = $definition;
      }
      // Remove the entity fields
      foreach ($this->entityFieldManager->getBaseFieldDefinitions($entity_type) as $definition) {
        unset($fields[$definition->getName()]);
      }
    }
    else {
      foreach ($this->entityFieldManager->getBaseFieldDefinitions($entity_type) as $definition) {
        $fields[$definition->getName()] = $definition;
      }
    }
    return $fields;
  }
  
  
}
