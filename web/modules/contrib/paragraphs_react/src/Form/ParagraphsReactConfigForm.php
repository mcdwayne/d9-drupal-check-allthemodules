<?php

namespace Drupal\paragraphs_react\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\paragraphs_react\ParagraphsReactManager;

/**
 * Class ParagraphsReactConfigForm.
 */
class ParagraphsReactConfigForm extends ConfigFormBase {

  /**
   * Drupal\paragraphs_react\ParagraphsReactManager definition.
   *
   * @var \Drupal\paragraphs_react\ParagraphsReactManager
   */
  protected $paragraphsReactManager;
  /**
   * Constructs a new ParagraphsReactConfigForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
      ParagraphsReactManager $paragraphs_react_manager
    ) {
    parent::__construct($config_factory);
        $this->paragraphsReactManager = $paragraphs_react_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('paragraphs_react.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'paragraphs_react.paragraphsreactconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'paragraphs_react_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('paragraphs_react.paragraphsreactconfig');
    $form['allow_paragraphs_react_to_load'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Paragraphs React to load the ReactJS and ReactDOM libraries'),
      '#description' => $this->t('You can use this module to load ReactJS and ReactDOM or you can disable this option to load the libraries on your own'),
      '#default_value' => $config->get('allow_paragraphs_react_to_load'),
    ];
    $form['react_library_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('React Library URL'),
      '#description' => $this->t('Insert the URL to the ReactJS Library if you want this module to load React'),
      '#maxlength' => 128,
      '#size' => 128,
      '#default_value' => $config->get('react_library_url'),
    ];
    $form['reactdom_library_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ReactDOM Library URL'),
      '#description' => $this->t('Insert the URL to the ReactDOM Library if you want this module to load ReactDOM'),
      '#maxlength' => 128,
      '#size' => 128,
      '#default_value' => $config->get('reactdom_library_url'),
    ];
    $form['babel_transpiler_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Babel stand-alone Library URL'),
      '#description' => $this->t('Insert the URL to the Babel stand-alone Library if you want this module to load Babel stand-alone'),
      '#maxlength' => 128,
      '#size' => 128,
      '#default_value' => $config->get('babel_transpiler_url'),
    ];
    $this->paragraphsReactManager->loadAllMarkup(FALSE,$form);
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

    $this->config('paragraphs_react.paragraphsreactconfig')
      ->set('react_library_url', $form_state->getValue('react_library_url'))
      ->set('reactdom_library_url', $form_state->getValue('reactdom_library_url'))
      ->set('allow_paragraphs_react_to_load', $form_state->getValue('allow_paragraphs_react_to_load'))
      ->set('babel_transpiler_url', $form_state->getValue('babel_transpiler_url'))
      ->save();
  }

}
