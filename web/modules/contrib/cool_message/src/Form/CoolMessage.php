<?php

namespace Drupal\cool_message\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Theme\Registry;

/**
 * Class CoolMessage.
 */
class CoolMessage extends ConfigFormBase {

  /**
   * Drupal\Core\Theme\Registry definition.
   *
   * @var \Drupal\Core\Theme\Registry
   */
  protected $themeRegistry;

  /**
   * Constructs a new CoolMessage object.
   */
  public function __construct(
      ConfigFactoryInterface $config_factory,
      Registry $theme_registry
    ) {
    parent::__construct($config_factory);
    $this->themeRegistry = $theme_registry;
  }

  /**
   * Create method.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('theme.registry')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cool_message.cool_message',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cool_message';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cool_message.cool_message');

    $form['coolmessage_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable cool message for Drupal messages'),
      '#description' => $this->t('If selected cool message will be used for drupal messages.'),
      '#default_value' => $config->get('coolmessage_enable'),
    ];
    $form['coolmessage_position'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fixed on top'),
      '#description' => $this->t('If selected all message will be fixed on the top of a page.'),
      '#default_value' => $config->get('coolmessage_position'),
    ];
    $form['status_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Status color'),
      '#description' => $this->t('Selected color is used for status message background'),
      '#default_value' => $config->get('status_color'),
    ];
    $form['info_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Info color'),
      '#description' => $this->t('Selected color is used for info message background'),
      '#default_value' => $config->get('info_color'),
    ];
    $form['warning_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Warning color'),
      '#description' => $this->t('Selected color is used for warning message background'),
      '#default_value' => $config->get('warning_color'),
    ];
    $form['error_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Error color'),
      '#description' => $this->t('Selected color is used for error message background'),
      '#default_value' => $config->get('error_color'),
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

    if ($this->config('cool_message.cool_message')->get('coolmessage_enable') != $form_state->getValue('coolmessage_enable')) {
      $this->themeRegistry->reset();
      drupal_set_message($this->t('Theme registry rebuild'));
    }

    $this->config('cool_message.cool_message')
      ->set('coolmessage_enable', $form_state->getValue('coolmessage_enable'))
      ->set('coolmessage_position', $form_state->getValue('coolmessage_position'))
      ->set('status_color', $form_state->getValue('status_color'))
      ->set('info_color', $form_state->getValue('info_color'))
      ->set('warning_color', $form_state->getValue('warning_color'))
      ->set('error_color', $form_state->getValue('error_color'))
      ->save();
  }

}
