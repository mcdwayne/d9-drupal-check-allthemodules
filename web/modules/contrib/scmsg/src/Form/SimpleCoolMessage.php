<?php

namespace Drupal\simple_cool_message\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Theme\Registry;

/**
 * Class CoolMessage.
 */
class SimpleCoolMessage extends ConfigFormBase {

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
      'simple_cool_message.simple_cool_message',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_cool_message';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('simple_cool_message.simple_cool_message');

    $form['simple_coolmessage_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable simple cool message for drupal messages'),
      '#description' => $this->t('if selected simple cool message will be used for drupal messages.'),
      '#default_value' => $config->get('simple_coolmessage_enable'),
    ];
    $form['status_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Status color'),
      '#description' => $this->t('selected color is used for status message background'),
      '#default_value' => $config->get('status_color'),
    ];
    $form['info_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Info color'),
      '#description' => $this->t('selected color is used for info message background'),
      '#default_value' => $config->get('info_color'),
    ];
    $form['warning_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Warning color'),
      '#description' => $this->t('selected color is used for warning message background'),
      '#default_value' => $config->get('warning_color'),
    ];
    $form['error_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Error color'),
      '#description' => $this->t('selected color is used for error message background'),
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
    
	$msgenable = $this->config('simple_cool_message.simple_cool_message')->get('simple_coolmessage_enable');
	$formenable = $form_state->getValue('simple_coolmessage_enable');
	
    if($msgenable != $formenable) {
	  if($formenable == 1){
        $this->themeRegistry->reset();      
        drupal_set_message($this->t('Theme registry rebuild'));		  
	  }
    }

    $this->config('simple_cool_message.simple_cool_message')
      ->set('simple_coolmessage_enable', $form_state->getValue('simple_coolmessage_enable'))
      ->set('status_color', $form_state->getValue('status_color'))
      ->set('info_color', $form_state->getValue('info_color'))
      ->set('warning_color', $form_state->getValue('warning_color'))
      ->set('error_color', $form_state->getValue('error_color'))
      ->save();

  }

}
