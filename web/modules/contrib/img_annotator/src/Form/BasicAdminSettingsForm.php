<?php

namespace Drupal\img_annotator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure basic settings for this site.
 */
class BasicAdminSettingsForm extends ConfigFormBase {

  protected $entity_type_manager;
  protected $config_factory;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactory $config_factory) {
    $this->entity_type_manager = $entity_type_manager;
    $this->config_factory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('entity_type.manager'),
        $container->get('config.factory')
        );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'img_annotator_basic_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['img_annotator.basic_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('img_annotator.basic_settings');
    $defaults = $config->get();

    // Select theme.
    $annoTheme = $defaults['anno_theme'];
    $themeOptions = array(
        'basic' => 'annotorious/css/annotorious.css',
        'dark' => 'annotorious/css/theme-dark/annotorious-dark.css',
    );

    $form['anno_theme'] = array(
        '#type' => 'radios',
        '#options' => $themeOptions,
        '#title' => 'Annotation Theme',
        '#default_value' => $annoTheme ? $annoTheme : 'basic',
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config_factory->getEditable('img_annotator.basic_settings');
    $annoTheme = $form_state->getValue('anno_theme');
    
    $config->set('anno_theme', $annoTheme)->save();
    
    parent::submitForm($form, $form_state);
  }

}
