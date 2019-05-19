<?php

namespace Drupal\spectra_flat\Plugin\Spectra;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Component\Utility\Xss;
use Drupal\spectra\SpectraPluginInterface;
use Drupal\spectra_flat\Entity\SpectraFlatStatement;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @SpectraPlugin(
 *   id = "spectra_flat_plugin",
 *   label = @Translation("DefaultSpectraPlugin"),
 * )
 */
class SpectraFlatPlugin extends PluginBase implements SpectraPluginInterface {

  /**
   * @return string
   *   A string description of the plugin.
   */
  public function description()
  {
    return $this->t('Default Spectra API Plugin');
  }

  /**
   * {@inheritdoc}
   */
  public function handlePostRequest(Request $request) {
    $content = json_decode($request->getContent(), TRUE);

    // Statement Values
    $s = array();

    // Set the types correctly if the short form was given
    foreach (array('time', 'type', 'type_secondary', 'data') as $k) {
      if (isset($content[$k])) {
        $s['flat_statement_' . $k] = $content[$k];
      }
      elseif (isset($content['flat_statement_' . $k])) {
        $s['flat_statement_' . $k] = $content['flat_statement_' . $k];
      }
    }

    // JSON-encode that JSON data
    if (isset($s['flat_statement_data'])) {
      $s['flat_statement_data'] = json_encode($s['flat_statement_data']);
    }

    // Create the entity, then assign
    $statement = SpectraFlatStatement::create($s);
    $statement->save();

    return $statement;
  }

  /**
   * {@inheritdoc}
   */
  public function handleGetRequest($request) {
    $content = $request->query->all();

    foreach (array('_format', 'plugin') as $k) {
      if (isset($content[$k])) {
        unset($content[$k]);
      }
    }
    // set the types correctly if the short form was given
    foreach (array('type', 'type_op', 'type_secondary', 'type_secondary_op') as $k) {
      if (isset($content[$k])) {
        $content['flat_statement_' . $k] = $content[$k];
        unset($content[$k]);
      }
    }

    $search = array();
    foreach ($content as $key => $value) {
      if (strpos($key, '_op') !== FALSE) {
        $key = str_replace('_op', '', $key);
        $search[Xss::filter($key)]['op'] = Xss::filter($value);
      }
      else {
        $search[Xss::filter($key)]['value'] = Xss::filter($value);
      }
    }
    $entities = $this->get_spectra_entities($search);

    return $entities;
  }

  /**
   * @inheritdoc
   *
   * Gets a Spectra Entity from the database
   *
   * @param (array) search:
   *   Array of conditions with keys as fields to query, and values as associative arrays with a "value" and "op" field.
   *   The "value" field is the search value, and "op" will be a search method such as '>', 'CONTAINS' and so on
   *
   * @param (string) type:
   *   The type of Spectra entity, in a "machine_name" format.
   *
   * @see \Drupal::entityQuery()
   */
  public function get_spectra_entities($search, $type = 'spectra_flat_statement') {
    $query = \Drupal::entityQuery($type);
    foreach ($search as $field => $opts) {
      if (isset($opts['value']) && isset($opts['op'])) {
        $query->condition($field, $opts['value'], $opts['op']);
      }
      else if (isset($opts['value'])) {
        $query->condition($field, $opts['value']);
      }
    }
    $ids = $query->execute();
    return $ids;
  }


  /**
   * @inheritdoc
   */
  public function post_spectra_flat_statement($content) {

  }

}