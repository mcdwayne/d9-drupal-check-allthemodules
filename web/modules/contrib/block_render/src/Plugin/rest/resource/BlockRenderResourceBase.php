<?php
/**
 * @file
 * Contains Drupal\block_render\Plugin\rest\resource\BlockRenderResource.
 */

namespace Drupal\block_render\Plugin\rest\resource;

use Drupal\block\BlockInterface;
use Drupal\block_render\BlockBuilderInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * REST endpoint base for rendered Block.
 */
abstract class BlockRenderResourceBase extends ResourceBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The block builder.
   *
   * @var \Drupal\block_render\BlockBuilderInterface
   */
  protected $builder;

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  protected $serializerFormats = array();

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountInterface $current_user,
    EntityManagerInterface $entity_manager,
    BlockBuilderInterface $builder,
    TranslationInterface $translator,
    RequestStack $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->entityManager = $entity_manager;
    $this->currentUser = $current_user;
    $this->builder = $builder;
    $this->stringTranslation = $translator;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('current_user'),
      $container->get('entity.manager'),
      $container->get('block_render.block_builder'),
      $container->get('string_translation'),
      $container->get('request_stack')
    );
  }


  /**
   * Gets the Current User session.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   Current User session object.
   */
  public function getCurrentUser() {
    return $this->currentUser;
  }

  /**
   * Gets the Entity Manager object.
   *
   * @return \Drupal\Core\Entity\EntityManagerInterface
   *   Entity Manager object.
   */
  public function getEntityManager() {
    return $this->entityManager;
  }

  /**
   * Gets the Builder service.
   *
   * @return \Drupal\block_render\BlockBuilderInterface
   *   Renderer object.
   */
  public function getBuilder() {
    return $this->builder;
  }

  /**
   * Gets the current request.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   Request Object.
   */
  public function getRequest() {
    return $this->request->getCurrentRequest();
  }

  /**
   * Gets the supported formats.
   *
   * @return array
   *   Supported Formats.
   */
  public function getFormats() {
    return $this->serializerFormats;
  }

}
