<?php


namespace Drupal\loft_data_grids;


interface DrupalExporterInterface {

    /**
     * Return a renderable build array
     *
     * @param null $page_id
     *
     * @return array
     */
    public function build($page_id = null);
}
