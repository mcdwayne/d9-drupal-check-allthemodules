<?php

namespace Drupal\svg_maps\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\MachineName;
use Drupal\svg_maps\SvgMapsTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SvgMapsEntityForm.
 */
class SvgMapsEntityForm extends EntityForm {

  /**
   * The instantiated plugin instances that have configuration forms.
   *
   * @var \Drupal\Core\Plugin\PluginFormInterface[]
   */
  protected $configurableInstances = [];

  /**
   * The svg maps plugin manager.
   *
   * @var \Drupal\svg_maps\SvgMapsTypeManager
   */
  protected $plugin_manager;

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;


  /**
   * Constructs a new class instance.
   *
   * @param SvgMapsTypeManager $pluginManager
   *   Svg maps plugin manager.
   * @param EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(SvgMapsTypeManager $pluginManager, EntityFieldManagerInterface $entity_field_manager) {
    $this->plugin_manager = $pluginManager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.svg_maps.type'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
//    $form_state->setCached(FALSE);
    /**
     * @var $svg_maps_entity \Drupal\svg_maps\Entity\SvgMapsEntityInterface
     */
    $form['#entity'] = $svg_maps_entity = $this->entity;
    $form_state->set('api_entity', $svg_maps_entity);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $svg_maps_entity->label(),
      '#description' => $this->t("Label for the Svg maps entity."),
      '#required' => TRUE,
    ];

    $plugins = $this->plugin_manager->getDefinitions();
    $options = [];
    foreach ($plugins as $plugin => $definition) {
      $options[$plugin] = $definition['label'];
    }

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type provider'),
      '#default_value' => $svg_maps_entity->getType()->getPluginId(),
      '#options' => $options,
      '#ajax' => [
        'callback' => '::actionCallback',
        'wrapper' => 'path-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Updating type provider configuration form.'),
        ],
      ],
    ];


    /** @var \Drupal\svg_maps\SvgMapsTypeInterface $pluginObj */
    if($form_state->getValue('type')) {
      /**
       * @var $pluginsManager \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
       */
      $pluginsManager = $svg_maps_entity->getPluginCollections()['type_configuration'];
      $pluginObj = $pluginsManager->get($form_state->getValue('type'));
    }
    else {
      $pluginObj = $svg_maps_entity->getType();
    }

    $form['maps_path'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#prefix' => '<div id="path-wrapper">',
      '#suffix' => '</div>',
    ];

    if ($pluginObj) {
      $plugin_configuration = (empty($this->configurableInstances[$pluginObj->getPluginId()]['plugin_config'])) ? $svg_maps_entity->getTypeConfiguration(): $this->configurableInstances[$pluginObj->getPluginId()]['plugin_config'];
      /** @var \Drupal\svg_maps\SvgMapsTypeBase $instance */
      $instance = $this->plugin_manager->createInstance($pluginObj->getPluginId(), $plugin_configuration);
      // Store the configuration for validate and submit handlers.
      $this->configurableInstances[$pluginObj->getPluginId()]['plugin_config'] = $plugin_configuration;

      $svg_maps_path = $form_state->get('maps_path');
      if ($svg_maps_path === NULL) {
        $svg_maps_path = $svg_maps_entity->getMapsPath();
        $form_state->set('maps_path', $svg_maps_path);
      }

      $form['maps_path'] += $instance->buildConfigurationForm([], $form_state);
      $childrenElements = Element::children($form['maps_path']);
      array_pop($childrenElements);
      foreach($childrenElements as $child_key) {
        $form['maps_path'][$child_key]['delete'] = [
          '#type' => 'submit',
          '#value' => $this->t('Delete'),
          '#name' => 'delete_' . $child_key,
          '#submit' => ['::removePath'],
          '#ajax' => [
            'callback' => '::actionCallback',
            'wrapper' => 'path-wrapper',
          ]
        ];
      }

      $form['add'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add'),
        '#submit' => ['::addPath'],
        '#ajax' => [
          'callback' => '::actionCallback',
          'wrapper' => 'path-wrapper',
        ],
      ];
    }

    return $form;
  }

  public function addPath(array &$form, FormStateInterface $form_state) {
    $svg_maps_entity = $this->entity;

    /** @var \Drupal\svg_maps\SvgMapsTypeInterface $pluginObj */
    if($form_state->getValue('type')) {
      /**
       * @var $pluginsManager \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
       */
      $pluginsManager = $svg_maps_entity->getPluginCollections()['type_configuration'];
      $pluginObj = $pluginsManager->get($form_state->getValue('type'));
    }
    else {
      $pluginObj = $svg_maps_entity->getType();
    }
    if ($pluginObj) {
      $plugin_configuration = (empty($this->configurableInstances[$pluginObj->getPluginId()]['plugin_config'])) ? $svg_maps_entity->getTypeConfiguration() : $this->configurableInstances[$pluginObj->getPluginId()]['plugin_config'];
      /** @var \Drupal\svg_maps\SvgMapsTypeBase $instance */
      $instance = $this->plugin_manager->createInstance($pluginObj->getPluginId(), $plugin_configuration);
      // Store the configuration for validate and submit handlers.
      $this->configurableInstances[$pluginObj->getPluginId()]['plugin_config'] = $plugin_configuration;
      $svg_maps_path = $form_state->get('maps_path');
      $svg_maps_path[] = [
        'path' => '',
        'label' => '',
      ];
      $form_state->set('maps_path', $svg_maps_path);
      $form_state->setRebuild();
    }

  }

  public function removePath(array $form, FormStateInterface $form_state) {
    $svg_maps_path = $form_state->get('maps_path');
    $triggeringElement = $form_state->getTriggeringElement();
    if (array_key_exists($triggeringElement['#parents'][1], $svg_maps_path)) {
      unset($svg_maps_path[$triggeringElement['#parents'][1]]);
      $form_state->set('maps_path', $svg_maps_path);
      $form_state->setValue('maps_path', $svg_maps_path);
    }
    $form_state->setRebuild();
  }

  public function actionCallback(array $form, FormStateInterface $form_state) {
    return $form['maps_path'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /**
     * @var $svg_maps_entity \Drupal\svg_maps\Entity\SvgMapsEntityInterface
     */
    $svg_maps_entity = $this->entity;
    $maps_path = $form_state->getValue('maps_path', []);
    $maps_path = array_filter($maps_path, function ($item) {
      return !empty($item['path']);
    });

    $svg_maps_entity->setMapsPath(array_values($maps_path));
    $status = $svg_maps_entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Svg maps entity.', [
          '%label' => $svg_maps_entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Svg maps entity.', [
          '%label' => $svg_maps_entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($svg_maps_entity->toUrl('collection'));
  }
}
