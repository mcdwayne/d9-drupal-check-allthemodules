<?php

namespace Drupal\content_connected\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\content_connected\ContentConnectedManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AdminSettingsForm.
 *
 * @package Drupal\content_connected\Form
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The contentconnected manager.
   *
   * @var \Drupal\content_connected\ContentConnectedManagerInterface
   */
  protected $contentConnectedmanager;

  /**
   * Constructs a new AdminSettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configFactory service.
   * @param \Drupal\content_connected\ContentConnectedManagerInterface $content_connected_manager
   *   The contentconnectedManager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ContentConnectedManagerInterface $content_connected_manager) {
    parent::__construct($config_factory);
    $this->configFactory = $config_factory;
    $this->contentConnectedmanager = $content_connected_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'), $container->get('content_connected.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'content_connected.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_connected_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('content_connected.adminsettings');
    $form['content_connected_exclude_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Fields working with content connected'),
    ];
    $form['content_connected_exclude_fieldset']['content_connected_exclude_entityreffields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Excluded entity reference fields'),
      '#options' => $this->contentConnectedmanager->getEntityRefrenceFields(),
      '#default_value' => $config->get('content_connected_exclude_entityreffields'),
    ];

    $form['content_connected_exclude_fieldset']['content_connected_exclude_linkfields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Excluded Link fields'),
      '#options' => $this->contentConnectedmanager->getlinkFields(),
      '#default_value' => $config->get('content_connected_exclude_linkfields'),
    ];
    $form['content_connected_exclude_fieldset']['content_connected_exclude_longtextfields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Excluded long text fields'),
      '#options' => $this->contentConnectedmanager->getLongTextFields(),
      '#default_value' => $config->get('content_connected_exclude_longtextfields'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('content_connected.adminsettings')
      ->set('content_connected_exclude_fieldset', $form_state->getValue('content_connected_exclude_fieldset'))
      ->set('content_connected_exclude_entityreffields', $form_state->getValue('content_connected_exclude_entityreffields'))
      ->set('content_connected_exclude_linkfields', $form_state->getValue('content_connected_exclude_linkfields'))
      ->set('content_connected_exclude_longtextfields', $form_state->getValue('content_connected_exclude_longtextfields'))
      ->save();
  }

}
