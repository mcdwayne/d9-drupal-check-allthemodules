<?php

namespace Drupal\webform_as_block\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WebformAsBlockForm.
 *
 * @package Drupal\webform_as_block\Form
 */
class WebformAsBlockForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entity_storage;

  /**
   * @var \Drupal\Core\Routing\RouteBuilder
   */
  protected $router_builder;

  /**
   * Constructs new WebformAsBlockForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   * @param \Drupal\Core\Routing\RouteBuilderInterface
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityStorageInterface $entity_storage, RouteBuilderInterface $router_builder) {
    parent::__construct($config_factory);
    $this->entity_storage = $entity_storage;
    $this->router_builder = $router_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.manager')->getStorage('webform'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'webform_as_block.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_as_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webform_as_block.settings');

    // Get active webforms.
    $query = $this->entity_storage->getQuery();
    $query->condition('status', 'close', '<>');
    $wids = $query->execute();

    if (!$wids) {
      $form['no_webforms'] = [
        '#markup' => $this->t('No active webforms found.'),
      ];
    }
    else {
      // Description message.
      $form['description'] = [
        '#markup' => $this->t('Choose the webforms you want to expose as blocks.'),
      ];

      // Load webforms and populate checkboxes.
      $webforms = [];
      foreach ($this->entity_storage->loadMultiple($wids) as $webform) {
        $webforms[$webform->id()] = $webform->label();
      }

      $form['webform_list'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Available webforms'),
        '#options' => $webforms,
        '#default_value' => $config->get('webform_list') ?: [],
      ];
    }

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
    $config = $this->config('webform_as_block.settings');
    $config->set('webform_list', $form_state->getValue('webform_list'));
    $config->save();
    // Rebuild menu.
    $this->router_builder->rebuild();

    return parent::submitForm($form, $form_state);
  }

}
