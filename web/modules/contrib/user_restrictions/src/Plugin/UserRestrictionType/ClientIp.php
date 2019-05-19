<?php

namespace Drupal\user_restrictions\Plugin\UserRestrictionType;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a restriction type by client IP.
 *
 * @UserRestrictionType(
 *   id = "client_ip",
 *   label = "Client IP",
 *   weight = 0
 * )
 */
class ClientIp extends UserRestrictionTypeBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_manager, RequestStack $request_stack, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $logger);
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity_type.manager'), $container->get('request_stack'), $container->get('logger.channel.user_restrictions'));
  }

  /**
   * {@inheritdoc}
   */
  public function matches(array $data) {
    $client_ip = $this->requestStack->getCurrentRequest()->getClientIp();
    $restriction = parent::matchesValue($client_ip);
    if ($restriction) {
      $this->logger->notice('Restricted client IP %client_ip matching %restriction has been blocked.', ['%client_ip' => $client_ip, '%restriction' => $restriction->toLink($restriction->label(), 'edit-form')]);
    }
    return $restriction;
  }

  /**
   * {@inheritdoc}
   */
  public function getErrorMessage() {
    return $this->t('Accessing the site from the IP %value is not allowed.', ['%value' => $this->requestStack->getCurrentRequest()->getClientIp()]);
  }

}
