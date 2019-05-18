<?php

namespace Drupal\roles_nested\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\Role;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @file
 */
class RolesNestedTable extends FormBase {

  public function getFormId() {
    return 'roles_nested_parent_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => $this->t('A role defines a group of users that have certain privileges. These privileges are defined on the <a href="/admin/people/permissions">Permissions page</a>. Here, you can define the names and the display sort order of the roles on your site. It is recommended to order roles from least permissive (for example, Anonymous user) to most permissive (for example, Administrator user). Users who are not logged in have the Anonymous user role. Users who are logged in have the Authenticated user role, plus any other roles granted to their user account.'),
      '#prefix' => '<div class="row">',
      '#suffix' => '</div>',
    ];
    
    $view_list   = Url::fromUri('internal:/admin/people/roles/add');
    $link_options = [
      'attributes' => [
        'class' => [
          'button',
          'button--primary', 
          'button--small',
          'action-links',
        ],
      ],
    ];

    $view_list->setOptions($link_options);
    $view_list->setOption('query', [
      'destination' => '/admin/people/roles-nested',
    ]);
    
    $form['view_list'] = ['#markup' => \Drupal::l(t('+ Add role'), $view_list, $link_options),
    '#prefix' => '<div class="row">',
      '#suffix' => '</div>',
      ];

    $form['table-row'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Machine Name'),
        $this->t('Operations'),
        $this->t('Weight'),
        $this->t('Parent'),
      ],
      '#empty' => $this->t('Sorry, There are no items!'),
      // TableDrag: Each array value is a list of callback arguments for
      // drupal_add_tabledrag(). The #id of the table is automatically
      // prepended; if there is none, an HTML ID is auto-generated.
      '#tabledrag' => [
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'row-pid',
          'source' => 'row-id',
          'hidden' => TRUE, /* hides the WEIGHT & PARENT tree columns below */
          'limit' => FALSE, // limit = 5 depth
        ],
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'row-weight',
        ],
      ],
    ];


    $results = self::getMyData();
    foreach ($results as $value) {
      // TableDrag: Mark the table row as draggable.
      $form['table-row'][$value->id]['#attributes']['class'][] = 'draggable';

      // Indent item on load.
      if (isset($value->depth) && $value->depth > 0) {
        $indentation = [
          '#theme' => 'indentation',
          '#size' => $value->depth,
        ];
      }

      // Some table columns containing raw markup.
      $form['table-row'][$value->id]['name'] = [
        '#markup' => $value->name,
        '#prefix' => !empty($indentation) ? drupal_render($indentation) : '',
      ];

      $form['table-row'][$value->id]['machine_name'] = [
        '#markup' => $value->machine_name,
      ];

      // Operation
      $edit_role  = Url::fromUri('internal:/admin/people/roles/manage/'. $value->machine_name);
      $edit_role->setOption('query', ['destination' => '/admin/people/roles-nested',]);
      $form['table-row'][$value->id]['extra_actions'] = array(
        '#type' => 'dropbutton',
        '#links' => array(
          'view_table_form' => array(
            'title' => $this
              ->t('Edit'),
            'url' => $edit_role,
          ),
          'edit_table_form' => array(
            'title' => $this
              ->t('Edit permissions'),
            'url' => Url::fromUri('internal:/admin/people/permissions/'. $value->machine_name),
          ),
        ),
      );

      // This is hidden from #tabledrag array (above).
      // TableDrag: Weight column element.
      $form['table-row'][$value->id]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for ID @id', ['@id' => $value->id]),
        '#title_display' => 'invisible',
        '#default_value' => $value->weight,
        // Classify the weight element for #tabledrag.
        '#attributes' => [
          'class' => ['row-weight'],
        ],
      ];
      $form['table-row'][$value->id]['parent']['id'] = [
        '#parents' => ['table-row', $value->id, 'id'],
        '#type' => 'hidden',
        '#value' => $value->id,
        '#attributes' => [
          'class' => ['row-id'],
        ],
      ];
      $form['table-row'][$value->id]['parent']['pid'] = [
        '#parents' => ['table-row', $value->id, 'pid'],
        '#type' => 'number',
        '#size' => 3,
        '#min' => 0,
        '#title' => $this->t('Parent ID'),
        '#default_value' => $value->pid,
        '#attributes' => [
          'class' => ['row-pid'],
        ],
      ];
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save All Changes'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => 'Cancel',
      '#attributes' => [
        'title' => $this->t('Return to TableDrag Overview'),
      ],
      '#submit' => ['::cancel'],
      '#limit_validation_errors' => [],
    ];

    return $form;
  }


  public function cancel(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('roles_nested.roles_nested_form');
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Because the form elements were keyed with the item ids from the database,
    // we can simply iterate through the submitted values.
    $submissions = $form_state->getValue('table-row');
    foreach ($submissions as $id => $item) {
      db_update('roles_nested')
        ->fields([
          'weight' => $item['weight'],
          'pid' => $item['pid'],
        ])
        ->condition('id', $id, '=')
        ->execute();
    }
  }

 
  public function getMyData() {
    
    // Get all 'root node' items (items with no parents), sorted by weight.
    $root_items = db_select('roles_nested', 't')
      ->fields('t')
      ->condition('pid', '0', '=')
      //->condition('id', 11, '<')
      ->orderBy('weight')
      ->execute()
      ->fetchAll();

    // Initialize a variable to store our ordered tree structure.
    $tree = [];

    // Depth will be incremented in our getMyTree()
    // function for the first parent item, so we start it at -1.
    $depth = -1;

    // Loop through the root item, and add their trees to the array.
    foreach ($root_items as $root_item) {
      $this->getMyTree($root_item, $tree, $depth);
    }

    return $tree;
  }

 
  public function getMyTree($item, array &$tree = [], &$depth = 0) {
    // Increase our $depth value by one.
    $depth++;

    // Set the current tree 'depth' for this item, used to calculate
    // indentation.
    $item->depth = $depth;

    // Add the item to the tree.
    $tree[$item->id] = $item;

    // Retrieve each of the children belonging to this nested demo.
    $children = db_select('roles_nested', 't')
      ->fields('t')
      ->condition('pid', $item->id, '=')
      //->condition('id', 11, '<')
      ->orderBy('weight')
      ->execute()
      ->fetchAll();

    foreach ($children as $child) {
      // Make sure this child does not already exist in the tree, to
      // avoid loops.
      if (!in_array($child->id, array_keys($tree))) {
        // Add this child's tree to the $itemtree array.
        $this->getMyTree($child, $tree, $depth);
      }
    }

    // Finished processing this tree branch.  Decrease our $depth value by one
    // to represent moving to the next branch.
    $depth--;
  }

}
