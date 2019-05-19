<?php

/**
 * @file
 * Theme functions for the uc_cart module.
 */

/**
 * Formats the cart contents table on the checkout page.
 *
 * @param array $variables
 *   An associative array containing:
 *   - show_subtotal: TRUE or FALSE indicating if you want a subtotal row
 *     displayed in the table.
 *   - items: An associative array of cart item information containing:
 *     - qty: Quantity in cart.
 *     - title: Item title.
 *     - price: Item price.
 *     - desc: Item description.
 *
 * @return string
 *   The HTML output for the cart review table.
 *
 * @ingroup themeable
 */
function theme_uc_cart_review_table(array $variables) {
  $items = $variables['items'];
  $show_subtotal = $variables['show_subtotal'];

  $subtotal = 0;

  // Set up table header.
  $header = [
    ['data' => t('Quantity'), 'class' => ['qty']],
    ['data' => t('Products'), 'class' => ['products']],
    ['data' => t('Price'), 'class' => ['price']],
  ];

  // Set up table rows.
  // @todo Replace with Views.
  foreach ($items as $item) {
    $subtotal += $item->price->value * $item->qty->value;
    $rows[] = [
      'data' => [
        [
          'data' => [
            '#theme' => 'uc_qty',
            '#qty' => $item->qty->value,
          ],
          'class' => ['qty'],
        ],
        [
          'data' => ['#markup' => $item->title->value . uc_product_get_description($item)],
          'class' => ['products'],
        ],
        [
          'data' => [
            '#theme' => 'uc_price',
            '#price' => $item->price->value * $item->qty->value,
          ],
          'class' => ['price'],
        ],
      ],
      'no_striping' => TRUE,
    ];
  }

  // Add the subtotal in the table footer.
  $footer = [];
  if ($show_subtotal) {
    $footer[] = [
      'data' => [
        // Cells.
        [
          'data' => ['#markup' => t('Subtotal:')],
          'colspan' => 2,
          'class' => ['subtotal-title'],
        ],
        [
          'data' => [
            '#theme' => 'uc_price',
            '#price' => $subtotal,
          ],
          'class' => ['price'],
        ],
      ],
      // Row attributes.
      'no_striping' => TRUE,
    ];
  }

  $table = [
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#footer' => $footer,
    '#attributes' => ['class' => ['cart-review']],
  ];

  return drupal_render($table);
}

/**
 * Themes the checkout review order page.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form, that by default includes
 *     the 'Back' and 'Submit order' buttons at the bottom of the review page.
 *   - panes: An associative array for each checkout pane that has information
 *     to add to the review page, keyed by the pane title:
 *     - <pane title>: The data returned for that pane or an array of returned
 *       data.
 *
 * @return string
 *   A string of HTML for the page contents.
 *
 * @ingroup themeable
 */
function theme_uc_cart_checkout_review(array $variables) {
  $panes = $variables['panes'];
  $form = $variables['form'];

  $build['instructions'] = [
    '#prefix' => '<p>',
    '#markup' => t("Your order is almost complete. Please review the details below and click 'Submit order' if all the information is correct.  You may use the 'Back' button to make changes to your order if necessary."),
    '#suffix' => '</p>',
  ];

  $rows = [];
  foreach ($panes as $title => $data) {
    // First pane row is the pane title.
    $rows[] = [
      'data' => [
        ['data' => $title, 'colspan' => '2'],
      ],
      'no_striping' => TRUE,
      'class' => ['pane-title-row'],
    ];
    // Subsequent pane rows may be subtitles or subtitle-data pairs.
    foreach ($data as $row) {
      if (isset($row['data'])) {
        if (isset($row['border'])) {
          $border = 'row-border-' . $row['border'];
        }
        else {
          $border = '';
        }
        $rows[] = [
          'data' => [
            ['data' => $row['title'] . ':', 'class' => ['title-col']],
            ['data' => $row['data'], 'class' => ['data-col']],
          ],
          'no_striping' => TRUE,
          'class' => [$border],
        ];
      }
      else {
        // Row is the cart review table.
        $rows[] = ['data' => [['data' => $row, 'colspan' => '2']]];
      }
    }
  }

  $rows[] = [
    'data' => [
      ['data' => $form, 'colspan' => '2'],
    ],
    'no_striping' => TRUE,
    'class' => ['review-button-row'],
  ];

  $build['review-table'] = [
    '#type' => 'table',
    '#rows' => $rows,
    '#attributes' => ['class' => ['order-review-table']],
  ];

  return drupal_render($build);
}
