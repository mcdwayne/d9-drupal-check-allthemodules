<?php

namespace Drupal\instapage\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\instapage\Api;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\ProxyClass\Routing\RouteBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Ajax\CloseDialogCommand;

/**
 * Class PageDeleteForm.
 *
 * @package Drupal\instapage\Form
 */
class PageDeleteForm extends FormBase {

  private $config;
  private $label;
  private $api;
  private $id;
  private $routeBuilder;

  /**
   * @return string
   */
  public function getFormId() {
    return 'instapage_delete_page';
  }

  /**
   * PageDeleteForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   * @param \Drupal\Core\ProxyClass\Routing\RouteBuilder $routeBuilder
   * @param \Drupal\instapage\Api $api
   */
  public function __construct(ConfigFactory $config, RouteBuilder $routeBuilder, Api $api, RequestStack $request) {
    $this->pagesConfig = $config->getEditable('instapage.pages');
    $this->config = $config->get('instapage.settings');
    $this->id = $request->getCurrentRequest()->get('instapage_id');
    $this->routeBuilder = $routeBuilder;
    $this->api = $api;
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
    $labels = $this->pagesConfig->get('page_labels');
    $this->label = (array_key_exists($this->id, $labels) ? $labels[$this->id] : '');
    $form['label'] = [
      '#type' => 'item',
      '#markup' => $this->t('Are you sure you want to delete the path and unpublish the page @label?', ['@label' => $this->label]),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
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
        'callback' => 'Drupal\instapage\Form\PageDeleteForm::closeModal',
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
   * Delete confirmation callback.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $op = $trigger['#parents'][0];
    if ($op == 'submit') {
      $token = $this->config->get('instapage_user_token');

      // Unpublish the page throught the API.
      $this->api->editPage($this->id, '', $token, 0);

      // Rebuild the route cache to instantly apply path changes.
      $this->routeBuilder->rebuild();

      // Set the message and redirect back to the pages form.
      drupal_set_message($this->t('Path for @label has been removed.', ['@label' => $this->label]));
      $form_state->setRedirect('instapage.landing_pages');
    }
  }

}
