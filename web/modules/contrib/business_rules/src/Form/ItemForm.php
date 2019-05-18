<?php

namespace Drupal\business_rules\Form;

use Drupal\business_rules\Entity\Action;
use Drupal\business_rules\Entity\Condition;
use Drupal\business_rules\Entity\Variable;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Business rules item.
 *
 * @package Drupal\business_rules\Form
 */
abstract class ItemForm extends EntityForm {

  /**
   * The services container.
   *
   * @var null|ContainerInterface
   */
  protected $container;

  /**
   * The form step.
   *
   * @var int
   */
  protected $step = 1;

  /**
   * The business rules util.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesUtil
   */
  protected $util;

  /**
   * The variable manager.
   *
   * @var \Drupal\business_rules\Plugin\BusinessRulesVariableManager
   */
  protected $variableManager;

  /**
   * The Business Rule flowchart.
   *
   * @var \Drupal\business_rules\Util\Flowchart\Flowchart
   */
  private $chart;

  /**
   * {@inheritdoc}
   */
  public function __construct(ContainerInterface $container) {
    $this->container       = $container;
    $this->util            = $container->get('business_rules.util');
    $this->variableManager = $container->get('plugin.manager.business_rules.variable');
    $this->chart           = $container->get('business_rules.flowchart');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form        = parent::form($form, $form_state);
    $itemManager = $this->getItemManager();
    $form_state->set('business_rules_step', $this->step);

    /** @var \Drupal\business_rules\ItemInterface $item */
    $item = $this->entity;
    $class = get_class($item);

    if ($this->step === 1 && $item->isNew()) {
      $options = $item->getTypes();

      $form['type'] = [
        '#type'     => 'select',
        '#title'    => $this->t('Type'),
        '#required' => TRUE,
        '#options'  => $options,
        '#weight'   => 0,
      ];
    }

    if ($this->step > 1 || !$item->isNew()) {

      $type        = $item->getType() ? $item->getType() : $form_state->getValue('type');
      $definition  = $itemManager->getDefinition($type);
      $reflection  = new \ReflectionClass($definition['class']);
      $custom_item = $reflection->newInstance($definition, $definition['id'], $definition);

      $form['label_type'] = [
        '#type'        => 'item',
        '#title'       => $this->t('Type'),
        '#markup'      => $definition['label'],
        '#description' => $definition['description'],
        '#weight'      => 10,
      ];

      $form['type'] = [
        '#type'  => 'value',
        '#value' => $type,
      ];

      $form['label'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('Label'),
        '#maxlength'     => 255,
        '#default_value' => $item->label(),
        '#description'   => $this->t("Label for the Item."),
        '#required'      => TRUE,
        '#weight'        => 20,
      ];

      $form['id'] = [
        '#type'          => 'machine_name',
        '#default_value' => $item->id(),
        '#machine_name'  => [
          'exists' => "\\$class::load",
        ],
        '#disabled'      => !$item->isNew(),
        '#weight'        => 30,
      ];

      $form['description'] = [
        '#type'          => 'textarea',
        '#title'         => $this->t('Description'),
        '#description'   => $this->t('A good description of this Item.'),
        '#required'      => TRUE,
        '#default_value' => $item->getDescription(),
        '#weight'        => 40,
      ];

      $form['tags'] = [
        '#type'                          => 'textfield',
        '#title'                         => $this->t('Tags'),
        '#default_value'                 => $item->getTags() ? implode(', ', $item->getTags()) : '',
        '#description'                   => $this->t('List of comma-separated tags.'),
        '#required'                      => FALSE,
        '#weight'                        => 41,
        '#autocomplete_route_name'       => 'business_rules.autocomplete_tags',
        '#autocomplete_route_parameters' => [],

      ];

      $form['settings']            = $this->getEntityInformationForm($definition);
      $form['settings']['#weight'] = 50;

      // Get the plugin definition form.
      $form['settings'] += $custom_item->getSettingsForm($form, $form_state, $item);

      // Additional item form fields.
      $form['additional_fields']            = [];
      $form['additional_fields']['#weight'] = 60;

      // Show available tokens replacements.
      $form['tokens']['#markup'] = $this->getTokensLink();
      $form['tokens']['#weight'] = 900;
      if ($this->util->moduleHandler->moduleExists('token')) {
        $form['#attached']['library'][] = 'token/token';
        $form['#attached']['library'][] = 'token/jquery.treeTable';
      }

      // Show the available variables.
      $form['variables']            = $this->util->getVariablesDetailsBox($item);
      $form['variables']['#weight'] = 1000;

      $form['business_rules_box']            = $this->util->getUsedByBusinessRulesDetailsBox($item);
      $form['business_rules_box']['#weight'] = 1100;

      $form['conditions_box']            = $this->util->getUsedByConditionsDetailsBox($item);
      $form['conditions_box']['#weight'] = 1110;

      $form['actions_box']            = $this->util->getUsedByActionsDetailsBox($item);
      $form['actions_box']['#weight'] = 1120;

      if (!$item->isNew() && !$item instanceof Variable && ($item instanceof Action && is_array($item->getSettings('items')) && count($item->getSettings('items')) || $item instanceof Condition)) {

        $flowchart = $this->chart->getGraph($item);

        if (count($flowchart)) {
          $form['flowchart'] = [
            '#type'   => 'details',
            '#title'  => $this->t('Flowchart'),
            '#weight' => 1200,
            '#open'   => FALSE,
          ];

          $form['flowchart']['graph']     = $flowchart;
          $form['#attached']['library'][] = 'business_rules/mxClient';
        }
      }

    }

    $form['#attached']['library'][] = 'business_rules/style';

    return $form;
  }

