<?php

namespace Drupal\chinese_address;

/**
 *
 */
class chineseAddressHelper
{
    const CHINESE_ADDRESS_ROOT_INDEX = 1;
    const CHINESE_ADDRESS_NULL_INDEX = 0;
    const CHINESE_ADDRESS_NAME_HIDE = "市辖区";

    /**
   *
   */
    public static function chinese_address_get_location($parentId = self::CHINESE_ADDRESS_ROOT_INDEX, $excludeNone = false, $limitIds = array()) 
    {
        $connection =\Drupal::database();
        $query = $connection->select('chinese_address', 'c')->fields('c')->condition('c.parent_id ', $parentId);
        if($limitIds) {
            $result =$query->condition('c.id ', $limitIds, 'in');
        }
        
        $result = $query->execute()->fetchAllKeyed(0, 2);
        if (!$excludeNone) {
            $result[self::CHINESE_ADDRESS_NULL_INDEX] = '--- 无----';
            ksort($result);
        }
        return $result;
    }

    /**
   *
   */
    public static  function chinese_address_get_siblings($regionId = 1) 
    {
        $connection =\Drupal::database();
      
        $subquery =$connection->select('chinese_address', 'ca')->fields(
            'ca', [
            'parent_id',
            ]
        )->condition('ca.id', $regionId);

        $result = $connection->select('chinese_address', 'c')->fields('c')->condition('c.parent_id ', $subquery, 'in')->execute()->fetchAllKeyed(0, 2);
        return $result;
    }

    /**
   *
   */
    public static function chinese_address_get_region_index($address) 
    {

        $connection =\Drupal::database();
      
        foreach ($address as $i => $a) {
            if (!in_array($i, ['province', 'city', 'county', 'street'])) {
                unset($address[$i]);
            }
        }
        $result = $connection->select('chinese_address', 'c')->fields('c')->condition('id', $address, 'IN')->execute()->fetchAllKeyed(0, 2);

        return $result;
    }
    
    /**
     *
     */
    public static function chinese_address_get_parent($ids =array())
    {
        $connection =\Drupal::database();
        $result =$connection->select('chinese_address', 'c')->fields('c')->condition('id', $ids, 'IN')->execute()->fetchAllKeyed(0, 1);      
        return $result;
    }
      
    
    public static function chinese_address_filter_none_option($address =array())
    {
        if(isset($address[self::CHINESE_ADDRESS_NULL_INDEX])) {
            unset($address[self::CHINESE_ADDRESS_NULL_INDEX]);
        }
        return $address;
    }
    
    public static function _chinese_address_get_parents($lastAddress,$depth) 
    {
        $connection =\Drupal::database();
        $select = $connection->select('chinese_address', 'c0');
        $select->addField('c0', 'id', 'c0_id');
      
        for($i =1; $i < $depth;$i++) {
            $prev = $i -1;
            $select->join('chinese_address', "c$i", "c$prev.parent_id=c$i.id");
            $select->addField("c$i", "id", "c{$i}_id");
        }
        $select->condition('c0.id', $lastAddress);
        $entries = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);
      
        return $entries;
      
    }
    

}
