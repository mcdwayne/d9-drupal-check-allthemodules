<?php

namespace Drupal\tr_rulez\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\rules\Engine\ExpressionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of RulesComponentConfig entities.
 *
 * @see \Drupal\rules\Entity\RulesComponent
 */
class RulesComponentListBuilder extends ConfigEntityListBuilder implements FormInterface {

  /**
   * The expression manager.
   *
   * @var \Drupal\rules\Engine\ExpressionManagerInterface
   */
  protected $expressionManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new RulesComponentListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\rules\Engine\ExpressionManagerInterface $expression_manager
   *   The rules expression plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, ExpressionManagerInterface $expression_manager) {
    parent::__construct($entity_type, $storage);
    $this->expressionManager = $expression_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.rules_expression')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rules_component_list_builder';
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the contact list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['label'] = $this->t('Rules Component');
    $header['plugin'] = $this->t('Plugin');
    $header['description'] = $this->t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $expression_config = $entity->getExpression()->getConfiguration();
    $definition = $this->expressionManager->getDefinition($expression_config['id']);
    $plugin = $definition['label'];

    /** @var \Drupal\rules\Entity\RulesComponentConfig $entity */
    $details = $this->t('Machine name: @name', ['@name' => $entity->id()]);
    if ($entity->hasTags()) {
      $details = $details . '<br />' . $this->t('Tags: @tags', ['@tags' => implode(', ', $entity->getTags())]);
    }

    $row['label']['data-drupal-selector'] = 'rules-table-filter-text-source';
    $row['label']['data'] = [
      '#plain_text' => $this->getLabel($entity),
      '#suffix' => '<div class="description">' . $details . '</div>',
    ];
    $row['plugin']['data-drupal-selector'] = 'rules-table-filter-text-source';
    $row['plugin']['data'] = [
      '#plain_text' => $plugin,
    ];
    $row['description']['data-drupal-selector'] = 'rules-table-filter-text-source';
    $row['description']['data'] = [
      '#type' => 'processed_text',
      '#text' => $entity->getDescription(),
      '#format' => 'restricted_html',
    ];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {
    $build = parent::buildOperations($entity);
    $build['#links']['clone'] = [
      'title' => $this->t('Clone'),
      'url' => Url::fromRoute('entity.rules_component.clone', ['rules_component' => $entity->id()]),
      // Active selection is weight 0, 'edit' is weight 10,
      // 'delete' is weight 100.
      'weight' => 20,
    ];

    uasort($build['#links'], 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = array_map(function ($definition) {
      return $definition['label'];
    }, $this->expressionManager->getDefinitions());

    if ($options) {
      uasort($options, 'strnatcasecmp');

      $form['add'] = [
        '#type' => 'details',
        '#title' => $this->t('Add component'),
        '#open' => TRUE,
        '#attributes' => [
          'class' => ['container-inline'],
        ],
      ];
      $form['add']['component_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Type'),
        '#empty_option' => $this->t('- Choose -'),
        '#options' => $options,
      ];
      $form['add']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add component'),
        '#validate' => ['::validateAddComponent'],
        '#submit' => ['::submitAddComponent'],
        '#limit_validation_errors' => [['component_type']],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No validation needed.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No submit handler.
  }

  /**
   * Form validation handler for adding a new component.
   */
  public function validateAddComponent(array &$form, FormStateInterface $form_state) {
    if ($form_state->isValueEmpty('component_type')) {
      $form_state->setErrorByName('component_type', $this->t('You must select the new component type.'));
    }
  }

  /**
   * Form submission handler for adding a new component.
   */
  public function submitAddComponent(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.rules_component.add_form.rule', [
      'plugin_id' => $form_state->getValue('component_type'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['#attached']['library'][] = 'tr_rulez/rules_ui.listing';
    $build['description'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('Components are stand-alone sets of Rules configuration that can be used by Rules and other modules on your site. Components are for example useful if you want to use the same conditions, actions or rules in multiple places, or call them from your custom module. You may also export each component separately. See the <a href=":documentation">online documentation</a> for more information about how to use components.', [':documentation' => 'https://www.drupal.org/node/1300024']),
      '#suffix' => '</p>',
    ];
    $build += $this->formBuilder()->getForm($this);
    $build += parent::render();
    $build['table']['#empty'] = $this->t('No rules components have been defined.');

    return $build;
  }

  /**
   * Returns the form builder.
   *
   * @return \Drupal\Core\Form\FormBuilderInterface
   *   The form builder.
   */
  protected function formBuilder() {
    if (!$this->formBuilder) {
      $this->formBuilder = \Drupal::formBuilder();
    }
    return $this->formBuilder;
  }

}
