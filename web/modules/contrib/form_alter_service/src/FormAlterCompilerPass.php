<?php

namespace Drupal\form_alter_service;

use Drupal\form_alter_service\Form\FormAlter;
use Drupal\form_alter_service\Form\FormBuilderAlterInterface;
use Drupal\form_alter_service\Annotation\FormSubmit;
use Drupal\form_alter_service\Annotation\FormValidate;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Reflection\Validator\Annotation\ReflectionValidatorAnnotationReader;

/**
 * Register the form alter services.
 */
class FormAlterCompilerPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   *
   * @example
   * @code
   * form_alter.node_news_form:
   *   class: Drupal\my_module\NodeNewsFormAlter
   *   arguments: ['node_news_form']
   *   tags:
   *     - { name: form_alter, priority: 10 }
   * @endcode
   */
  public function process(ContainerBuilder $container) {
    if ($container->hasDefinition(FormAlter::SERVICE_ID)) {
      $form_builder = $container->getDefinition('form_builder');
      $translation = $container->getDefinition('string_translation');
      $form_alter = $container->getDefinition(FormAlter::SERVICE_ID);
      $services = [];

      foreach ($container->findTaggedServiceIds('form_alter') as $service_id => $tags) {
        $alter = $container
          ->getDefinition($service_id)
          // Set string translation service as it required by the injecting
          // trait (see description by the reference below).
          /* @see \Drupal\Core\StringTranslation\StringTranslationTrait::setStringTranslation() */
          ->addMethodCall('setStringTranslation', [$translation]);

        $locator = $alter->getArgument(0);

        if (!is_string($locator)) {
          throw new \InvalidArgumentException(sprintf('The argument 1 for "%s" service must be a string (ID of a form, base ID or "match" - special keyword to compute operability in runtime).', $service_id));
        }

        foreach ($tags as $attributes) {
          $attributes += ['priority' => 0];

          $services[$locator][] = [$attributes['priority'], $alter];
        }
      }

      if (!empty($services)) {
        foreach ($this->processServices($services) as $arguments) {
          /* @see \Drupal\form_alter_service\Form\FormAlter::registerService() */
          $form_alter->addMethodCall('registerService', $arguments);
        }
      }

      if (is_a($form_builder->getClass(), FormBuilderAlterInterface::class, TRUE)) {
        $form_builder->addMethodCall('setFormAlter', [$form_alter]);
      }
    }
  }

  /**
   * Process registered alter services.
   *
   * @param \Symfony\Component\DependencyInjection\Definition[][] $collection
   *   A collection of registered services.
   *
   * @return \Generator
   *   Every item contains a locator of the form and its handlers.
   */
  protected function processServices(array $collection): \Generator {
    $reader = new ReflectionValidatorAnnotationReader();
    $reader->addNamespace('Drupal\form_alter_service\Annotation');

    // Clear the annotation loaders of any previous annotation classes.
    AnnotationRegistry::reset();
    // Register the namespaces of classes that can be used for annotations.
    AnnotationRegistry::registerLoader('class_exists');

    foreach ($collection as $locator => $services) {
      array_multisort($collection[$locator], SORT_ASC);

      foreach ($services as list($priority, $service)) {
        $handlers = $this->getServiceHandlers($service, $reader);

        foreach ($handlers as $type => $strategies) {
          foreach ($strategies as $strategy => $items) {
            array_multisort($handlers[$type][$strategy], SORT_ASC);
          }
        }

        yield [$locator, $service->addMethodCall('setHandlers', [$handlers])];
      }
    }
  }

  /**
   * Seeks handlers in given service.
   *
   * @param \Symfony\Component\DependencyInjection\Definition $service
   *   A service to scan.
   * @param \Doctrine\Common\Annotations\Reader $reader
   *   Annotation reader.
   *
   * @return array[][][]
   *   The list of handlers of the service.
   *
   * @see \Drupal\form_alter_service\FormAlterBase::setHandlers()
   */
  protected function getServiceHandlers(Definition $service, Reader $reader): array {
    $handlers = [];
    $errors = [];

    foreach ((new \ReflectionClass($service->getClass()))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
      foreach ([FormValidate::class, FormSubmit::class] as $annotation) {
        try {
          /* @var \Drupal\form_alter_service\Annotation\FormHandler $handler */
          $handler = $reader->getMethodAnnotation($method, $annotation);

          if (NULL !== $handler) {
            $handlers[(string) $handler][$handler->strategy][] = [$handler->priority, $method->name];

            // A single method cannot be validation and submission handler in
            // the same time, so break here.
            break;
          }
        }
        catch (\Exception $e) {
          $errors[] = $e->getMessage();
        }
      }
    }

    if (!empty($errors)) {
      throw new \LogicException(implode(PHP_EOL, $errors));
    }

    return $handlers;
  }

}
