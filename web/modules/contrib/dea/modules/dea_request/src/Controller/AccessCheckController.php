<?php
namespace Drupal\dea_request\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Session\AccountProxy;
use Drupal\dea\EntityAccessManager;
use Drupal\dea_request\Entity\AccessRequest;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Zend\Diactoros\Response\JsonResponse;

class AccessCheckController extends ControllerBase {

  /**
   * @var \Drupal\dea\EntityAccessManager
   */
  protected $entityAccessManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $account;

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('dea.entity.access'),
      $container->get('entity_type.manager'),
      $container->get('entity.query'),
      $container->get('current_user'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityAccessManager $entity_access_manager,
                              EntityTypeManagerInterface $entity_type_manager,
                              QueryFactory $query_factory,
                              AccountProxy $account,
                              Request $request) {
    $this->entityAccessManager = $entity_access_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->queryFactory = $query_factory;
    $this->account = $account;
    $this->request = $request;
  }

  /**
   * Retrieve a list of get entity-operation triples from the url and
   * check access.
   */
  public function checkAccess() {
    $response = [];

    foreach ($this->request->request->get('operations') as $triple) {
      list($entity_type, $entity_id, $operation) = explode(':', $triple);
      $result_key = $entity_type . ':' . $entity_id . ':' . $operation;
      $entity = $this->entityTypeManager
        ->getStorage($entity_type)
        ->load($entity_id);

      if (!$entity) {
        continue;
      }

      if ($entity->access($operation, $this->account)) {
        $response[$result_key] = 'accessible';
      }
      else if ($this->account->hasPermission('request dynamic entity access')) {
        $result = $this->queryFactory->get('dea_request')
          ->condition('entity_type', $entity_type)
          ->condition('entity_id', $entity_id)
          ->condition('operation', $operation)
          ->condition('uid', $this->account->id())
          ->execute();
        if ($request_id = array_pop($result)) {
          $request = $this->entityTypeManager->getStorage('dea_request')->load($request_id);
          if ($request->getStatus() == AccessRequest::OPEN) {
            $response[$result_key] = 'open';
          }
          else {
            $response[$result_key] = 'closed';
          }
        }
        else {
          $response[$result_key] = 'requestable';
        }
      }
      else {
        $response[$result_key] = 'closed';
      }
    }
    return new JsonResponse($response);
  }
}