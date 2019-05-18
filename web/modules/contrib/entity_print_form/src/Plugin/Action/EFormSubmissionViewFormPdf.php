<?php

namespace Drupal\entity_print_form\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Redirects to view form as a PDF.
 *
 * @Action(
 *   id = "eform_submission_view_form_pdf",
 *   label = @Translation("View form for submission as PDF"),
 *   type = "eform_submission"
 * )
 */
class EFormSubmissionViewFormPdf extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * Entity print form builder service.
   */
  protected $entityPrintFormService;

  /**
   * Entity print engine manager.
   */
  protected $printEngineManager;

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $account->hasPermission('access entity print form');
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $print_engine = $this->printEngineManager->createSelectedInstance('pdf');
    $entity = $object;

    // Maybe just better to redirect to the controller that does this?
    return (new StreamedResponse(function () use ($entity, $print_engine) {
      // The pdf is sent straight to the browser.
      $this->entityPrintFormService->deliverPrintable([$entity], $print_engine, FALSE, FALSE);
    }))->send();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_print_form.print_form_builder'),
      $container->get('plugin.manager.entity_print.print_engine')
    );
  }

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user, $entity_print_form_service, $print_engine_manager) {
    $this->currentUser = $current_user;
    $this->entityPrintFormService = $entity_print_form_service;
    $this->printEngineManager = $print_engine_manager;

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

}
