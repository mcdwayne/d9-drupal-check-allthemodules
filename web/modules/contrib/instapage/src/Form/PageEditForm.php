<?php

namespace Drupal\instapage\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\instapage\Api;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\ProxyClass\Routing\RouteBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class PageEditForm.
 *
 * @package Drupal\instapage\Form
 */
class PageEditForm extends FormBase {

  private $config;
  private $label;
  private $api;
  private $id;
  private $routeBuilder;
  private $pagesConfig;

  /**
   * @return string
   */
  public function getFormId() {
    return 'instapage_edit_page';
  }

  /**
   * PageEditForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   * @param \Drupal\Core\ProxyClass\Routing\RouteBuilder $routeBuilder
   * @param \Drupal\instapage\Api $api
   */
  public function __construct(ConfigFactory $config, RouteBuilder $routeBuilder, Api $api, RequestStack $request) {
    $this->pagesConfig = $config->getEditable('instapage.pages');
    $this->config = $config->get('instapage.settings');
    $this->routeBuilder = $routeBuilder;
    $this->api = $api;
    $this->id = $request->getCurrentRequest()->get('instapage_id');
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('router.builder'),
      $container->get('instapage.api'),
      $container->get('request_stack')
    );
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $pages = $this->pagesConfig->get('instapage_pages');
    $labels = $this->pagesConfig->get('page_labels');
    $this->label = (array_key_exists($this->id, $labels) ? $labels[$this->id] : '');

    $form['label'] = [
      '#type' => 'item',
      '#title' => 'Page Label',
      '#markup' => $this->label,
    ];
    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#required' => TRUE,
      '#default_value' => (isset($pages[$this->id]) ? $pages[$this->id] : ''),
      '#description' => $this->t('Without leading forward slash.'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    $form['cancel'] = [
      '#type' => 'button',
      '#limit_validation_errors' => [],
      '#value' => $this->t('Cancel'),
      '#attributes' => [
        'class' => [
          'btn',
        ],
      ],
      '#ajax' => [
        'callback' => 'Drupal\instapage\Form\PageEditForm::closeModal',
        'event' => 'click',
      ],
    ];
    return $form;
  }

  /**
   * Ajax callback to close the modal dialog.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public static function closeModal(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild(FALSE);
    $response = new AjaxResponse();
    $response->addCommand(new CloseDialogCommand());
    return $response;
  }

  /**
   * Form submit callback.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $op = $trigger['#parents'][0];
    // 'Save' clicked.
    if ($op == 'submit') {
      $path = $form_state->getValue('path');
      $token = $this->config->get('instapage_user_token');

      // Send the changes through the API.
      $this->api->editPage($this->id, $path, $token);

      // Rebuild the route cache to instantly apply path changes.
      $this->routeBuilder->rebuild();

      // Set the message and redirect back to the pages form.
      drupal_set_message($this->t('Path for @label has been saved.', ['@label' => $this->label]));
      $form_state->setRedirect('instapage.landing_pages');
    }
  }

}
