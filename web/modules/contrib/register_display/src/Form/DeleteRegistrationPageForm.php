<?php

namespace Drupal\register_display\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\register_display\RegisterDisplayServices;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DeleteRegistrationPageForm.
 *
 * @package Drupal\register_display\Form
 */
class DeleteRegistrationPageForm extends ConfigFormBase {
  protected $services;

  /**
   * {@inheritdoc}
   */
  public function __construct(RegisterDisplayServices $services,
    ConfigFactoryInterface $config_factory) {

    parent::__construct($config_factory);
    $this->services = $services;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('register_display.services'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'register_display_admin_delete_registration_page';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'register_display.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $roleId = NULL) {
    // Get page config.
    $pageConfig = $this->services->getRegistrationPages($roleId);
    if (empty($pageConfig)) {
      $form['message'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Request is not valid.'),
      ];
      return $form;
    }

    $form['roleId'] = [
      '#type' => 'value',
      '#value' => $roleId,
    ];

    $form['message'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Are you sure you want to delete <strong>
        @roleName</strong> register page?', [
          '@roleName' => $pageConfig['roleName'],
        ]),
    ];

    $form['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('No'),
    ];

    $form['approved'] = [
      '#type' => 'submit',
      '#value' => $this->t('Yes'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $action = $form_state->getTriggeringElement()['#parents'][0];

    switch ($action) {
      case 'approved':
        $roleId = $form_state->getValue('roleId');
        $this->services->deleteRegisterDisplayPage($roleId);
        break;
    }
    $form_state->setRedirect('register_display.admin_index');
  }

}
