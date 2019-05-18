<?php

namespace Drupal\bibcite_export\Controller;

use Drupal\bibcite\Plugin\BibciteFormatInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class ExportController.
 *
 * @package Drupal\bibcite_export\Controller
 */
class ExportController extends ControllerBase {

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(SerializerInterface $serializer, EntityTypeManagerInterface $entity_type_manager) {
    $this->serializer = $serializer;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('serializer'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Serialize and create response contains file in selected format.
   *
   * @param array $entities
   *   Array of entities objects.
   * @param \Drupal\bibcite\Plugin\BibciteFormatInterface $bibcite_format
   *   Instance of format plugin.
   * @param null|string $filename
   *   Filename. Will be generated if not provided.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response object contains serialized reference data.
   */
  protected function processExport(array $entities, BibciteFormatInterface $bibcite_format, $filename = NULL) {
    if (!$filename) {
      $filename = $bibcite_format->getLabel();
    }

    $response = new Response();

    if ($result = $this->serializer->serialize($entities, $bibcite_format->getPluginId())) {
      $response->headers->set('Cache-Control', 'no-cache');
      $response->headers->set('Content-type', 'text/plain');
      $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.' . $bibcite_format->getExtension() . '";');

      $response->sendHeaders();

      $result = is_array($result) ? implode("\n", $result) : $result;
      $response->setContent($result);
    }

    return $response;
  }

  /**
   * Export entity to available export format.
   *
   * @param \Drupal\bibcite\Plugin\BibciteFormatInterface $bibcite_format
   *   Instance of format plugin.
   * @param string $entity_type
   *   Entity type identifier.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response object contains serialized reference data.
   */
  public function export(BibciteFormatInterface $bibcite_format, $entity_type, EntityInterface $entity) {
    if (!$bibcite_format->isExportFormat()) {
      throw new NotFoundHttpException();
    }

    $filename = vsprintf('%s-%s-%s', [
      $entity_type, $entity->id(), $bibcite_format->getLabel(),
    ]);
    return $this->processExport([$entity], $bibcite_format, $filename);
  }

  /**
   * Export multiple entities to available export formats.
   *
   * @param \Drupal\bibcite\Plugin\BibciteFormatInterface $bibcite_format
   *   Instance of format plugin.
   * @param string $entity_type
   *   Entity type identifier.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response object contains serialized reference data.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Throw 404 error if Id parameter is not provided or entities not loaded.
   */
  public function exportMultiple(BibciteFormatInterface $bibcite_format, $entity_type, Request $request) {
    if (!$bibcite_format->isExportFormat()) {
      throw new NotFoundHttpException();
    }

    $storage = $this->entityTypeManager->getStorage($entity_type);

    if (!$request->query->has('id')) {
      throw new NotFoundHttpException();
    }

    $ids = explode(' ', $request->query->get('id'));
    $entities = $storage->loadMultiple($ids);

    if (!$entities) {
      throw new NotFoundHttpException();
    }

    $filename = vsprintf('%s-%s', [
      $entity_type, $bibcite_format->getLabel(),
    ]);
    return $this->processExport($entities, $bibcite_format, $filename);
  }

}
