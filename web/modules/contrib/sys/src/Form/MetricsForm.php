<?php

/**
 * @file
 * Contains \Drupal\sys\Form\MetricsForm.
 */

namespace Drupal\sys\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Operations with metrics.
 */
class MetricsForm extends FormBase {

	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'metrics_form';
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		// Get sys data.
		$size = _sys_database_size();
		$tables = _sys_tables_list();
		$disk_table = _sys_disk_usage();
		$memory_table = _sys_memory_usage();

		$form['metrics'] = [
			'#type' => 'vertical_tabs',
			'#default_tab' => 'metrics-disk-usage',
		];

		$form['dashboard'] = [
			'#type' => 'details',
			'#title' => $this
				->t('Dashboard'),
			'#group' => 'metrics',
			'#theme' => 'sys_dashboard',
			'#db_data' => $tables,
			'#disk_data' => $disk_table,
			'#memory_data' => $memory_table,
			'#size' => $size,
		];

		$form['tables_u'] = [
			'#type' => 'details',
			'#title' => $this
				->t('List of tables in the database'),
			'#group' => 'metrics',
		];

		$form['disk_u'] = [
			'#type' => 'details',
			'#title' => $this
				->t('Disk usage & Memory'),
			'#group' => 'metrics',
		];

    $form['php_metrics'] = [
      '#type' => 'details',
      '#title' => $this->t('Php metrics'),
      '#group' => 'metrics',
      '#theme' => 'sys_php_metrics',
    ];
		$headers = array(
			'name' => array(
				'data' => $this->t('Table name'),
			),
			'size' => array(
				'data' => $this->t('Table size'),
				'field' => 'size',
				'sort' => 'desc',
			),
		);
		$sort = tablesort_get_sort($headers);
		usort($tables, function ($a, $b) use ($sort) {
			return $a['size_byte'] < $b['size_byte'];
		});

		$rows = array();

		foreach ($tables as $table) {
			unset($table['size_byte']);

			$rows[$table['name']] = $table;
		}

		$form['tables_u']['tables'] = array(
			'#type' => 'tableselect',
			'#header' => $headers,
			'#options' => $rows,
			'#empty' => $this->t('No metrics available.'),
			'#prefix' => "<h2>The size of all tables in the database : {$size['size']}</h2>"
		);

		$form['disk_u']['disk'] = [
			'#type' => 'details',
			'#title' => t('Disk Usage'),
			'#group' => 'advanced',
			'#open' => TRUE,
		];

		$form['disk_u']['memory'] = [
			'#type' => 'details',
			'#title' => t('Memory Usage'),
			'#group' => 'advanced',
			'#open' => TRUE,
		];

		$headers = [
			'partition' => $this->t('Partition name'),
			'percentage' => $this->t('%'),
			'free_space' => $this->t('Free Space'),
			'used_space' => $this->t('Used space'),
			'partition_size' => $this->t('Total'),
		];

		$memory_headers = [
			'name' => $this->t('Memory name'),
			'used' => $this->t('Used Memory'),
			'free' => $this->t('Free Memory'),
			'cached' => $this->t('Cached Memory'),
			'total' => $this->t('Total'),
		];

		$rows = [];

		foreach ($disk_table as $table) {
			$rows[$table[0]] = [
				'partition' => $table[0],
				'percentage' => $table[1],
				'free_space' => $table[2],
				'used_space' => $table[3],
				'partition_size' => $table[4],
			];
		}

		$form['disk_u']['disk']['tables'] = [
			'#type' => 'tableselect',
			'#header' => $headers,
			'#options' => $rows,
			'#empty' => $this->t('No metrics available.'),
		];

		$memory_rows = [];
		foreach ($memory_table as $table) {
			$memory_rows[$table['name']] = $table;
		}

		$form['disk_u']['memory']['tables'] = [
			'#type' => 'tableselect',
			'#header' => $memory_headers,
			'#options' => $memory_rows,
			'#empty' => $this->t('No metrics available.'),
		];

		$form['#attached']['library'][] = 'sys/sys_js';
		$form['#attached']['drupalSettings']['sys']['disk_u'] = $disk_table;
		$form['#attached']['drupalSettings']['sys']['db_u'] = $tables;
		$form['#attached']['drupalSettings']['sys']['memory_u'] = $memory_table;

		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$form_state->getValue('tables');
	}

}