  /**
   * Get the pluginManager.
   *
   * @return \Drupal\Core\Plugin\DefaultPluginManager
   *   The item PluginManager.
   */
  abstract public function getItemManager();

  /**
   * Get the fields for entity type and bundle.
   *
   * @param array $item_definition
   *   The item definition.
   *
   * @return array
   *   The render array.
   */
  public function getEntityInformationForm(array $item_definition) {

    $form = [];

    /** @var \Drupal\business_rules\ItemInterface $item */
    $item = $this->entity;

    $show_entity       = FALSE;
    $show_bundle       = FALSE;
    $show_field        = FALSE;
    $context_dependent = $item_definition['isContextDependent'];

    if ($item_definition['hasTargetField']) {
      $show_entity = TRUE;
      $show_bundle = TRUE;
      $show_field = TRUE;
    }
    elseif ($item_definition['hasTargetBundle']) {
      $show_entity = TRUE;
      $show_bundle = TRUE;
    }
    elseif ($item_definition['hasTargetEntity']) {
      $show_entity = TRUE;
    }

    if ($show_entity) {
      if ($context_dependent) {
        $form['context'] = [
          '#type'  => 'fieldset',
          '#title' => $this->t('Context: This information cannot be changed after the item is saved.'),
        ];
      }

      $form['context']['target_entity_type'] = [
        '#type'          => 'select',
        '#options'       => $this->util->getEntityTypes(),
        '#required'      => TRUE,
        '#title'         => $this->t('Target Entity Type'),
        '#description'   => $this->t('The Entity Type which this item is applicable.'),
        '#default_value' => $item->getTargetEntityType(),
        '#disabled'      => $this->entity->isNew() || !$context_dependent ? FALSE : TRUE,
      ];
    }

    if ($show_bundle) {
      $form['context']['target_entity_type']['#ajax'] = [
        'callback' => [
          $this,
          'targetEntityTypeCallback',
        ],
      ];

      $form['context']['target_bundle'] = [
        '#type'          => 'select',
        '#title'         => $this->t('Target Bundle'),
        '#description'   => $this->t('The Bundle which this item is applicable.'),
        '#options'       => $this->util->getBundles($item->getTargetEntityType()),
        '#required'      => TRUE,
        '#default_value' => $item->getTargetBundle(),
        '#disabled'      => $this->entity->isNew() || !$context_dependent ? FALSE : TRUE,
        '#prefix'        => '<div id="target_bundle-wrapper">',
        '#suffix'        => '</div>',
      ];
    }

    if ($show_field) {
      $form['context']['target_bundle']['#ajax'] = [
        'callback' => [
          $this,
          'targetBundleCallback',
        ],
      ];

      $form['field'] = [
        '#type'          => 'select',
        '#options'       => $this->util->getBundleFields($item->getTargetEntityType(), $item->getTargetBundle()),
        '#required'      => TRUE,
        '#disabled'      => FALSE,
        '#title'         => $this->t('Field'),
        '#description'   => $this->t('The entity field.'),
        '#default_value' => $item->getSettings('field'),
        '#prefix'        => '<div id="field_selector-wrapper">',
        '#suffix'        => '</div>',
      ];
    }

    return $form;
  }

