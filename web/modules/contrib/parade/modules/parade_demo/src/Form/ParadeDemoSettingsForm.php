<?php

namespace Drupal\parade_demo\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Config\FileStorage;

/**
 * Defines a form that configures Parade settings.
 */
class ParadeDemoSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The render cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $renderCache;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('cache.render')
    );
  }

  /**
   * ParadeDemoSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $render_cache
   *   The render cache service.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    EntityTypeManagerInterface $entityTypeManager,
    CacheBackendInterface $render_cache
  ) {
    parent::__construct($configFactory);

    $this->entityTypeManager = $entityTypeManager;
    $this->renderCache = $render_cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'parade_demo_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['parade_demo.settings'];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $settings = $this->config('parade_demo.settings')->get('bundles');

    $header = [
      'bundle' => $this->t('Content type'),
      'enabled' => $this->t('Parade feature'),
      'css_disabled' => $this->t('Parade demo css'),
      // 'menu' => $this->t('Parade One-page menu'),.
    ];

    $form['bundles'] = [
      '#type' => 'table',
      '#header' => $header,
      '#empty' => $this->t('No content types available. <a href=":link">Add content type</a>.', [
        ':link' => Url::fromRoute('node.type_add')
          ->toString(),
      ]),
    ];

    $contentTypes = $this->entityTypeManager
      ->getStorage('node_type')
      ->loadMultiple();
    if (!empty($contentTypes)) {
      foreach ($contentTypes as $contentType) {
        $contentTypeId = $contentType->id();
        $form['bundles'][$contentTypeId] = [
          'bundle' => ['#markup' => $contentType->label()],
          'enabled' => [
            '#title' => $this->t('Enabled'),
            '#type' => 'checkbox',
            '#default_value' => isset($settings[$contentTypeId]) ? 1 : 0,
          ],
          'css_disabled' => [
            '#title' => $this->t('Disabled'),
            '#type' => 'checkbox',
            '#default_value' => (isset($settings[$contentTypeId]) && isset($settings[$contentTypeId]['css_disabled']) && $settings[$contentTypeId]['css_disabled']) ? 1 : 0,
          ],
          /*
          'menu' => [
             '#title' => 'Add menu field on Save',
             '#type' => 'checkbox',
             '#default_value' => (isset($settings[$contentType->id()])
                && $settings[$contentType->id()]['menu']) ? 1 : 0,
          ],
           */
        ];
      }
      if (isset($form['bundles']['parade_onepage'])) {
        $form['bundles']['parade_onepage']['enabled']['#value'] = 1;
        $form['bundles']['parade_onepage']['enabled']['#disabled'] = TRUE;
        $form['bundles']['parade_onepage']['css_disabled']['#value'] = 0;
        $form['bundles']['parade_onepage']['css_disabled']['#disabled'] = TRUE;
        // $form['bundles']['parade_onepage']['menu']['#value'] = 1;
        // $form['bundles']['parade_onepage']['menu']['#disabled'] = TRUE;.
      }

      $form['description'] = [
        '#markup' => $this->t("For enabled content types on 'Save configuration' parade field will be added: parade_onepage_sections. Field should be deleted manually.<br />You can disable loading parade.demo.css file on enabled content type node view pages."),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Add parade_ fields to bundle save enabled bundles to settings.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $enabled = $just_enabled = [];
    $settings = $this->config('parade_demo.settings')->get('bundles');
    foreach ($form_state->getValue('bundles') as $bundle => $data) {
      if (isset($data['enabled']) && $data['enabled']) {
        $enabled[$bundle] = $data;
        unset($enabled[$bundle]['enabled']);

        if (!isset($settings[$bundle], $settings[$bundle]['enabled']) || !$settings[$bundle]['enabled']) {
          $just_enabled[$bundle] = $data;
        }
      }
    }

    if (!empty($just_enabled)) {
      $configPath = drupal_get_path('module', 'parade_demo') . '/config/install';
      $source = new FileStorage($configPath);

      $field_names = [
        // 'parade_onepage_id',
        // 'parade_onepage_menu',.
        'parade_onepage_sections',
      ];
      foreach ($just_enabled as $bundle => $data) {

        foreach ($field_names as $delta => $field_name) {
          $field_storage = $this->entityTypeManager->getStorage('field_storage_config')->load('node' . '.' . $field_name);

          if (NULL === $field_storage) {
            $config_name = 'field.storage.node.' . $field_name;
            $config = $source->read($config_name);
            $field_storage = $this->entityTypeManager->getStorage('field_storage_config')->create([
              'field_name' => $field_name,
              'entity_type' => $config['entity_type'],
              'type' => $config['type'],
              'cardinality' => $config['cardinality'],
              'settings' => $config['settings'],
            ])->save();
          }

          $field = $this->entityTypeManager->getStorage('field_config')->load('node' . '.' . $bundle . '.' . $field_name);
          if (NULL === $field) {
            // Load our base config data.
            $config_name = 'field.field.node.parade_onepage.' . $field_name;
            $config = $source->read($config_name);

            $field = $this->entityTypeManager->getStorage('field_config')->create([
              'field_storage' => $field_storage,
              'bundle' => $bundle,
              'label' => $config['label'],
              'settings' => $config['settings'],
            ]);
            $field->save();

            $config_name = 'core.entity_form_display.node.parade_onepage.default';
            $config = $source->read($config_name);

            // Assign widget settings for the 'default' form mode.
            $entity_form_display = $this->entityTypeManager
              ->getStorage('entity_form_display')
              ->load('node.' . $bundle . '.default');
            $title_weight = $entity_form_display->getComponent('title')['weight'];
            $entity_form_display->setComponent($field_name, [
              'type' => $config['content'][$field_name]['type'],
              'settings' => $config['content'][$field_name]['settings'],
              'region' => 'content',
              'weight' => $title_weight + ($delta + 1),
            ])->save();

            $config_name = 'core.entity_view_display.node.parade_onepage.default';
            $config = $source->read($config_name);

            // Assign display settings for the 'default' view mode.
            $this->entityTypeManager
              ->getStorage('entity_view_display')
              ->load('node.' . $bundle . '.default')
              ->setComponent($field_name, [
                'label' => $config['content'][$field_name]['label'],
                'type' => $config['content'][$field_name]['type'],
                'settings' => $config['content'][$field_name]['settings'],
                'region' => 'content',
                'weight' => $delta,
              ])
              ->save();
          }
        }
      }
    }

    $this->config('parade_demo.settings')
      ->set('bundles', $enabled)
      ->save();

    parent::submitForm($form, $form_state);

    $this->renderCache->deleteAll();
  }

}
