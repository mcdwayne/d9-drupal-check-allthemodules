<?php

namespace Drupal\web_accessibility\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\web_accessibility\WebServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form to delete Web Accessibility Form.
 */
class DeleteServiceForm extends ConfirmFormBase {

  /**
   * The Web Service.
   *
   * @var string
   */
  protected $service;

  /**
   * The Web Service manager.
   *
   * @var \Drupal\web_accessibility\WebServiceInterface
   */
  protected $serviceManager;

  /**
   * Constructs a new DeleteService object.
   *
   * @param \Drupal\web_accessibility\WebServiceInterface $service_manager
   *   The Web Service manager.
   */
  public function __construct(WebServiceInterface $service_manager) {
    $this->serviceManager = $service_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('web_accessibility.service_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'web_accessibility_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want delete service `%name`?', ['%name' => $this->service['name']]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('web_accessibility.settings');
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $service_id
   *   The Web Accessibility Service record ID to delete.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $service_id = '') {
    if (!$this->service = $this->serviceManager->findById($service_id)) {
      throw new NotFoundHttpException();
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->serviceManager->deleteService($this->service['id']);
    $this->logger('user')
      ->notice('Deleted `%name`', ['%name' => $this->service['name']]);
    drupal_set_message($this->t('The web service `%name` was deleted.', ['%name' => $this->service['name']]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
