<?php

namespace Drupal\aws_cloud\Service;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The renderer service for instance type price table.
 */
class InstanceTypePriceTableRenderer {

  /**
   * Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  private $request;

  /**
   * The price data provider.
   *
   * @var \Drupal\aws_cloud\Service\InstanceTypePriceDataProvider
   */
  protected $dataProvider;

  /**
   * Constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request Object.
   * @param \Drupal\aws_cloud\Service\InstanceTypePriceDataProvider $data_provider
   *   The price data provider.
   */
  public function __construct(
    RequestStack $request_stack,
    InstanceTypePriceDataProvider $data_provider
  ) {
    $this->request = $request_stack->getCurrentRequest();
    $this->dataProvider = $data_provider;
  }

  /**
   * Render price table.
   *
   * @param string $cloud_context
   *   The cloud context.
   * @param string $instance_type
   *   The instance type such as m3.small, t3.medium and etc.
   *
   * @return array
   *   The render array of price table.
   */
  public function render($cloud_context, $instance_type = NULL) {
    $fields = $this->dataProvider->getFields();
    $data = $this->dataProvider->getData(
      $cloud_context,
      $instance_type,
      $this->request->get('sort'),
      $this->getOrderField($fields, $this->request->get('order'))
    );

    $table_header = array_map(function ($key, $value) {
      return [
        'data' => $value,
        'field' => $key,
      ];
    }, array_keys($fields), array_values($fields));

    $table = [
      '#type' => 'table',
      '#header' => $table_header,
      '#sticky' => TRUE,
      '#attributes' => [
        'class' => ['aws_cloud_instance_type_prices'],
      ],
    ];
    $table['#attached']['library'][] = 'aws_cloud/aws_cloud_instance_type_prices';

    $rows = $this->getTableRows($data, $instance_type);
    foreach ($rows as $row) {
      $table[] = $row;
    }

    return $table;
  }

  /**
   * Get rows of table.
   *
   * @return array
   *   The render array of table's rows.
   */
  private function getTableRows($rows_data, $instance_type) {
    $rows = [];
    foreach ($rows_data as $row_data) {
      $row = [];
      foreach ($row_data as $col_name => $col_val) {
        $this->buildCell($row, $col_name, $col_val);
      }

      if ($row_data['instance_type'] == $instance_type) {
        $row['#attributes'] = ['class' => ['highlight']];
      }

      $rows[] = $row;
    }

    return $rows;
  }

  /**
   * Get order field.
   *
   * @param array $fields
   *   The fields.
   * @param string $title
   *   The title of field.
   *
   * @return string
   *   The field of header.
   */
  private function getOrderField(array $fields, $title) {
    $field = NULL;
    foreach ($fields as $name => $label) {
      if ($label == $title) {
        $field = $name;
        break;
      }
    }
    return $field;
  }

  /**
   * Build cell.
   *
   * @param array &$row
   *   The render array of row.
   * @param string $cell_name
   *   The name of cell.
   * @param string $cell_value
   *   The value of cell.
   */
  private function buildCell(array &$row, $cell_name, $cell_value) {
    $row[$cell_name] = ['#markup' => $cell_value];
  }

}
