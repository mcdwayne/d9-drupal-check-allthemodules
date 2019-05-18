<?php

namespace Drupal\instapage\Form;

use Drupal\instapage\Api;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\ProxyClass\Routing\RouteBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;

/**
 * Class PageNewForm.
 *
 * @package Drupal\instapage\Form
 */
class PageNewForm extends FormBase {

  private $api;
  private $pagesConfig;
  private $config;
  private $routeBuilder;

  /**
   * @return string
   */
  public function getFormId() {
    return 'instapage_new_page';
  }

  /**
   * PageNewForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\instapage\Api $api
   * @param \Drupal\Core\ProxyClass\Routing\RouteBuilder $routeBuilder
   */
  public function __construct(ConfigFactoryInterface $config_factory, Api $api, RouteBuilder $routeBuilder) {
    $this->pagesConfig = $config_factory->getEditable('instapage.pages');
    $this->config = $config_factory->getEditable('instapage.settings');
    $this->api = $api;
    $this->routeBuilder = $routeBuilder;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('instapage.api'),
      $container->get('router.builder')
    );
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $pageLabels = $this->pagesConfig->get('page_labels');
    $pages = $this->pagesConfig->get('instapage_pages');

    // In the dropdown show only pages that don't have a path set.
    foreach ($pages as $i => $item) {
      if (array_key_exists($i, $pageLabels)) {
        unset($pageLabels[$i]);
      }
    }

    $form['page'] = [
      '#type' => 'select',
      '#title' => $this->t('Page'),
      '#required' => TRUE,
      '#options' => $pageLabels,
    ];
    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#required' => TRUE,
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
        'callback' => 'Drupal\instapage\Form\PageNewForm::closeModal',
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
    if ($op == 'submit') {
      $id = $form_state->getValue('page');
      $path = $form_state->getValue('path');
      $token = $this->config->get('instapage_user_token');

      // Send the edit command to the server.
      $this->api->editPage($id, $path, $token);

      // Rebuild the route cache to instantly apply path changes.
      $this->routeBuilder->rebuild();

      // Set the message and redirect back to the pages form.
      $labels = $this->pagesConfig->get('page_labels');
      $label = (array_key_exists($id, $labels) ? $labels[$id] : '');
      drupal_set_message($this->t('Path for @label has been saved.', ['@label' => $label]));
      $form_state->setRedirect('instapage.landing_pages');
    }
  }

}
