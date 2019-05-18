<?php

namespace Drupal\outvoice_ui\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Configure OutVoice settings for this site.
 */
class OutvoiceSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * This function is a construct.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Defines the interface for a configuration object factory.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'outvoice_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['outvoice.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('outvoice.settings');

    $options = [];
    $nodeTypes = $this->entityTypeManager
      ->getStorage('node_type')
      ->loadMultiple();
    foreach ($nodeTypes as $type => $value) {
      $options[$type] = $value->label();
    }

    $form['blurb'] = [
      '#type' => 'item',
      '#markup' => '<p>' . $this->t('Adding OutVoice to a content type will enable an interface on the node/add/* form that lets you pay contributors. 
Anyone who wishes to use the interface will require an OutVoice account, as will anyone who wishes to be paid. They can be invited via the <a href=":platform">OutVoice Platform</a>.', [':platform' => 'https://outvoice.com']) . '</p>'
    ];

    $form['content_types'] = [
      '#default_value' => $config->get('content_types'),
      '#type' => 'checkboxes',
      '#title' => $this->t('Add OutVoice payment options to the following Content Types:'),
      '#options' => $options,
      '#open' => TRUE,
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
    $config = $this->config('outvoice.settings');
    $content_types = [];
    foreach ($form_state->getValue('content_types') as $key => $value) {
      if ($value) {
        $content_types[] = $value;
      }
    }
    $config
      ->set('content_types', $content_types)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
