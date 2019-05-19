<?php

namespace Drupal\xero\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\xero\XeroQuery;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Xero autocomplete controller.
 */
class XeroAutocompleteController implements ContainerInjectionInterface {

  /**
   * Create a new instance of the controller with dependency injection.
   *
   * @param $query
   *   The xero query class.
   * @param $typedDataManager
   *   The typed data manager.
   */
  public function __construct(XeroQuery $query, TypedDataManager $typedDataManager) {
    $this->query = $query;
    $this->typedDataManager = $typedDataManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('xero.query'),
      $container->get('typed_data_manager')
    );
  }

  /**
   * Controller method.
   *
   * @param $request
   *   The Symfony Request object.
   * @param $type
   *   The Xero type.
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response of potential guid and label matches as key/value pairs.
   */
  public function autocomplete(Request $request, $type) {
    $search = $request->query->get('q');
    $matches = NULL;

    $definition = $this->typedDataManager->createDataDefinition($type);
    $class = $definition->getClass();

    $this->query
      ->setType($type)
      ->setMethod('get')
      ->setFormat('xml');

    if ($class::$label) {
      $this->query->addCondition($class::$label, $search, 'StartsWith');
    }
    else {
      $this->query->setId($search);
    }

    $items = $this->query->execute();

    if (!empty($items)) {
      $matches = [];
      foreach ($items as $item) {
        $key = $item->get($class::$guid_name)->getValue();
        $label = $class::$label ? $item->get($class::$label)->getValue() : $key;
        $key .= $key !== $label ? ' (' . $label . ')' : '';

        $matches[] = array('value' => $key, 'label' => SafeMarkup::checkPlain($label));
      }
    }

    return new JsonResponse($matches);
  }
}
