<?php

namespace Drupal\views_breadcrumb\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;



class ViewsBreadCrumbBuilder implements BreadcrumbBuilderInterface{
    /**
     * {@inheritdoc}
     */
    public function applies(RouteMatchInterface $attributes) {
        $parameters = $attributes->getParameters()->all();
        if (!empty($parameters['view_id'])) {
            return TRUE;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function build(RouteMatchInterface $route_match) {
        $breadcrumb = new Breadcrumb();
        $breadcrumb->addLink(Link::createFromRoute('Home', '<front>'));

        $path = \Drupal::service('path.current')->getPath();
        $url_object =\Drupal::service('path.validator')->getUrlIfValid($path);
        $route_name = $url_object->getRouteName();

        $query = \Drupal::database()->select('menu_tree', 'm');
        $query->fields('m');
        $query->condition('m.route_name', $route_name);
        $result = $query->execute()->fetchAssoc();
        $depth = $result['depth'];
        if(empty($result)){
            return $breadcrumb;
        }
        for($i = 1; $i< $depth; $i++){
            $query = \Drupal::database()->select('menu_tree', 'm');
            $query->fields('m');
            $query->condition('m.mlid', $result['p'.$i]);
            $result1 = $query->execute()->fetchAssoc();
            $breadcrumb->addLink(Link::createFromRoute(unserialize($result1['title']), $result1['route_name']));
        }
        return $breadcrumb;
    }

}