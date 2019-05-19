<?php

namespace Drupal\smartsheet\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\smartsheet\SmartsheetClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Ask for confirmation to delete a smartsheet.
 */
class DeleteForm extends ConfirmFormBase {

  use StringTranslationTrait;

  /**
   * The Smartsheet client.
   *
   * @var \Drupal\smartsheet\SmartsheetClientInterface
   */
  protected $smartsheetClient;

  /**
   * The Smartsheet config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new DeleteForm object.
   *
   * @param \Drupal\smartsheet\SmartsheetClientInterface $smartsheet_client
   *   The Smartsheet client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(
    SmartsheetClientInterface $smartsheet_client,
    ConfigFactoryInterface $config_factory,
    MessengerInterface $messenger,
    RouteMatchInterface $route_match
  ) {
    $this->smartsheetClient = $smartsheet_client;
    $this->config = $config_factory->get('smartsheet.config');
    $this->messenger = $messenger;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('smartsheet.client'),
      $container->get('config.factory'),
      $container->get('messenger'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smartsheet.delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('smartsheet.overview');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you really want to delete this sheet?');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $id = $this->routeMatch->getParameter('id');

    if ($response = $this->smartsheetClient->delete("/sheets/$id")) {
      $this->messenger->addMessage($this->t('The sheet has been deleted.'));
    }

    $form_state->setRedirect('smartsheet.overview');
  }

}
