<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 6/7/18
 * Time: 5:51 PM
 */

namespace Drupal\drupal_helper;


class DrupalQuery
{
    public $query;
    public function __construct()
    {
    }
    /**@param type entity or Query Manager Object**/
    public function query_init($entity_type)
    {   $query_factory =null ;
        if(is_string($entity_type)){
        $query_factory = \Drupal::entityTypeManager()->getStorage($entity_type)->getQuery();
        }
        if(is_object($entity_type)){
            $query_factory = $entity_type ;
        }
        $this->query = $query_factory;
        return $this->query;
    }
    public function query_execute()
    {
        return $this->query->execute();
    }
    public function sort_query($field, $order = 'desc')
    {
        $this->query->sort($field, $order);
    }
    public function node_type($node)
    {
        $this->query->condition('type', $node);
    }
    public function pager_query($limit = 12, $order = 0)
    {
        $this->query->pager($limit, $order);
    }
    public function range_query($start, $limit)
    {
        $this->query->range($start, $limit);
    }
    public function get_query()
    {
        return $this->query;
    }
    public function add_filter($field, $value, $operator = '=')
    {
        $this->query->condition($field, $value, $operator);
    }
    public function add_filter_not_in($field, $value, $operator = '!=')
    {
        $this->query->condition($field, $value, $operator);
    }
    public function seach_node_by_title($keyword, $operator = 'CONTAINS')
    {
        $this->query->condition('title', $keyword, $operator);
    }
    /**@Param $filters[] = ['operator'=> YOUR-OPERATOR,'field'=>YOUR-FIELD-NAME,'field_value'=>YOUR-FIELD-VALUE ]**/
    public function add_filter_multi($filters, $conjunction = 'OR')
    {
        if ($conjunction == 'AND' || $conjunction == 'and') {
            $group = $this->query->andConditionGroup();
        } else {
            $group = $this->query->orConditionGroup();
        }
        foreach ($filters as $key => $filter) {
            if (isset($filter['operator']) && $filter['operator'] != null) {
                $group->condition($filter['field'], $filter['value'], $filter['operator']);
            } else {
                //for example $group ->condition('field_tags.entity.name', 'cats');
                $group->condition($filter['field'], $filter['value']);
            }
        }
        $this->query->condition($group);
    }
}