  /**
   * Provide a link a modal window with all available tokens.
   *
   * @return \Drupal\Core\GeneratedLink|null
   *   The modal link or NULL if Token module is not installed.
   */
  protected function getTokensLink() {

    if ($this->util->moduleHandler->moduleExists('token')) {
      // Show a link to a modal window with all available tokens.
      $keyvalue = $this->util->getKeyValueExpirable('token_tree');
      $content = $this->util->tokenTree->buildAllRenderable();
      $keyvalue->set('token_tree', $content);

      $tokens_link = Link::createFromRoute($this->t('Click here to see all available tokens. Be aware that some tokens will only works on the right context.'),
        'business_rules.ajax.modal',
        [
          'method'     => 'nojs',
          'title'      => $this->t('Tokens'),
          'collection' => 'token_tree',
          'key'        => 'token_tree',
        ],
        [
          'attributes' => [
            'class' => ['use-ajax'],
          ],
        ]
      )->toString();

      return $tokens_link;
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $actions = parent::actionsElement($form, $form_state);

    if (!$this->entity->isNew()) {
      $actions['done'] = [
        '#type'   => 'submit',
        '#value'  => $this->t('Done'),
        '#submit' => ['::submitForm', '::save'],
        '#op'     => 'done',
        '#weight' => 7,
      ];
    }
    elseif ($this->step === 1) {
      $actions['submit']['#value'] = $this->t('Continue');
    }

    $actions['submit']['#op'] = 'save';

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form        = parent::buildForm($form, $form_state);
    $itemManager = $this->getItemManager();

    $form['actions']['#weight'] = 1200;

    $type = $this->entity->getType() ? $this->entity->getType() : $form_state->getValue('type');
    if (!empty($type)) {
      $definition = $itemManager->getDefinition($type);
      $reflection = new \ReflectionClass($definition['class']);

      $custom_item = $reflection->newInstance($definition, $definition['id'], $definition);
      $custom_item->buildForm($form, $form_state);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    if ($this->step < 2 && $this->entity->isNew()) {
      $this->step++;
      $form_state->setRebuild();

      return $form;
    }
    else {
      /** @var \Drupal\business_rules\ItemInterface $item */
      $item        = $this->entity;
      $itemManager = $this->getItemManager();

      // Put the settings fields in an array format to save the ConfigEntity.
      // @TODO change it to use the item's schema.
      $settings = [];
      foreach ($form['settings'] as $key => $value) {
        if (substr($key, 0, 1) != '#' && array_key_exists($key, $form_state->getValues())
          && !in_array($key, [
            'target_entity_type',
            'target_bundle',
          ])
        ) {
          $settings[$key] = $form_state->getValue($key);
        }
      }

      $type        = $item->getType() ? $item->getType() : $form_state->getValue('type');
      $definition  = $itemManager->getDefinition($type);
      $reflection  = new \ReflectionClass($definition['class']);
      $custom_item = $reflection->newInstance($definition, $definition['id'], $definition);
      $settings    = $custom_item->processSettings($settings, $item);

      $item->set('settings', $settings);
      $item->setTags(explode(',', $form_state->getValue('tags')));

      $status = $item->save();
      // As the item may need to be executed under a cached hook, we need to
      // invalidate all rendered caches.
      Cache::invalidateTags(['rendered']);

      switch ($status) {
        case SAVED_NEW:
          drupal_set_message($this->t('Created the %label Item.', [
            '%label' => $item->label(),
          ]));
          break;

        default:
          drupal_set_message($this->t('Saved the %label Item.', [
            '%label' => $item->label(),
          ]));
      }

      if (isset($form_state->getTriggeringElement()['#op'])) {
        $op = $form_state->getTriggeringElement()['#op'];

        if ($op == 'save') {
          $form_state->setRedirectUrl($custom_item->getEditUrl($item));
        }
        else {
          $form_state->setRedirectUrl($custom_item->getRedirectUrl($item));
        }
      }

    }

    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate machine name. We don't want actions and conditions with the same
    // machine name because Business Rules use this items together and it's id
    // as the array key.
    $id = $form_state->getValue('id');
    if ($id && $this->entity->isNew()) {
      $action    = Action::load($id);
      $condition = Condition::load($id);
      if (!empty($action) || !empty($condition)) {
        $form_state->setErrorByName('id', $this->t('The machine-readable name is already in use. It must be unique.'));
      }
    }

    // Validate the form using the plugin validateForm() method.
    $type        = $form_state->getValue('type');
    $definition  = $this->getItemManager()->getDefinition($type);
    $reflection  = new \ReflectionClass($definition['class']);
    $custom_item = $reflection->newInstance($definition, $definition['id'], $definition);
    $custom_item->validateForm($form, $form_state);
  }

  /**
   * Helper function to show the list of fields according the selected Bundle.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AjaxResponse.
   */
  public function targetBundleCallback(array &$form, FormStateInterface $form_state) {
    $selected_bundle         = $form_state->getValue('target_bundle');
    $selected_entity_type    = $form_state->getValue('target_entity_type');
    $field                   = &$form['settings']['field'];
    $field['#options']       = $this->util->getBundleFields($selected_entity_type, $selected_bundle);
    $field['#default_value'] = '';

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#field_selector-wrapper', $field));
    $form_state->setRebuild();

    return $response;

  }

  /**
   * Show the list of bundles according the selected Entity Type.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AjaxResponse.
   */
  public function targetEntityTypeCallback(array &$form, FormStateInterface $form_state) {

    $selected_entity_type            = $form_state->getValue('target_entity_type');
    $target_bundle                   = &$form['settings']['context']['target_bundle'];
    $target_bundle['#options']       = $this->util->getBundles($selected_entity_type);
    $target_bundle['#default_value'] = '';
    $target_bundle['#ajax']          = [
      'callback' => [$this, 'targetBundleCallback'],
    ];

    $field                   = &$form['settings']['field'];
    $field['#options']       = [
      '' => t('-Select-'),
    ];
    $field['#default_value'] = '';

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#target_bundle-wrapper', $target_bundle));
    $response->addCommand(new ReplaceCommand('#field_selector-wrapper', $field));
    $form_state->setRebuild();

    return $response;
  }

}
