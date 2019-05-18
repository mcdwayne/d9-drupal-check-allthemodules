<?php

namespace Drupal\measuremail\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\measuremail\ConfigurableMeasuremailElementInterface;
use Drupal\measuremail\Plugin\MeasuremailElementsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for measuremail edit form.
 *
 * @internal
 */
class MeasuremailEditForm extends MeasuremailFormBase {

  /**
   * The measuremail elements manager service.
   *
   * @var \Drupal\measuremail\Plugin\MeasuremailElementsManager
   */
  protected $measuremailElementManager;

  /**
   * Constructs an MeasuremailEditForm object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $measuremail_storage
   *   The storage.$form['settings'] = [
   * '#type' => 'fieldgroup',
   * '#title' => $this->t('Settings'),
   * ];
   *
   * $form['settings']['endpoint'] = [
   * '#type' => 'textfield',
   * '#title' => $this->t('Endpoint'),
   * '#default_value' => '',
   * ];
   * @param \Drupal\measuremail\Plugin\MeasuremailElementsManager $measuremail_storage
   *   The measuremail element manager service.
   * @param \Drupal\measuremail\Plugin\MeasuremailElementsManager $measuremail_element_manager
   *   The measuremail element manager service.
   */
  public function __construct(EntityStorageInterface $measuremail_storage, MeasuremailElementsManager $measuremail_element_manager) {
    parent::__construct($measuremail_storage);
    $this->measuremailElementManager = $measuremail_element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('measuremail'),
      $container->get('plugin.manager.measuremail.elements')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $measuremail = $this->entity;
    $user_input = $form_state->getUserInput();
    $settings = $measuremail->getSettings();

    // Build the list of existing measuremail elements for this measuremail form.
    $form['elements'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Measuremail ID'),
        $this->t('Element'),
        $this->t('Required'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'measuremail-element-order-weight',
        ],
      ],
      '#attributes' => [
        'id' => 'measuremail-elements',
      ],
      '#empty' => t('There are currently no elements in this form. Add one by selecting an option below.'),
      // Render elements below parent form.
      '#weight' => 5,
    ];
    foreach ($measuremail->getElements() as $element) {
      $data = $element->getConfiguration()['data'];
      $key = $element->getUuid();
      $form['elements'][$key]['#attributes']['class'][] = 'draggable';
      $form['elements'][$key]['#weight'] = isset($user_input['elements']) ? $user_input['elements'][$key]['weight'] : NULL;
      $form['elements'][$key]['label'] = [
        '#tree' => FALSE,
        'data' => [
          'label' => [
            '#plain_text' => $data['label'],
          ],
        ],
      ];
      $form['elements'][$key]['id'] = [
        '#tree' => FALSE,
        'data' => [
          'label' => [
            '#plain_text' => $data['id'],
          ],
        ],
      ];
      $form['elements'][$key]['element'] = [
        '#tree' => FALSE,
        'data' => [
          'label' => [
            '#plain_text' => $element->label(),
          ],
        ],
      ];

      $form['elements'][$key]['required'] = [
        '#tree' => FALSE,
        'data' => [
          'label' => [
            '#plain_text' => $data['required'] == 1 ? t('Yes') : t('No'),
          ],
        ],
      ];


      $form['elements'][$key]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $element->label()]),
        '#title_display' => 'invisible',
        '#default_value' => $element->getWeight(),
        '#attributes' => [
          'class' => ['measuremail-element-order-weight'],
        ],
      ];

      $links = [];
      $is_configurable = $element instanceof ConfigurableMeasuremailElementInterface;
      if ($is_configurable) {
        $links['edit'] = [
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('measuremail.element_edit_form', [
            'measuremail' => $this->entity->id(),
            'measuremail_element' => $key,
          ]),
        ];
      }
      $links['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('measuremail.element_delete', [
          'measuremail' => $this->entity->id(),
          'measuremail_element' => $key,
        ]),
      ];
      $form['elements'][$key]['operations'] = [
        '#type' => 'operations',
        '#links' => $links,
      ];
    }

    // Build the new measuremail element addition form and add it to the element list.
    $new_element_options = [];
    $elements = $this->measuremailElementManager->getDefinitions();
    uasort($elements, function ($a, $b) {
      return Unicode::strcasecmp($a['label'], $b['label']);
    });
    foreach ($elements as $element => $definition) {
      $new_element_options[$element] = $definition['label'];
    }
    $form['elements']['new'] = [
      '#tree' => FALSE,
      '#weight' => isset($user_input['weight']) ? $user_input['weight'] : NULL,
      '#attributes' => ['class' => ['draggable']],
    ];

    $form['elements']['new']['label'] = [
      'data' => [],
    ];
    $form['elements']['new']['id'] = [
      'data' => [],
    ];

    $form['elements']['new']['element'] = [
      'data' => [
        'new' => [
          '#type' => 'select',
          '#title' => $this->t('Element'),
          '#title_display' => 'invisible',
          '#options' => $new_element_options,
          '#empty_option' => $this->t('Select a new element'),
        ],
        [
          'add' => [
            '#type' => 'submit',
            '#value' => $this->t('Add'),
            '#validate' => ['::elementValidate'],
            '#submit' => ['::submitForm', '::elementSave'],
          ],
        ],
      ],
      '#prefix' => '<div class="measuremail-element-new">',
      '#suffix' => '</div>',
    ];

    $form['elements']['new']['required'] = [
      'data' => [],
    ];

    $form['elements']['new']['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight for new element'),
      '#title_display' => 'invisible',
      '#default_value' => count($this->entity->getElements()) + 1,
      '#attributes' => ['class' => ['measuremail-element-order-weight']],
    ];
    $form['elements']['new']['operations'] = [
      'data' => [],
    ];


    return parent::form($form, $form_state);
  }

  /**
   * Validate handler for measuremail element.
   */
  public function elementValidate($form, FormStateInterface $form_state) {
    if (!$form_state->getValue('new')) {
      $form_state->setErrorByName('new', $this->t('Select an element to add.'));
    }
  }

  /**
   * Submit handler for measuremail element.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function elementSave($form, FormStateInterface $form_state) {
    $this->save($form, $form_state);

    // Check if this field has any configuration options.
    $element = $this->measuremailElementManager->getDefinition($form_state->getValue('new'));

    // Load the configuration form for this option.
    if (is_subclass_of($element['class'], '\Drupal\measuremail\ConfigurableMeasuremailElementInterface')) {
      $form_state->setRedirect(
        'measuremail.element_add_form',
        [
          'measuremail' => $this->entity->id(),
          'measuremail_element' => $form_state->getValue('new'),
        ],
        ['query' => ['weight' => $form_state->getValue('weight')]]
      );
    }
    // If there's no form, immediately add the measuremail element.
    else {
      $element = [
        'id' => $element['id'],
        'data' => [],
        'weight' => $form_state->getValue('weight'),
      ];
      $element_id = $this->entity->addMeasuremailElement($element);
      $this->entity->save();
      if (!empty($element_id)) {
        drupal_set_message($this->t('The measuremail element was successfully applied.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    drupal_set_message($this->t('Changes to the element have been saved.'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Update measuremail element weights.
    if (!$form_state->isValueEmpty('elements')) {
      $this->updateElementsWeights($form_state->getValue('elements'));
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Updates measuremail elements weights.
   *
   * @param array $elements
   *   Associative array with elements having element uuid as keys and array
   *   with element data as values.
   */
  protected function updateElementsWeights(array $elements) {
    foreach ($elements as $uuid => $element_data) {
      if ($this->entity->getElements()->has($uuid)) {
        $this->entity->getElement($uuid)->setWeight($element_data['weight']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Update element');

    return $actions;
  }

}
