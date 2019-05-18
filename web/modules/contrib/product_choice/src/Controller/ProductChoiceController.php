<?php

namespace Drupal\product_choice\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\product_choice\Entity\ProductChoiceTermInterface;
use Drupal\product_choice\Entity\ProductChoiceListInterface;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\product_choice\ProductChoiceUsageService;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for product_choice.module.
 */
class ProductChoiceController extends ControllerBase {

  /**
   * Config Factory Service Object.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Entity Type Manager Service Object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Product Choice Usage Service Object.
   *
   * @var \Drupal\product_choice\ProductChoiceUsageService
   */
  protected $productChoiceUsageService;

  /**
   * Constructs a ProductChoicesController object.
   */
  public function __construct(EntityTypeManager $entityTypeManager,
    ProductChoiceUsageService $productChoiceUsageService,
    ConfigFactory $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->productChoiceUsageService = $productChoiceUsageService;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('product_choice.usage_service'),
      $container->get('config.factory')
    );
  }

  /**
   * Returns a form to add a new choice term to a product choice list.
   *
   * @param \Drupal\product_choice\ProductChoiceListInterface $product_choice_list
   *   The product choice list this choice term will be added to.
   *
   * @return array
   *   The product choice term add form.
   */
  public function addForm(ProductChoiceListInterface $product_choice_list) {
    $term = $this->entityTypeManager->getStorage('product_choice_term')
      ->create(['lid' => $product_choice_list->id()]);
    return $this->entityFormBuilder()->getForm($term);
  }

  /**
   * Returns list of commerce products that use a given product choice term.
   *
   * @param Drupal\product_choice\Entity\ProductChoiceTermInterface $product_choice_term
   *   The product choice term usage to display.
   *
   * @return array
   *   A render array representing the list of commerce products.
   */
  public function productUsage(ProductChoiceTermInterface $product_choice_term) {

    $product_ids = $this->productChoiceUsageService->getProducts($product_choice_term);
    $products = $this->entityTypeManager->getStorage('commerce_product')
      ->loadMultiple($product_ids);

    $rows = [];
    foreach ($products as $product) {
      // Code copied from \Drupal\commerce_product\ProductListBuilder.
      $product_type = ProductType::load($product->bundle());

      $row = [];
      $row['title']['data'] = [
        '#type' => 'link',
        '#title' => $product->label(),
      ] + $product->toUrl()->toRenderArray();
      $row['type'] = $product_type->label();
      $row['status'] = $product->isPublished() ? $this->t('Published') : $this->t('Unpublished');

      $rows[] = $row;
    }

    return [
      '#type' => 'table',
      '#header' => [$this->t('Title'), $this->t('Type'), $this->t('Status')],
      '#rows' => $rows,
      '#empty' => $this->t('This term is not currently being used by any products.'),
    ];
  }

  /**
   * Returns a list of product choice terms.
   *
   * @param Drupal\product_choice\Entity\ProductChoiceListInterface $product_choice_list
   *   The product choice list terms to display.
   *
   * @return array
   *   A render array representing the list of product choice terms.
   */
  public function listTerms(ProductChoiceListInterface $product_choice_list) {

    $config_name = 'core.entity_form_display.product_choice_term.'
      . $product_choice_list->id() . '.default';
    $config = $this->configFactory->get($config_name);

    // Hide columns that don't appear on the default data entry form.
    $hidden_columns = $config->get('hidden');
    if (!$hidden_columns) {
      $hidden_columns = [];
    }

    // Get style name for icon image display.
    $style_name = 'thumbnail';
    if (!isset($hidden_columns['icon'])) {
      $config_content = $config->get('content');
      if ($config_content) {
        $style_name = $config_content['icon']['settings']['preview_image_style'];
      }
      if ($style_name == '') {
        $style_name = 'thumbnail';
      }
    }

    $header = [];
    // Always show main term label.
    $header['term'] = [
      'data' => $this->t('Term'),
    ];

    if (!isset($hidden_columns['shortened'])) {
      $header['shortened'] = [
        'data' => $this->t('Shortened'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ];
    }

    if (!isset($hidden_columns['formatted'])) {
      $header['formatted'] = [
        'data' => $this->t('Formatted'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ];
    }

    if (!isset($hidden_columns['icon'])) {
      $header['icon'] = [
        'data' => $this->t('Icon'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ];
    }

    $header['operations'] = [
      'data' => $this->t('Operations'),
    ];

    $rows = [];
    $terms = $this->entityTypeManager->getStorage('product_choice_term')
      ->loadByProperties(['lid' => $product_choice_list->id()]);

    foreach ($terms as $term) {
      $row = [];
      $row['term'] = [
        'data' => [
          '#plain_text' => $term->label(),
        ],
      ];
      if (!isset($hidden_columns['shortened'])) {
        $row['shortened'] = [
          'data' => [
            '#plain_text' => $term->getShortened(),
          ],
        ];
      }
      if (!isset($hidden_columns['formatted'])) {
        $row['formatted'] = [
          'data' => [
            '#type' => 'processed_text',
            '#text' => $term->getFormattedText(),
            '#format' => $term->getFormattedFormat(),
          ],
        ];
      }
      if (!isset($hidden_columns['icon'])) {
        if (isset($term->icon->entity)) {
          $row['icon'] = [
            'data' => [
              '#theme' => 'image_style',
              '#style_name' => $style_name,
              '#uri' => $term->icon->entity->getFileUri(),
              '#title' => $term->getLabel(),
            ],
          ];
        }
        else {
          $row['icon'] = [
            'data' => [
              '#plain_text' => $this->t('N/A'),
            ],
          ];
        }
      }
      $links = [];
      $links['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('entity.product_choice_term.edit_form', [
          'product_choice_term' => $term->id(),
          'product_choice_list' => $product_choice_list->id(),
        ]),
      ];
      $links['usage'] = [
        'title' => $this->t('Usage'),
        'url' => Url::fromRoute('entity.product_choice_term.usage_list', [
          'product_choice_term' => $term->id(),
          'product_choice_list' => $product_choice_list->id(),
        ]),
      ];
      $links['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('entity.product_choice_term.delete_form', [
          'product_choice_term' => $term->id(),
          'product_choice_list' => $product_choice_list->id(),
        ]),
      ];
      $row['operations'] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ];
      $rows[] = $row;
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No terms available.'),
      '#sticky' => TRUE,
    ];
  }

}
