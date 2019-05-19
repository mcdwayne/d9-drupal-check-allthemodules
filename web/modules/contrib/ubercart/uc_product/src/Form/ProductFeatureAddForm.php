<?php

namespace Drupal\uc_product\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the form for adding a product feature to the features tab.
 */
class ProductFeatureAddForm extends FormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Form constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_product_feature_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $form['#node'] = $node;

    foreach ($this->moduleHandler->invokeAll('uc_product_feature') as $feature) {
      $options[$feature['id']] = $feature['title'];
    }
    if (isset($options)) {
      ksort($options);

      $form['feature'] = [
        '#type' => 'select',
        '#title' => $this->t('Add a new feature'),
        '#options' => $options,
      ];

      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add'),
      ];
    }
    else {
      $form['feature'] = [
        '#markup' => $this->t('There are no product features are available to add. To add a feature you must first enable the module that provides that feature.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('uc_product.feature_add', [
      'node' => $form['#node']->id(),
      'fid' => $form_state->getValue('feature'),
    ]);
  }

}
