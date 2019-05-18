<?php

namespace Drupal\hidden_tab\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hidden_tab\Plugable\Komponent\HiddenTabKomponentPluginManager;
use Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginManager;
use Drupal\hidden_tab\Service\HiddenTabEntityHelperInterface;
use Drupal\hidden_tab\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Hidden Tab Page layout form.
 *
 * <b>THIS MODULE</b> is adopted from core's <em>block</em> module.
 *
 * @property \Drupal\hidden_tab\Entity\HiddenTabPageInterface $entity
 *
 * @see \Drupal\hidden_tab\Entity\HiddenTabPageInterface
 * @see \Drupal\block\BlockListBuilder
 */
class LayoutForm extends EntityForm {

  /**
   * Current request.
   *
   * To get query parameter, block-placement out of.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * To find label of plugins.
   *
   * @var \Drupal\hidden_tab\Plugable\Komponent\HiddenTabKomponentPluginManager
   */
  protected $komponentMan;

  /**
   * To find templates.
   *
   * @var \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginManager
   */
  protected $templateMan;

  /**
   * To find placements.
   *
   * @var \Drupal\hidden_tab\Service\HiddenTabEntityHelperInterface
   */
  protected $entityHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct(Request $request,
                              HiddenTabEntityHelperInterface $entity_helper,
                              HiddenTabKomponentPluginManager $komponent_man,
                              HiddenTabTemplatePluginManager $template_man) {
    $this->request = $request;
    $this->entityHelper = $entity_helper;
    $this->komponentMan = $komponent_man;
    $this->templateMan = $template_man;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('hidden_tab.entity_helper'),
      $container->get('plugin.manager.hidden_tab_komponent'),
      $container->get('plugin.manager.hidden_tab_template')
    );
  }

  /**
   * Create orderable table of regions and their komponents.
   *
   * @param array $regions
   *   Regions available in the current template the form is generated for.
   *
   * @return array
   *   Render array
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function table(array $regions): array {
    $blocks = [];
    $entities = $this->entityHelper->placementsOfPage($this->entity->id());
    /** @var \Drupal\hidden_tab\Entity\HiddenTabPlacementInterface[] $entities */
    foreach ($entities as $placement) {
      $blocks[$placement->region()][$placement->id()] = [
        'label' => $placement->label(),
        'entity_id' => $placement->id(),
        'komponent' => $placement->komponent(),
        'komponent_type' => $this->komponentMan->labelOfPlugin($placement->komponentType()),
        'weight' => $placement->weight(),
        'entity' => $placement,
      ];
    }

    $form = [
      '#type' => 'table',
      '#header' => [
        $this->t('Placement'),
        $this->t('Komponent'),
        $this->t('Komponent Type'),
        $this->t('Region'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#attributes' => [
        'id' => 'blocks',
      ],
    ];

    // Weights range from -delta to +delta, so delta should be at least half
    // of the amount of blocks present. This makes sure all blocks in the same
    // region get an unique weight.
    $weight_delta = round(count($entities) / 2);

    $put_at = FALSE;
    if ($this->request->query->has('block-placement')) {
      $put_at = $this->request->query->get('block-placement');
      $form['#attached']['drupalSettings']['blockPlacement'] = $put_at;
      // Remove the block placement from the current request so that it is not
      // passed on to any redirect destinations.
      $this->request->query->remove('block-placement');
    }

    foreach ($regions as $region => $title) {
      $form['#tabledrag'][] = [
        'action' => 'match',
        'relationship' => 'sibling',
        'group' => 'block-region-select',
        'subgroup' => 'block-region-' . $region,
        'hidden' => FALSE,
      ];
      $form['#tabledrag'][] = [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'block-weight',
        'subgroup' => 'block-weight-' . $region,
      ];

      $form['region-' . $region] = [
        '#attributes' => [
          'class' => ['region-title', 'region-title-' . $region],
          'no_striping' => TRUE,
        ],
      ];
      $form['region-' . $region]['title'] = [
        '#theme_wrappers' => [
          'container' => [
            '#attributes' => ['class' => 'region-title__action'],
          ],
        ],
        '#prefix' => $title,
        '#type' => 'link',
        '#title' => $this->t('Place komponent <span class="visually-hidden">in the %region region</span>', ['%region' => $title]),
        '#url' => Url::fromRoute('hidden_tab.admin_library', [], [
          'query' => [
            'region' => $region,
            'page' => $this->entity->id(),
            'lredirect' => Utility::lRedirect(),
          ],
        ]),
        '#wrapper_attributes' => [
          'colspan' => 5,
        ],
        '#attributes' => [
          'class' => ['use-ajax', 'button', 'button--small'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 700,
          ]),
        ],
      ];

      $form['region-' . $region . '-message'] = [
        '#attributes' => [
          'class' => [
            'region-message',
            'region-' . $region . '-message',
            empty($blocks[$region]) ? 'region-empty' : 'region-populated',
          ],
        ],
      ];
      $form['region-' . $region . '-message']['message'] = [
        '#markup' => '<em>' . $this->t('No komponents in this region') . '</em>',
        '#wrapper_attributes' => [
          'colspan' => 5,
        ],
      ];

      if (isset($blocks[$region])) {
        foreach ($blocks[$region] as $info) {
          $entity_id = $info['entity_id'];

          $form[$entity_id] = [
            '#attributes' => [
              'class' => ['draggable'],
            ],
          ];
          if ($put_at && $put_at == Html::getClass($entity_id)) {
            $form[$entity_id]['#attributes']['class'][] = 'color-success';
            $form[$entity_id]['#attributes']['class'][] = 'js-block-placed';
          }
          $form[$entity_id]['info'] = [
            '#plain_text' => $info['label'],
            '#wrapper_attributes' => [
              'class' => ['block'],
            ],
          ];
          $form[$entity_id]['komponent_type'] = [
            '#plain_text' => $info['komponent_type'],
            '#wrapper_attributes' => [
              'class' => ['block'],
            ],
          ];
          $form[$entity_id]['komponent'] = [
            '#plain_text' => $info['komponent'],
            '#wrapper_attributes' => [
              'class' => ['block'],
            ],
          ];
          $form[$entity_id]['region-theme']['region'] = [
            '#type' => 'select',
            '#default_value' => $region,
            '#required' => TRUE,
            '#title' => $this->t('Region for @block komponent', ['@block' => $info['label']]),
            '#title_display' => 'invisible',
            '#options' => $regions,
            '#attributes' => [
              'class' => ['block-region-select', 'block-region-' . $region],
            ],
            '#parents' => ['blocks', $entity_id, 'region'],
          ];
          $form[$entity_id]['weight'] = [
            '#type' => 'weight',
            '#default_value' => $info['weight'],
            '#delta' => $weight_delta,
            '#title' => $this->t('Weight for @block komponent', ['@block' => $info['label']]),
            '#title_display' => 'invisible',
            '#attributes' => [
              'class' => ['block-weight', 'block-weight-' . $region],
            ],
          ];
          $form[$entity_id]['operations'] = [
            '#type' => 'operations',
            '#links' => [
              'delete' => [
                'title' => $this->t('Delete'),
                'weight' => 10,
                'url' => Url::fromRoute('entity.hidden_tab_placement.delete_form', [
                  'hidden_tab_placement' => $entity_id,
                  'lredirect' => Utility::lRedirect(),
                ]),
              ],
            ],
          ];
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form['#attached']['library'][] = 'core/drupal.tableheader';
    $form['#attached']['library'][] = 'block/drupal.block';
    $form['#attached']['library'][] = 'block/drupal.block.admin';
    $form['#attributes']['class'][] = 'clearfix';

    $regions = NULL;
    foreach ($this->templateMan->plugins() as $plugin) {
      if ($this->entity->template() === $plugin->id()) {
        /** @var \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplateInterface $plugin */
        $regions = $plugin->regions();
        break;
      }
    }
    if ($this->entity->inlineTemplate() || !$this->entity->template() || !$regions) {
      $regions = [];
      for ($i = 0; $i < $this->entity->inlineTemplateRegionCount(); $i++) {
        $regions['reg_' . $i] = $this->t('Region @r', ['@r' => $i]);
      }
    }

    $form['blocks'] = $this->table($regions);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    foreach ($form_state->getValue('blocks') as $placement => $attr) {
      /** @var \Drupal\hidden_tab\Entity\HiddenTabPlacementInterface $c */
      $c = $this->entityHelper->placement($placement);
      $c->set('weight', $attr['weight']);
      $c->set('region', $attr['region']);
      $c->save();
    }
    $message_args = ['%label' => $this->entity->label()];
    $message = $this->t("Updated Hidden Tab Page %label layout.", $message_args);
    $this->messenger()->addStatus($message);

    if (Utility::checkRedirect()) {
      $form_state->setRedirectUrl(Utility::checkRedirect());
    }
    else {
      $form_state->setRedirect('entity.hidden_tab_page.collection');
    }

    return $result;
  }

}
