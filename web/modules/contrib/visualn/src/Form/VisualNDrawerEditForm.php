<?php

namespace Drupal\visualn\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\visualn\Manager\DrawerManager;
use Drupal\visualn\Plugin\VisualNDrawerModifierManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\visualn\ConfigurableDrawerModifierInterface;

/**
 * Class VisualNDrawerEditForm.
 */
class VisualNDrawerEditForm extends VisualNDrawerFormBase {

  /**
   * The visualn drawer modifier manager service.
   *
   * @var \Drupal\visualn\Plugin\VisualNDrawerModifierManager
   */
  // @todo: use shorter variable names (drawerModifierManager for managers)
  protected $visualNDrawerModifierManager;

  /**
   * Constructs an VisualNDrawerEditForm object.
   *
   * @param \Drupal\visualn\Manager\DrawerManager $visualn_drawer_manager
   *   The visualn drawer manager service.
   *
   * @param \Drupal\visualn\Plugin\VisualNDrawerModifierManager $visualn_drawer_modifier_manager
   *   The visualn drawer modifier manager service.
   */
  public function __construct(DrawerManager $visualn_drawer_manager, VisualNDrawerModifierManager $visualn_drawer_modifier_manager) {
    parent:: __construct($visualn_drawer_manager);
    $this->visualNDrawerModifierManager = $visualn_drawer_modifier_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('plugin.manager.visualn.drawer'),
    $container->get('plugin.manager.visualn.drawer_modifier')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // @todo: move to the end of the file (see ImageStyleEditForm())
    $form = parent::form($form, $form_state);

    $form['#attached']['library'][] = 'visualn/admin';


    // @todo: since all keys are on the same level (proabaly because of #tree == FALSE)
    //    the "modifiers" key may be overridden by one of the configuration form keys


    // @todo: check for any changes in ImageStyleEditForm() in further drupal verions
    //    because the code below is mostly based on it

    // Build the list of existing drawer modifiers for this subdrawer.
    $form['modifiers'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Modifier'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'drawer-modifier-order-weight',
        ],
      ],
      '#attributes' => [
        'id' => 'user-defined-drawer-modifiers',
      ],
      '#empty' => t('There are currently no modifiers for this subdrawer. Add one by selecting an option below.'),
      // Render modifiers below parent elements.
      '#weight' => 5,
    ];

    foreach ($this->entity->getModifiers() as $modifier) {
      $key = $modifier->getUuid();
      $form['modifiers'][$key]['#attributes']['class'][] = 'draggable';
      $form['modifiers'][$key]['#weight'] = isset($user_input['modifiers']) ? $user_input['modifiers'][$key]['weight'] : NULL;
      $form['modifiers'][$key]['modifier'] = [
        '#tree' => FALSE,
        'data' => [
          'label' => [
            '#plain_text' => $modifier->label(),
          ],
        ],
      ];
      // @todo:
      $summary = $modifier->getSummary();
      if (!empty($summary)) {
        $summary['#prefix'] = ' ';
        $form['modifiers'][$key]['modifier']['data']['summary'] = $summary;
      }
      $form['modifiers'][$key]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', [
          '@title' => $modifier->label(),
        ]),
        '#title_display' => 'invisible',
        '#default_value' => $modifier->getWeight(),
        '#attributes' => [
          'class' => [
            'drawer-modifier-order-weight',
          ],
        ],
      ];
      $links = [

      ];
      $is_configurable = $modifier instanceof ConfigurableDrawerModifierInterface;
      if ($is_configurable) {
        $links['edit'] = [
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('visualn.drawer.modifier_edit_form', [
            'visualn_drawer' => $this->entity->id(),
            'drawer_modifier' => $key,
          ]),
        ];
      }
      $links['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('visualn.drawer.modifier_delete', [
          'visualn_drawer' => $this->entity->id(),
          'drawer_modifier' => $key,
        ]),
      ];
      $form['modifiers'][$key]['operations'] = [
        '#type' => 'operations',
        '#links' => $links,
      ];
    }

    // Build the new drawer modifier addition form and add it to the modifiers list.
    $new_modifiers_options = [

    ];
    $modifiers = $this->visualNDrawerModifierManager->getDefinitions();
    uasort($modifiers, function ($a, $b) {
      return Unicode::strcasecmp($a['label'], $b['label']);
    });
    foreach ($modifiers as $modifier => $definition) {
      $new_modifiers_options[$modifier] = $definition['label'];
    }
    $form['modifiers']['new'] = [
      '#tree' => FALSE,
      '#weight' => isset($user_input['weight']) ? $user_input['weight'] : NULL,
      '#attributes' => [
        'class' => [
          'draggable',
        ],
      ],
    ];
    $form['modifiers']['new']['modifier'] = [
      'data' => [
        'new' => [
          '#type' => 'select',
          '#title' => $this->t('Modifier'),
          '#title_display' => 'invisible',
          '#options' => $new_modifiers_options,
          '#empty_option' => $this->t('Select a new modifier'),
        ],
        [
          'add' => [
            '#type' => 'submit',
            '#value' => $this->t('Add'),
            '#validate' => [
              '::modifierValidate',
            ],
            '#submit' => [
              '::submitForm',
              '::modifierSave',
            ],
          ],
        ],
      ],
      '#prefix' => '<div class="drawer-modifier-new">',
      '#suffix' => '</div>',
    ];
    $form['modifiers']['new']['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight for new modifier'),
      '#title_display' => 'invisible',
      '#default_value' => count($this->entity->getModifiers()) + 1,
      '#attributes' => [
        'class' => [
          'drawer-modifier-order-weight',
        ],
      ],
    ];
    $form['modifiers']['new']['operations'] = [
      'data' => [

      ],
    ];

    // do no allow to change base drawer if modifiers are already added
    if (count($this->entity->getModifiers())) {
      $form['drawer_plugin_id']['#disabled'] = TRUE;
    }

    return $form;
  }

  /**
   * Validate handler for drawer modifier.
   */
  public function modifierValidate($form, FormStateInterface $form_state) {
    if (!$form_state->getValue('new')) {
      $form_state->setErrorByName('new', $this->t('Select a modifier to add.'));
    }
  }

  /**
   * Submit handler for drawer modifier.
   */
  public function modifierSave($form, FormStateInterface $form_state) {
    $this->save($form, $form_state);

    // Check if this field has any configuration options.
    $modifier = $this->visualNDrawerModifierManager->getDefinition($form_state->getValue('new'));

    // Load the configuration form for this option.
    if (is_subclass_of($modifier['class'], '\\Drupal\\visualn\\ConfigurableDrawerModifierInterface')) {
      $form_state->setRedirect('visualn.drawer.modifier_add_form', [
        'visualn_drawer' => $this->entity->id(),
        'drawer_modifier' => $form_state->getValue('new'),
      ], [
        'query' => [
          'weight' => $form_state->getValue('weight'),
        ],
      ]);
    }
    else {
      $modifier = [
        'id' => $modifier['id'],
        'data' => [

        ],
        'weight' => $form_state->getValue('weight'),
      ];
      $modifier_id = $this->entity->addDrawerModifier($modifier);
      $this->entity->save();
      if (!empty($modifier_id)) {
        drupal_set_message($this->t('The drawer modifier was successfully applied.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Update drawer modifier weights.
    if (!$form_state->isValueEmpty('modifiers')) {
      $this->updateModifierWeights($form_state->getValue('modifiers'));
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Updates drawer modifier weights.
   *
   * @param array $modifiers
   *   Associative array with modifiers having modifier uuid as keys and array
   *   with modifier data as values.
   */
  protected function updateModifierWeights(array $modifiers) {
    foreach ($modifiers as $uuid => $modifier_data) {
      if ($this->entity->getModifiers()->has($uuid)) {
        $this->entity->getModifier($uuid)->setWeight($modifier_data['weight']);
      }
    }
  }

}
