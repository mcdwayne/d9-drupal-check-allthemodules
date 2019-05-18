<?php

namespace Drupal\cb\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteCompiler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the breadcrumbs overview administration form.
 */
class OverviewBreadcrumbs extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The breadcrumb storage.
   *
   * @var \Drupal\cb\BreadcrumbStorageInterface
   */
  protected $breadcrumbStorage;

  /**
   * The overview tree form.
   *
   * @var array
   */
  protected $overviewTreeForm = ['#tree' => TRUE];

  /**
   * Creates a OverviewBreadcrumbs form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->breadcrumbStorage = $entity_type_manager->getStorage('cb_breadcrumb');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cb_overview_breadcrumbs';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form_state->set('breadcrumbs_overview_form_parents', ['breadcrumbs']);
    $form['breadcrumbs'] = [];
    $form['breadcrumbs'] = $this->buildOverviewForm($form['breadcrumbs'], $form_state);
    $form['actions'] = ['#type' => 'actions', '#tree' => FALSE];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->submitOverviewForm($form, $form_state);
  }

  /**
   * Form constructor to manage all breadcrumbs as tree at once.
   */
  protected function buildOverviewForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->has('breadcrumbs_overview_form_parents')) {
      $form_state->set('breadcrumbs_overview_form_parents', []);
    }

    $query = db_select('cb_breadcrumb', 'cb');
    $query->addField('cb', 'bid');
    $ids = $query->execute()->fetchCol();
    $breadcrumbs = $this->breadcrumbStorage->loadMultiple($ids);
    $tree = $this->breadcrumbStorage->buildTree();

    $delta = max(count($breadcrumbs), 50);

    $form['breadcrumbs'] = [
      '#type' => 'table',
      '#theme' => 'table__breadcrumbs_overview',
      '#header' => [
        $this->t('Breadcrumb'),
        $this->t('Breadcrumb path(s)'),
        $this->t('Link path'),
        [
          'data' => $this->t('Enabled'),
          'class' => ['checkbox'],
        ],
        $this->t('Weight'),
        [
          'data' => $this->t('Operations'),
          'colspan' => 3,
        ],
      ],
      '#attributes' => [
        'id' => 'breadcrumbs-overview',
      ],
      '#tabledrag' => [
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'breadcrumb-parent',
          'subgroup' => 'breadcrumb-parent',
          'source' => 'breadcrumb-id',
          'hidden' => FALSE,
        ],
        [
          'action' => 'order',
          'relationship' => 'subling',
          'group' => 'breadcrumb-weight',
        ],
      ],
    ];

    $form['breadcrumbs']['#empty'] = $this->t('There are no chained breadcrumbs yet. <a href=":url">Add breadcrumb</a>.', [
      ':url' => $this->url('entity.cb_breadcrumb.add_form')
    ]);

    $breadcrumbs = $this->buildOverviewTreeForm($tree, $delta);
    foreach (Element::children($breadcrumbs) as $id) {
      if (isset($breadcrumbs[$id]['#item'])) {
        $element = $breadcrumbs[$id];

        $form['breadcrumbs'][$id]['#item'] = $element['#item'];

        // TableDrag: Mark the table row as draggable.
        $form['breadcrumbs'][$id]['#attributes'] = $element['#attributes'];
        $form['breadcrumbs'][$id]['#attributes']['class'][] = 'draggable';

        // TableDrag: Sort the table row according to its existing/configured weight.
        $form['breadcrumbs'][$id]['#weight'] = $element['#item']->getWeight();

        // Add special classes to be used for tabledrag.js.
        $element['parent']['#attributes']['class'] = ['breadcrumb-parent'];
        $element['weight']['#attributes']['class'] = ['breadcrumb-weight'];
        $element['id']['#attributes']['class'] = ['breadcrumb-id'];
        $form['breadcrumbs'][$id]['title'] = [
          [
            '#theme' => 'indentation',
            '#size' => ($element['depth']['#value']) - 1,
          ],
          $element['title'],
        ];
        $form['breadcrumbs'][$id]['path'] = $element['path'];
        $form['breadcrumbs'][$id]['link_path'] = $element['link_path'];
        $form['breadcrumbs'][$id]['enabled'] = $element['enabled'];
        $form['breadcrumbs'][$id]['enabled']['#wrapper_attributes']['class'] = ['checkbox', 'breadcrumb-enabled'];

        $form['breadcrumbs'][$id]['weight'] = $element['weight'];

        // Operations (dropbutton) column.
        $form['breadcrumbs'][$id]['operations'] = $element['operations'];

        $form['breadcrumbs'][$id]['id'] = $element['id'];
        $form['breadcrumbs'][$id]['parent'] = $element['parent'];
      }
    }

    return $form;
  }

  /**
   * Recursive helper function for buildOverviewForm().
   *
   * @param array $tree
   *   The tree builded by \Drupal\cb\BreadcrumbStorage::buildTree().
   * @param int $delta
   *   The default number of breadcrumbs used in the breadcrumb weight selector is 50.
   *
   * @return array
   *   The overview tree form.
   */
  protected function buildOverviewTreeForm($tree, $delta) {
    $form = &$this->overviewTreeForm;
    $tree_access_cacheability = new CacheableMetadata();
    $destination = $this->getDestinationArray();
    foreach ($tree as $element) {

      /** @var \Drupal\cb\Entity\BreadcrumbInterface $cb_breadcrumb */
      $breadcrumb = $element['breadcrumb'];
      if ($breadcrumb) {
        $id = $breadcrumb->id();
        $form[$id]['#item'] = $breadcrumb;
        $form[$id]['#attributes'] = $breadcrumb->isEnabled() ? ['class' => ['breadcrumb-enabled']] : ['class' => ['breadcrumb-disabled']];
        $form[$id]['title']['#markup'] = $breadcrumb->getName();
        if (!$breadcrumb->isEnabled()) {
          $form[$id]['title']['#suffix'] = ' (' . $this->t('disabled') . ')';
        }
        $form[$id]['path']['#markup'] = implode('</br>', $breadcrumb->pathsToArray());
        $form[$id]['link_path']['#markup'] = implode('</br>', explode("\r\n", $breadcrumb->getLinkPaths()));
        $form[$id]['enabled'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Enable @title breadcrumb', ['@title' => $breadcrumb->getName()]),
          '#title_display' => 'invisible',
          '#default_value' => $breadcrumb->isEnabled(),
        ];
        $form[$id]['weight'] = [
          '#type' => 'weight',
          '#delta' => $delta,
          '#default_value' => $breadcrumb->getWeight(),
          '#title' => $this->t('Weight for @title', ['@title' => $breadcrumb->getName()]),
          '#title_display' => 'invisible',
        ];
        $form[$id]['depth'] = [
          '#type' => 'hidden',
          '#value' => $element['depth'],
        ];
        $form[$id]['id'] = [
          '#type' => 'hidden',
          '#value' => $breadcrumb->id(),
        ];
        $form[$id]['parent'] = [
          '#type' => 'hidden',
          '#default_value' => $breadcrumb->getParentId(),
        ];
        // Build a list of operations.
        $operations = [];
        $operations = [
          'edit' => [
            'title' => $this->t('Edit'),
            'query' => $destination,
            'url' => $breadcrumb->urlInfo('edit-form'),
          ],
          'delete' => [
            'title' => $this->t('Delete'),
            'query' => $destination,
            'url' => $breadcrumb->urlInfo('delete-form'),
          ],
        ];
        if ($breadcrumb->isTranslatable()) {
          $operations['translate'] = [
            'title' => $this->t('Translate'),
            'url' => $breadcrumb->getTranslateRoute(),
          ];
        }
        $form[$id]['operations'] = [
          '#type' => 'operations',
          '#links' => $operations,
        ];
      }

      if ($element['children']) {
        $this->buildOverviewTreeForm($element['children'], $delta);
      }
    }

    $tree_access_cacheability
      ->merge(CacheableMetadata::createFromRenderArray($form))
      ->applyTo($form);

    return $form;
  }

  /**
   * Submit handler for the breadcrumbs overview form.
   */
  protected function submitOverviewForm(array $complete_form, FormStateInterface $form_state) {
    // Form API supports constructing and validating self-contained sections
    // within forms, but does not allow to handle the form section's submission
    // equally separated yet. Therefore, we use a $form_state key to point to
    // the parents of the form section.
    $parents = $form_state->get('breadcrumbs_overview_form_parents');
    $form = &NestedArray::getValue($complete_form, $parents);

    $fields = ['weight', 'parent', 'enabled'];
    $form_breadcrumbs = $form['breadcrumbs'];
    $reset_parents = [];
    foreach (Element::children($form_breadcrumbs) as $id) {
      if (isset($form_breadcrumbs[$id]['#item'])) {
        $element = $form_breadcrumbs[$id];
        $updated_values = [];
        // Update fields if its was changed.
        foreach ($fields as $field) {
          if ($element[$field]['#value'] != $element[$field]['#default_value']) {
            $updated_values[$field] = $element[$field]['#value'];
          }
        }
        if ($updated_values) {
          foreach ($updated_values as $key => $value) {
            // Reset cache of the parent with any changes.
            if ($element['#item']->getParentId() != 0 && !in_array($element['#item']->getParentId(), $reset_parents)) {
              array_push($reset_parents, $element['#item']->getParentId());
            }
            switch ($key) {
              case 'weight':
                $element['#item']->setWeight($value);
                break;
              case 'parent':
                // If new parent is not zero and it not already in reset cache add parent to the reset cache.
                if ($value != 0 && !in_array($value, $reset_parents)) {
                  array_push($reset_parents, $value);
                }
                $element['#item']->setParent($value);
                break;
              case 'enabled':
                $element['#item']->enable($value);
                break;
            }
          }
          $element['#item']->save();
        }
      }
    }
    // Reset cache for the all parents that was changed.
    if ($reset_parents) {
      $this->breadcrumbStorage->resetCache($reset_parents);
    }
  }

}
