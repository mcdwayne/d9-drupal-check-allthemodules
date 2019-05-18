<?php
/**
 * @file
 * Contains \Drupal\collect\Processor\Postprocessor.
 */

namespace Drupal\collect\Processor;

use Drupal\collect\CollectContainerInterface;
use Drupal\collect\Model\ModelManagerInterface;
use Drupal\collect\TypedData\TypedDataProvider;

/**
 * Applies post-processing to containers.
 */
class Postprocessor {

  /**
   * The model plugin manager.
   *
   * @var \Drupal\collect\Model\ModelManagerInterface
   */
  protected $modelManager;

  /**
   * The injected container Typed Data provider.
   *
   * @var \Drupal\collect\TypedData\TypedDataProvider
   */
  protected $typedDataProvider;

  /**
   * Constructs a Postprocessor.
   *
   * @param \Drupal\collect\Model\ModelManagerInterface $model_manager
   *   Collect model plugin manager.
   * @param \Drupal\collect\TypedData\TypedDataProvider $typed_data_provider
   *   Collect Typed Data provider service.
   */
  public function __construct(ModelManagerInterface $model_manager, TypedDataProvider $typed_data_provider) {
    $this->modelManager = $model_manager;
    $this->typedDataProvider = $typed_data_provider;
  }

  /**
   * Processes a single container, using configured processors.
   *
   * @param \Drupal\collect\CollectContainerInterface $container
   *   The container to process.
   */
  public function process(CollectContainerInterface $container) {
    $model = $this->modelManager->loadModelByUri($container->getSchemaUri());
    if (empty($model)) {
      return;
    }

    // Get the container data to process.
    $data = $this->typedDataProvider->getTypedData($container);

    // Invoke each processor.
    $context = array();
    foreach ($model->getProcessorsPluginCollection() as $processor) {
      /** @var \Drupal\collect\Processor\ProcessorInterface $processor */
      $processor->process($data, $context);
    }
  }

}
