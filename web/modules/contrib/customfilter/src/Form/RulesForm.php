<?php

// Namespace for all forms in this module.
namespace Drupal\customfilter\Form;

// Load the Drupal interface for forms.
use Drupal\Core\Form\FormInterface;

// Load the class with the custom filter entity.
use Drupal\customfilter\Entity\CustomFilter;

// Necessary for SafeMarkup::checkPlain().
use Drupal\Component\Utility\SafeMarkup;

// Load the Drupal interface for the current state of a form.
use Drupal\Core\Form\FormStateInterface;

// Necessary to create links.
use Drupal\Core\Url;

// Necessary for $this->t().
use Drupal\Core\StringTranslation\StringTranslationTrait;

class RulesForm implements FormInterface {
  
  use StringTranslationTrait;

  /** @var \Drupal\customfilter\Entity\CustomFilter */
  protected $entity;

  /**
   * Define the ID of the form.
   */
  public function getFormID() {
    return 'customfilter_rules_form';
  }

  /**
   * Create the form that list the rules.
   */
  public function buildForm(array $form, FormStateInterface $form_state, CustomFilter $customfilter = NULL) {
    $this->entity = $customfilter;

    $form['rules'] = [
      '#type' => 'table',
      '#tree' => 'true',
      '#header' => [
        $this->t('Rule'),
        $this->t('Machine name'),
        [
          'data' => $this->t('Enabled'),
          'class' => ['checkbox'],
        ],
        $this->t('Weight'),
        $this->t('Parent'),
        [
          'data' => $this->t('Operations'),
          'colspan' => 2,
        ],
      ],
        '#empty' => $this->t('There are no items yet. <a href="@add-url">Add an item.</a>', [
        '@add-url' => Url::fromRoute('customfilter.rules.add', ['customfilter' => $this->entity->id()])->toString(),
        ]),

      // TableDrag: Each array value is a list of callback arguments for
      // drupal_add_tabledrag(). The #id of the table is automatically
      // prepended if there is none, an HTML ID is auto-generated.
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'rules-order-weight',
        ],
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'rule-prid',
          'subgroup' => 'rule-prid',
          'source' => 'rule-rid',
          'hidden' => TRUE,
        ],
      ],
    ];

    // Build the table rows and columns.
    // The first nested level in the render array forms the table row, on which
    // you likely want to set #attributes and #weight.
    // Each child element on the second level represents a table column cell in
    // the respective table row, which are render elements on their own. For
    // single output elements, use the table cell itself for the render element.
    // If a cell should contain multiple elements, simply use nested sub-keys
    // to build the render element structure for drupal_render() as you would
    // everywhere else.
    $entities = $this->entity->getRules('', TRUE);
    if (count($entities) > 0) {
      $form['fid'] = [
        '#type' => 'hidden',
        '#value' => $this->entity->id(),
      ];
    }

    $maxWeight = count($this->entity->rules);
    $this->rulesTree($form, $entities, $maxWeight, 0);

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save changes'),
    );
    return $form;
  }

  /**
   * Add the rules to table.
   */
  private function rulesTree(array &$form, array $rules, $maxWeight, $level = 0) {
    foreach ($rules as $rule) {
      // TableDrag: Mark the table row as draggable.
      $form['rules'][$rule['rid']]['#attributes']['class'][] = 'draggable';
      // TableDrag: Sort the table row according to its
      // existing/configured weight.
      $form['rules'][$rule['rid']]['#weight'] = $rule['weight'];
      // Some table columns containing raw markup.
      $form['rules'][$rule['rid']]['name'] = [
        [
          '#theme' => 'indentation',
          '#size' => $level,
        ],
        [
          '#markup' => SafeMarkup::checkPlain($rule['name']),
        ],
      ];

      $form['rules'][$rule['rid']]['ridss'] = [
        '#markup' => $rule['rid'],
      ];

      $form['rules'][$rule['rid']]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable @title menu link', ['@title' => $rule['name']]),
        '#title_display' => 'invisible',
        '#default_value' => $rule['enabled'],
      ];

      // TableDrag: Weight column element.
      $form['rules'][$rule['rid']]['weight'] = [
        '#type' => 'weight',
        '#delta' => $maxWeight,
        '#title' => $this->t('Weight for @title', ['@title' => $rule['name']]),
        '#title_display' => 'invisible',
        '#default_value' => $rule['weight'],
        // Classify the weight element for #tabledrag.
        '#attributes' => ['class' => ['rules-order-weight']],
      ];

      $form['rules'][$rule['rid']]['prid'] = [
        '#type' => 'textfield',
        '#size' => '12',
        '#default_value' => $rule['prid'],
        '#attributes' => ['class' => ['rule-prid']],
      ];

      // Operations (dropbutton) column.
      $links = [];
      $links['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('customfilter.rules.edit', [
          'customfilter' => $this->entity->id(),
          'rule_id' => $rule['rid'],
        ]),
      ];

      $links['add_sub_rule'] = [
        'title' => $this->t('Add Sub Rule'),
        'url' => Url::fromRoute('customfilter.rules.add.subrule', [
          'customfilter' => $this->entity->id(),
          'rule_id' => $rule['rid'],
        ]),
      ];

      $links['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('customfilter.rules.delete', [
          'customfilter' => $this->entity->id(),
          'rule_id' => $rule['rid'],
        ]),
      ];

      $form['rules'][$rule['rid']]['operations'] = [
        '#type' => 'operations',
        '#links' => $links,
      ];

      $form['rules'][$rule['rid']]['rid'] = [
        '#type' => 'hidden',
        '#value' => $rule['rid'],
        '#attributes' => ['class' => ['rule-rid']],
      ];

      $subrules = $this->entity->getRules($rule['rid'], TRUE);

      if (count($subrules) > 0) {
        $this->rulesTree($form, $subrules, $maxWeight, $level + 1);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity = entity_load("customfilter", $form_state->getValue('fid'));
    foreach ($form_state->getValue('rules') as $rule) {
      $item = [
        'rid' => $rule['rid'],
        'prid' => $rule['prid'],
        'fid' => $this->entity->id(),
        'enabled' => $rule['enabled'],
        'weight' => $rule['weight'],
      ];
      $this->entity->updateRule($item);
    }
    $this->entity->save();
  }

}
