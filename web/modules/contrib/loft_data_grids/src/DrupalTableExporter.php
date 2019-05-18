<?php

namespace Drupal\loft_data_grids;

use AKlump\LoftDataGrids\Exporter;
use AKlump\LoftDataGrids\ExporterInterface;

/**
 * Class DrupalTableExporter
 */
class DrupalTableExporter extends Exporter implements ExporterInterface, DrupalExporterInterface {

    public $format;
    protected $extension = '.html';

    public function getInfo()
    {
        $info = parent::getInfo();
        $info = [
                'name'        => 'Drupal table formatter',
                'shortname'   => 'theme_table',
                'description' => 'Export data using theme_table().',
            ] + $info;

        return $info;
    }

    public function compile($page_id = null)
    {
        $build = $this->build($page_id);
        $this->output = \Drupal::service("renderer")->render($build);

        return $this;
    }

    public function build($page_id = null)
    {
        $pages = $this->getData()->get();
        if ($page_id && array_key_exists($page_id, $pages)) {
            $pages = [$pages[$page_id]];
        }

        $build = [];
        $page_ct = 0;
        foreach ($pages as $page_id => $data) {
            $vars['#ExportData'] = $this;
            $vars['#page_id'] = $page_id;
            $vars['attributes'] = [];
            $vars['caption'] = !empty($page_id) ? $page_id : null;
            $vars['header'] = [];
            $column_no = 1;
            foreach ($this->getHeader($page_id) as $header_key => $value) {
                $header_classes = [];
                $header_classes[] = 'column-' . $column_no;
                if ($column_no === count(reset($data)) - 1) {
                    $header_classes[] = 'last';
                }
                elseif ($column_no === 0) {
                    $header_classes[] = 'first';
                }
                $string = preg_replace('/[^a-z0-9\-\.]/', '-', strtolower($header_key));
                $string = preg_replace('/^\d/', 'c-\0', $string);
                $header_classes[] = preg_replace('/-{2,}/', '-', $string);

                $vars['header'][] = [
                    'data'  => $value,
                    'class' => $this->tableClassesHandler($header_classes),
                ];
                $column_no++;
            }
            $vars['rows'] = [];
            foreach (array_values($data) as $row_no => $row) {
                $row_classes = [];
                $row_classes[] = 'row-' . $row_no;
                if ($row_no === count($data) - 1) {
                    $row_classes[] = 'last';
                }
                elseif ($row_no === 0) {
                    $row_classes[] = 'first';
                }

                $columns = [];
                $array_keys = array_keys($row);
                foreach (array_values($row) as $column_no => $column) {
                    $column_classes = [];
                    $column_classes[] = 'column-' . $column_no;
                    if ($column_no === count($row) - 1) {
                        $column_classes[] = 'last';
                    }
                    elseif ($column_no === 0) {
                        $column_classes[] = 'first';
                    }
                    $string = preg_replace('/[^a-z0-9\-\.]/', '-', strtolower($array_keys[$column_no]));
                    $string = preg_replace('/^\d/', 'c-\0', $string);
                    $column_classes[] = preg_replace('/-{2,}/', '-', $string);

                    $columns[] = [
                        'data'  => $column,
                        'class' => $this->tableClassesHandler($column_classes),
                    ];
                }
                $vars['rows'][] = [
                    'data'  => $columns,
                    'class' => $this->tableClassesHandler($row_classes),
                ];
            }
            $build['table' . ($page_ct++ > 0 ? '_' . $page_ct : '')] = [
                '#theme'      => 'table',
                '#rows'       => $vars['rows'],
                '#header'     => $vars['header'],
                '#attributes' => $vars['attributes'],
                '#caption'    => $vars['caption'],
            ];

            \Drupal::moduleHandler()
                   ->alter('loft_data_grids_table', $build, $this, $page_id);
        }

        return $build;
    }

    /**
     * Theme table handled classes differently across versions, so this is a
     * helper.
     *
     * @param $classes
     *
     * @return string|array
     */
    protected function tableClassesHandler($classes)
    {
        return $classes;
    }
}
