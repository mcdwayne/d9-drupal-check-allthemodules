<?php

namespace Drupal\bcubed\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure bcubed settings for this site.
 */
class BcubedAdminSettingsForm extends ConfigFormBase {

  /**
   * Bcubed Event Plugin Manager.
   *
   * @var \Drupal\bcubed\EventManager
   */
  protected $eventManager;

  /**
   * Bcubed Action Plugin Manager.
   *
   * @var \Drupal\bcubed\ActionManager
   */
  protected $actionManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new SettingsForm object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $event_manager
   *   Bcubed Event Plugin Manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $action_manager
   *   Bcubed Action Plugin Manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(PluginManagerInterface $event_manager, PluginManagerInterface $action_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->eventManager = $event_manager;
    $this->actionManager = $action_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.bcubed.event'),
      $container->get('plugin.manager.bcubed.action'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bcubed_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['bcubed.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;

    $config = $this->config('bcubed.settings');

    $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();

    $form['content_types'] = [
      '#type' => 'details',
      '#title' => 'Content Types',
      '#open' => TRUE,
    ];

    foreach ($content_types as $type => $value) {
      $field = FieldConfig::loadByName('node', $type, 'bcubed');
      $field_exists = !empty($field);
      $form['content_types'][$type] = [
        '#type' => 'checkbox',
        '#title' => $value->label(),
        '#description' => 'Enable bcubed ad selection for this content type',
        '#default_value' => $field_exists ? 1 : 0,
        '#disabled' => $field_exists,
      ];

      if ($field_exists) {
        $url = Url::fromRoute('entity.node.field_ui_fields', ['node_type' => $type]);
        $form['content_types'][$type]['#description'] = $this->t('Bcubed ad selection is enabled for this content type. To remove bcubed information from all nodes of this type, delete the @field-label field from the content type on the <a href="@manage-fields">manage fields page</a>.', ['@field-label' => $field->label(), '@manage-fields' => $url->toString()]);
      }

    }

    $form['default_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Bcubed Ad Type'),
      '#description' => $this->t('The default ad type to apply when no other has been specified'),
      '#options' => ['brand' => 'Brand', 'buy' => 'Buy', 'behave' => 'Behave'],
      '#default_value' => $config->get('default_type'),
    ];

    $form['clear_cache'] = [
      '#type' => 'details',
      '#title' => $this->t('Rebuild Caches'),
      '#description' => $this->t('Clears and rebuilds all BCubed generated strings, JS files, and custom routes.'),
      '#open' => TRUE,
    ];

    $form['clear_cache']['clear'] = [
      '#type' => 'submit',
      '#value' => $this->t('Rebuild BCubed Cached Data'),
      '#submit' => ['::submitCacheClear'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->disableCache();
    $config = $this->config('bcubed.settings');

    foreach ($form_state->getValue('content_types') as $key => $value) {
      // Add field to type.
      $field_storage = FieldStorageConfig::loadByName('node', 'bcubed');
      $field = FieldConfig::loadByName('node', $key, 'bcubed');
      if (empty($field) && $value) {
        $field = FieldConfig::create([
          'field_storage' => $field_storage,
          'bundle' => $key,
          'label' => 'Bcubed Ad Type',
          'default_value' => [0 => ['value' => 'brand']],
        ]);
        $field->save();

        // Assign widget settings for the 'default' form mode.
        entity_get_form_display('node', $key, 'default')
          ->setComponent('bcubed', [
            'type' => 'options_select',
          ])
          ->save();

        // Assign display settings for the 'default' view mode.
        entity_get_display('node', $key, 'default')
          ->removeComponent('bcubed')
          ->save();
      }
    }

    $config->set('default_type', $form_state->getValue('default_type'));

    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Submit handler for cache clear button.
   */
  public function submitCacheClear(array &$form, FormStateInterface $form_state) {
    // Clear all bcubed data.
    bcubed_rebuild_cache([
      'js' => TRUE,
      'generated_strings' => TRUE,
      'routes' => TRUE,
    ]);
    drupal_set_message($this->t('All BCubed data has been successfully rebuilt.'));
  }

}
