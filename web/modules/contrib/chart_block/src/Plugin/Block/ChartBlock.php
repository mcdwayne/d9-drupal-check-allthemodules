<?php

namespace Drupal\chart_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'ChartBlock' block.
 *
 * @Block(
 *  id = "chart_block",
 *  admin_label = @Translation("Chart block"),
 * )
 */
class ChartBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = $this->getConfiguration();

    $chart_title = isset($config['chart_block_chart_title']) ? $config['chart_block_chart_title'] : '';
    $chart_type = isset($config['chart_block_chart_type']) ? $config['chart_block_chart_type'] : '';
    $chart_width = isset($config['chart_block_chart_width']) ? $config['chart_block_chart_width'] : '';
    $chart_height = isset($config['chart_block_chart_height']) ? $config['chart_block_chart_height'] : '';
    $x_axis_label = isset($config['chart_block_x_axis_label']) ? $config['chart_block_x_axis_label'] : '';
    $y_axis_label = isset($config['chart_block_y_axis_label']) ? $config['chart_block_y_axis_label'] : '';
    $chart_data = isset($config['chart_block_data']) ? $config['chart_block_data'] : '';
    $chart_div_id = 'chart-block-' . isset($config['chart_block_chart_machine_name']) ? $config['chart_block_chart_machine_name'] : '';

    return array(
      '#markup' => $this->t('<div id="@id" style="height: @heightpx; width: @widthpx;"></div>',
       array(
         '@width' => $chart_width,
         '@height' => $chart_height,
         '@id' => $chart_div_id,
       )
      ),
      '#attached' => array(
        'library' => array('chart_block/jquery.jqplot', 'chart_block/chart'),
        'drupalSettings' => array(
          'chart_block' => array(
            $chart_div_id => array(
              'chart_div_id' => $chart_div_id,
              'chart_title' => $chart_title,
              'chart_type' => $chart_type,
              'chart_x_axis_label' => $x_axis_label,
              'chart_y_axis_label' => $y_axis_label,
              'chart_data' => $chart_data,
            ),
          ),
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['chart_block_chart_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Chart title'),
      '#default_value' => isset($config['chart_block_chart_title']) ? $config['chart_block_chart_title'] : '',
    );

    $form['chart_block_chart_type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Chart type'),
      '#default_value' => isset($config['chart_block_chart_type']) ? $config['chart_block_chart_type'] : 'line',
      '#options' => array(
        'line' => $this->t('Line'),
        'bar' => $this->t('Bar'),
        'pie' => $this->t('Pie'),
      ),
      '#required' => TRUE,
    );

    $form['chart_block_chart_width'] = array(
      '#type' => 'number',
      '#title' => $this->t('Chart width'),
      '#description' => $this->t('Width in pixels'),
      '#default_value' => isset($config['chart_block_chart_width']) ? $config['chart_block_chart_width'] : '300',
      '#min' => 150,
      '#max' => 1500,
      '#required' => TRUE,
    );

    $form['chart_block_chart_height'] = array(
      '#type' => 'number',
      '#title' => $this->t('Chart height'),
      '#description' => $this->t('Height in pixels'),
      '#default_value' => isset($config['chart_block_chart_height']) ? $config['chart_block_chart_height'] : '300',
      '#min' => 150,
      '#max' => 1500,
      '#required' => TRUE,
    );

    $form['chart_block_x_axis_data'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('X axis data'),
      '#description' => $this->t('Comma separated categories e.g. A, B, C'),
      '#default_value' => isset($config['chart_block_x_axis_data']) ? $config['chart_block_x_axis_data'] : '',
      '#required' => TRUE,
    );

    $form['chart_block_x_axis_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('X axis label'),
      '#default_value' => isset($config['chart_block_x_axis_label']) ? $config['chart_block_x_axis_label'] : '',
    );

    $form['chart_block_y_axis_data'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Y axis data'),
      '#description' => $this->t('Comma seperated numeric values e.g.  1, 2, 3'),
      '#default_value' => isset($config['chart_block_y_axis_data']) ? $config['chart_block_y_axis_data'] : '',
      '#required' => TRUE,
    );

    $form['chart_block_y_axis_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Y axis label'),
      '#default_value' => isset($config['chart_block_y_axis_label']) ? $config['chart_block_y_axis_label'] : '',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

    $x_data = $form_state->getValue('chart_block_x_axis_data');
    $y_data = $form_state->getValue('chart_block_y_axis_data');
    $this->configuration['chart_block_data'] = $this->prepareData($x_data, $y_data);

    $this->configuration['chart_block_chart_machine_name'] = $form_state->getFormObject()->getEntity()->id();

    $this->configuration['chart_block_chart_title'] = $form_state->getValue('chart_block_chart_title');
    $this->configuration['chart_block_chart_type'] = $form_state->getValue('chart_block_chart_type');
    $this->configuration['chart_block_chart_width'] = $form_state->getValue('chart_block_chart_width');
    $this->configuration['chart_block_chart_height'] = $form_state->getValue('chart_block_chart_height');
    $this->configuration['chart_block_x_axis_data'] = $form_state->getValue('chart_block_x_axis_data');
    $this->configuration['chart_block_x_axis_label'] = $form_state->getValue('chart_block_x_axis_label');
    $this->configuration['chart_block_y_axis_data'] = $form_state->getValue('chart_block_y_axis_data');
    $this->configuration['chart_block_y_axis_label'] = $form_state->getValue('chart_block_y_axis_label');
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {

    $x = split(',', $form_state->getValue('chart_block_x_axis_data'));
    $y = split(',', $form_state->getValue('chart_block_y_axis_data'));

    if (count($x) != count($y)) {
      $form_state->setErrorByName(
       'chart_block_settings_equal',
       t('The X and Y axes must have equal number of elements.'));
    }

    for ($i = 0; $i < count($y); $i++) {
      if (is_numeric($y[$i]) == FALSE) {
        $form_state->setErrorByName(
          'chart_block_settings_numeric',
          t('The Y axis must have numeric values only.'));
      }
    }

  }

  /**
   * Prepares the data for jqPlot.
   */
  private function prepareData($x_data, $y_data) {

    $x = split(',', $x_data);
    $y = array_map('floatval', explode(',', $y_data));

    $data = array();

    for ($i = 0; $i < count($x); $i++) {
      $data[] = array($x[$i], $y[$i]);
    }

    return $data;
  }

}
