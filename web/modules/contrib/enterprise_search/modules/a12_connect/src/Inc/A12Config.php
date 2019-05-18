<?php
namespace Drupal\a12_connect\Inc;


class A12Config
{
    private static $config = NULL;

    private static function getConfig() {
        if (! self::$config) {
            self::$config = \Drupal::configFactory()->getEditable('a12_connect.settings');
        }
        return self::$config;
    }

    public static function getId() {
        $config = \Drupal::config('a12_connect.settings');
        $a12_id = $config->get('a12_connect_id');

        return $a12_id;
    }

    public static function getSecret() {
        $config = self::getConfig();
        $a12_secret = $config->get('a12_connect_secret');

        return $a12_secret;
    }

    public static function getIndicies() {
        $config = self::getConfig();
        $a12_indicies = $config->get('a12_connect_indicies');

        return $a12_indicies;
    }


    public static function setId($id) {
        $config = self::getConfig();
        $config->set('a12_connect_id', $id);
        $config->save();

        return TRUE;
    }

    public static function setSecret($secret) {
        $config = self::getConfig();
        $config->set('a12_connect_secret', $secret);
        $config->save();

        return TRUE;
    }

    public static function deleteConfig() {
        $config = self::getConfig();
        $config->delete();

        return TRUE;
    }

    public static function setIndicies($indicies) {
        $config = self::getConfig();
        $config->set('a12_connect_indicies', $indicies);
        $config->save();

        return TRUE;
    }

    public static function getSolrPath() {
      $config = \Drupal::config('a12_connect.settings');
      $a12_solr_path = $config->get('a12_connect_solr_path');

      return $a12_solr_path;
    }

    public static function setSolrPath($solr_path) {
      $config = self::getConfig();
      $config->set('a12_connect_solr_path', $solr_path);
      $config->save();

      return TRUE;
    }
}