<?php

namespace Drupal\menu_entity_index\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\menu_entity_index\TrackerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a configuration form for administrative settings.
 *
 * @package Drupal\menu_entity_index\Form
 */
class ConfigurationForm extends FormBase {

  /**
   * The Menu Entity Index Tracker service.
   *
   * @var \Drupal\menu_entity_index\TrackerInterface
   */
  protected $tracker;

  /**
   * {@inheritdoc}
   */
  public function __construct(TrackerInterface $tracker) {
    $this->tracker = $tracker;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('menu_entity_index.tracker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'menu_entity_index_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->tracker->getConfiguration();
    $form['all_menus'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Track all menus'),
      '#default_value' => $config->get('all_menus'),
    ];
    $form['menus'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Tracked menus'),
      '#description' => $this->t('Select menus that should be included in menu entity index.'),
      '#options' => $this->tracker->getAvailableMenus(),
      '#default_value' => $config->get('menus'),
      '#states' => [
        'invisible' => [
          ':input[name="all_menus"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['entity_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Tracked entity types'),
      '#description' => $this->t('Select entity types that should be included in menu entity index.'),
      '#options' => $this->tracker->getAvailableEntityTypes(),
      '#default_value' => $config->get('entity_types'),
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];
    $form['actions']['rebuild'] = [
      '#type' => 'submit',
      '#value' => $this->t('Rebuild index'),
      '#button_type' => 'secondary',
      '#submit' => [[get_class($this), 'rebuildIndex']],
    ];
    $form['#theme'] = 'system_config_form';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->tracker->setConfiguration($form_state->getValues());
    $this->messenger()->addStatus($this->t('The configuration options have been saved.'));
  }

  /**
   * Form submission handler to rebuild the index.
   *
   * @ingroup form
   */
  public static function rebuildIndex(array &$form, FormStateInterface $form_state) {
    \Drupal::service('menu_entity_index.tracker')->setConfiguration($form_state->getValues(), TRUE);
    \Drupal::service('messenger')->addStatus(t('The index has been rebuilt.'));
  }

}
