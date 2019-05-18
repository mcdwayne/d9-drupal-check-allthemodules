<?php

/**
 * @file
 * Definition of Drupal\accessibility\Form\AccessibilityTestFormController.
 */

namespace Drupal\accessibility\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityFormController;
use Drupal\Core\Language\Language;

/**
 * Base for controller for accessibility test edit forms.
 */
class AccessibilityTestFormController extends ContentEntityFormController {

  /**
   * The test storage.
   *
   * @var \Drupal\accessibility\AccessibilityTestStorageControllerInterface
   */
  protected $testStorage;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a new AccessibilityTestFormController.
   *
   * @param \Drupal\accessibility\AccessibilityTestStorageControllerInterface $test_storage
   *   The accessibility test storage.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(AccessibilityTestStorageControllerInterface $test_storage, ConfigFactory $config_factory) {
    $this->testStorage = $test_storage;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity')->getStorageController('accessibility_test'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $test = $this->entity;
    if($test->id()) {
      drupal_set_title(t('<em>Edit accessibility test</em> @name', array('@name' => $test->name->value)), PASS_THROUGH);
    }
    else {
      drupal_set_title(t('Create accessibility test'));
    }
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Test name'),
      '#default_value' => $test->name->value,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#weight' => -5,
    );
    
    $form['quail_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Machine name'),
      '#default_value' => $test->quail_name->value,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#weight' => -5,
    );
    
    $form['severity'] = array(
      '#type' => 'select',
      '#title' => $this->t('Severity'),
      '#required' => TRUE,
      '#default_value' => $test->severity->value,
      '#options' => array(
        ACCESSIBILITY_TEST_SEVERE => t('Severe'),
        ACCESSIBILITY_TEST_MODERATE => t('Moderate'),
        ACCESSIBILITY_TEST_SUGGESTION => t('Suggestion'),
      ),
      '#weight' => -5,
    );
    
    $form['status'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Test is active'),
      '#default_value' => $test->status->value,
      '#weight' => -5,
    );

    $form['test_id'] = array(
      '#type' => 'value',
      '#value' => $test->id(),
    );

    $form_state['redirect'] = ($test->isNew()) ? current_path() : 'accessibility-test/' . $test->id();

    $form += array('#submit' => array());

    return parent::form($form, $form_state, $test);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, array &$form_state) {
    $test = parent::buildEntity($form, $form_state);

    // Prevent leading and trailing spaces in test names.
    $test->name->value = trim($test->name->value);

    return $test;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $test = $this->entity;

    switch ($test->save()) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created new accessibility test %test.', array('%test' => $test->label())));
        watchdog('accessibility', 'Created new accessibility test %test.', array('%test' => $test->label()), WATCHDOG_NOTICE, l($this->t('edit'), 'accessibility/test/' . $test->id() . '/edit'));
        break;
      case SAVED_UPDATED:
        drupal_set_message($this->t('Updated accessibility test %test.', array('%test' => $test->label())));
        watchdog('accessibility', 'Updated accessibility test %test.', array('%test' => $test->label()), WATCHDOG_NOTICE, l($this->t('edit'), 'accessibility/test/' . $test->id() . '/edit'));
        // Clear the page and block caches to avoid stale data.
        Cache::invalidateTags(array('content' => TRUE));
        break;
    }

    $form_state['values']['test_id'] = $test->id();
    $form_state['test_id'] = $test->id();
    $form_state['redirect'] = array('accessibility-test/'. $test->id());
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, array &$form_state) {
    $destination = array();
    if (isset($_GET['destination'])) {
      $destination = drupal_get_destination();
      unset($_GET['destination']);
    }
    $form_state['redirect'] = array('accessibility-test/' . $this->entity->id() . '/delete', array('query' => $destination));
  }

}
