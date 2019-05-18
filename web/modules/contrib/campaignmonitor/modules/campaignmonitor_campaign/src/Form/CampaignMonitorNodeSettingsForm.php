<?php

namespace Drupal\campaignmonitor_campaign\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\PermissionHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Cache\Cache;

/**
 * Node Access settings form.
 *
 * @package Drupal\campaignmonitor_campaign\Form
 */
class CampaignMonitorNodeSettingsForm extends FormBase {


  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * Constructs a new CampaignMonitorNodeSettingsForm.
   *
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   */
  public function __construct(PermissionHandlerInterface $permission_handler, EntityManagerInterface $entity_manager) {
    $this->permissionHandler = $permission_handler;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.permissions'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'campaignmonitor_campaign_node_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node_type = NULL) {
    $storage = [
      'node_type' => $node_type,
    ];

    $form_state->setStorage($storage);

    $form['lists'] = [
      '#type' => 'checkboxes',
      '#options' => campaignmonitor_get_list_options(),
      '#title' => t('Lists'),
      '#description' => t('The lists that this node type is published to'),
      '#default_value' => campaignmonitor_campaign_get_node_settings('lists', $node_type),
    ];

    $form['view_mode'] = [
      '#type' => 'select',
      '#options' => $this->entityManager->getViewModeOptionsByBundle('node', $node_type),
      '#description' => t('The view mode to use for the content of the campaign'),
      '#title' => $this->t('View mode'),
      '#default_value' => campaignmonitor_campaign_get_node_settings('view_mode', $node_type),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#weight' => 10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $storage = $form_state->getStorage();
    $node_type = $storage['node_type'];

    // Update content access settings.
    $settings = campaignmonitor_campaign_get_node_settings('all', $node_type);
    foreach (campaignmonitor_campaign_available_settings() as $setting) {
      if (isset($values[$setting])) {
        $settings[$setting] = is_array($values[$setting]) ? array_keys(array_filter($values[$setting])) : $values[$setting];
      }
    }

    campaignmonitor_campaign_set_node_settings($settings, $node_type);

    $caches = ['cache.menu', 'cache.render'];
    $module_handler = \Drupal::moduleHandler();
    // Flush entity and render persistent caches.
    $module_handler->invokeAll('cache_flush');
    foreach (Cache::getBins() as $service_id => $cache_backend) {
      if (in_array($cache_backend->_serviceId, $caches)) {
        $cache_backend->deleteAll();
      }
    }
    drupal_set_message(t('Your changes have been saved.'));
  }

}
