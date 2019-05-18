<?php
namespace Drupal\loft_data_grids;

use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class LoftDataGridsPermissions
 *
 * Define permissions for the module based on exporters.
 *
 * @package Drupal\loft_data_grids
 */
class LoftDataGridsPermissions {

    use StringTranslationTrait;

    /**
     * Return the permissions defined by this module.
     *
     * @return array
     */
    public function permissions()
    {
        $perms = [];
        foreach (\Drupal::service('loft_data_grids.core')
                        ->getExporters(false) as $exporter) {
            $perms['loft_data_grids:' . $exporter['id']] = [
                'title'       => $this->t('Visible in UI: @id', ['@id' => $exporter['name']]),
                'description' => Html::escape($exporter['description']),
            ];
        }

        return $perms;
    }
}
