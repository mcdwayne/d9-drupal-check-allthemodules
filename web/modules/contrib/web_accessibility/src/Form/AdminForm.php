<?php

namespace Drupal\web_accessibility\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\web_accessibility\WebServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Web Accessibility settings form.
 */
class AdminForm extends FormBase {
  /**
   * Drupal\web_accessibility\WebServiceInterface.
   *
   * @var \Drupal\web_accessibility\WebServiceInterface
   */
  protected $serviceManager;

  /**
   * Constructs a new Web Accessibility Admin object.
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
    return new static($container->get('web_accessibility.service_manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'web_accessibility_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $rows = [];
    $header = [$this->t('Name'), $this->t('URL'), $this->t('Operations')];
    $result = $this->serviceManager->findAll();
    foreach ($result as $service) {
      $row = [];
      $row[] = $service->name;
      $row[] = $service->url;
      $links = [];
      $links['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('web_accessibility.delete_service', ['service_id' => $service->id]),
      ];
      $row[] = ['data' => ['#type' => 'operations', '#links' => $links]];
      $rows[] = $row;
    }

    $form['name'] = [
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#size' => 48,
      '#maxlength' => 255,
      '#default_value' => '',
      '#description' => $this->t('Enter service name.'),
    ];
    $form['url'] = [
      '#title' => $this->t('URL'),
      '#type' => 'textfield',
      '#size' => 48,
      '#maxlength' => 255,
      '#default_value' => '',
      '#description' => $this->t('Enter a valid URL.'),
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = ['#type' => 'submit', '#value' => $this->t('Add')];

    $form['services_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No accessibility services available.'),
      '#weight' => 120,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $name = trim($form_state->getValue('name'));
    if (empty($name)) {
      $form_state->setErrorByName('name', $this->t('Service name is required.'));
    }
    $url = trim($form_state->getValue('url'));
    if (empty($url)) {
      $form_state->setErrorByName('url', $this->t('Service URL is required.'));
    }
    elseif (!preg_match('%^https?://%i', $url)) {
      $form_state->setErrorByName('url', $this->t('Service URL is not valid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $url = trim($form_state->getValue('url'));
    $name = trim($form_state->getValue('name'));
    $this->serviceManager->addService($url, $name);
    drupal_set_message($this->t('The web accessibility service `%name` has been added.', ['%name' => $name]));
    $form_state->setRedirect('web_accessibility.settings');
  }

}
