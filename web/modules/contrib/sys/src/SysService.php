<?php

/**
 * @file
 * Contains \Drupal\sys\SysService.
 */

namespace Drupal\sys;

class SysService {

	/**
	 * Get metrics in html tables.
	 *
	 * @return mixed|null|string
	 */
	public function getTables() {
		$render = \Drupal::service('renderer');
		$size = _sys_database_size();
		$tables = _sys_tables_list();
		$disk_table = _sys_disk_usage();

		$headers = array(
			'name' => array(
				'data' => t('Table name'),
			),
			'size' => array(
				'data' => t('Table size'),
			),
		);
		$sort = tablesort_get_sort($headers);
		usort($tables, function($a, $b) use ($sort) {
			return $a['size_byte'] < $b['size_byte'];
		});

		$rows = array();
		$tables = array_slice($tables, 0, 10);

		foreach ($tables as $table) {
			unset($table['size_byte']);

			$rows[$table['name']] = $table;
		}
		$build['table'] = [
			'#type' => 'table',
			'#header' => $headers,
			'#rows' => $rows,
			'#empty' => t('No metrics available.'),
			'#prefix' => "<h2>The size of all tables in the database : {$size['size']}</h2><h4>hit 10 big tables size</h4>",
		];

		$headers = [
			'partition' => t('Partition name'),
			'percentage' => t('%'),
			'free_space' => t('Free Space'),
			'used_space' => t('Used space'),
			'partition_size' => t('Total'),
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

		$build['disk'] = [
			'#type' => 'table',
			'#header' => $headers,
			'#rows' => $rows,
			'#empty' => t('No metrics available.'),
		];

		return $render->renderPlain($build);
	}
}