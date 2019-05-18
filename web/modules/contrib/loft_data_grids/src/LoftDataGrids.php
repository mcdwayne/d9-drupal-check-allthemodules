<?php


namespace Drupal\loft_data_grids;

use AKlump\LoftDataGrids\ExportData;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class LoftDataGrids {

    use StringTranslationTrait;

    /**
     * Return an options array of exporter classes available.
     *
     * @param bool $checkAccess   Check to see if the current user has access to
     *                            each option.
     * @param bool $includeDescription
     * @param bool $useShortname  By default the label is used, set this to true
     *                            to use the shortname.
     *
     * @return array Keys are classnames, values are human options.
     */
    public function getExporterOptions($checkAccess = true, $includeDescription = true, $useShortname = true)
    {
        $options = &drupal_static(__CLASS__ . '::' . __FUNCTION__, []);
        if (empty($options)) {
            foreach ($this->getExporters($checkAccess) as $info) {

                // Do not include html it will break for select lists
                $label = '';
                $label .= $useShortname && isset($info['shortname']) ? $info['shortname'] : $info['name'];
                $label .= ' (' . $info['extension'] . ')';
                if ($includeDescription) {
                    $label .= ' ' . $info['description'];
                }
                $options[$info['id']] = $label;
            }
        }

        return $options;
    }

    /**
     * Return an array of all available exporters.
     *
     * @param bool $removeNoAccess    If $removeNoAccess is true, those
     *                                exporters which the current use does not
     *                                have access to will be removed.
     *
     * @return array Each value is an array of data about the exporter with
     *               keys:
     * - id
     * - name
     * - shortname
     * - description
     * - class
     * - extension
     * - access True if the current user has access.
     */
    public function getExporters($removeNoAccess = true)
    {
        $exporters = [];
        $cid = 'loft_data_grids:exporters';
        if ($cache = \Drupal::cache()->get($cid)) {
            $exporters = $cache->data;
        }
        else {
            $path = DRUPAL_ROOT . '/vendor/aklump/loft_data_grids/src/AKlump/LoftDataGrids/';
            $possible = file_scan_directory($path, '/.+Exporter\.php$/');
            foreach ($possible as $path => $data) {
                $class = '\\AKlump\\LoftDataGrids\\' . $data->name;
                $interfaces = class_implements($class);
                if (in_array('AKlump\LoftDataGrids\ExporterInterface', $interfaces)) {
                    $obj = new \ReflectionClass($class);
                    if (!$obj->isAbstract()) {
                        $info = new $class(new ExportData());
                        $i = $info->getInfo();
                        $id = $this->generateExporterId($i);
                        $exporters[$id] = [
                                'access' => true,
                            ] + $i;
                    }
                }
            }

            // Exporters added by our module.
            $info = new DrupalTableExporter(new ExportData());
            $i = $info->getInfo();
            $id = $this->generateExporterId($i);
            $exporters[$id] = $i;

            \Drupal::cache()->set($cid, $exporters);
        }

        // Add in the access flag for the current user.
        $user = \Drupal::currentUser();
        array_walk($exporters, function (&$info) use ($user) {
            $perm = 'loft_data_grids:' . $info['id'];
            $info['access'] = $user->hasPermission($perm);
        });

        \Drupal::moduleHandler()
               ->alter('loft_data_grids_exporters', $exporters);

        if ($removeNoAccess) {
            $exporters = array_filter($exporters, function ($value) {
                return $value['access'];
            });
        }

        return $exporters;
    }

    /**
     * Return the id for an exporter based on it's info array.
     *
     * @param array $info
     *
     * @return string
     */
    protected function generateExporterId(array &$info)
    {
        $id = md5($info['class']);
        $info = ['id' => $id] + $info;

        return $id;
    }
}
