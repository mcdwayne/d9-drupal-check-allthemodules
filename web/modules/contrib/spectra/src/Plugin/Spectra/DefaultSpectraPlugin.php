<?php

namespace Drupal\spectra\Plugin\Spectra;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Component\Utility\Xss;
use Drupal\spectra\SpectraPluginInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\spectra\Entity\SpectraData;
use Drupal\spectra\Entity\SpectraStatement;

/**
 *
 * @SpectraPlugin(
 *   id = "default_spectra_plugin",
 *   label = @Translation("DefaultSpectraPlugin"),
 * )
 */
class DefaultSpectraPlugin extends PluginBase implements SpectraPluginInterface {

  /**
   * @return string
   *   A string description of the plugin.
   */
  public function description() {
    return $this->t('Default Spectra API Plugin');
  }

  /**
   * {@inheritdoc}
   *
   * Simple helper function to determine whether array is associative
   */
  public static function is_assoc(array $array) {
    return (array_values($array) !== $array);
  }

  /**
   * {@inheritdoc}
   */
  public function handleDeleteRequest(Request $request) {
    $content = json_decode($request->getContent(), TRUE);
    if (isset($content['uuid'])) {
      $uuid = $content['uuid'];
      if (is_string($uuid) && (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) === 1)) {
        $s = \Drupal::entityTypeManager()->getStorage('spectra_statement')->loadByProperties(['uuid' => $uuid]);
        if (!empty($s)) {
          $id = array_keys($s)[0];
          $statement = SpectraStatement::load($id);
          $statement->delete();
          return 'Deleted Statement: ' . $statement->id();
        }
      }
    }
    elseif (isset($content['search'])) {
      $search = $content['search'];
      // We want to go ahead and load the statements. Data are deleted with each statement.
      $search['load_statements'] = FALSE;
      $search['include_data'] = FALSE;
      $statements = $this->search_from_request_content($search);
      $ret = [];
      foreach ($statements as $id) {
        $statement = SpectraStatement::load($id);
        if (isset($search['include_entities']) && $search['include_entities']) {
          $statement->deleteAssociatedEntities();
        }
        $statement->delete();
        $ret[] = (string) $id;
      }
      return 'The following statements were deleted: ' . implode(', ', $ret);
    }
    return 'No valid statement UUID found';
  }

  /**
   * {@inheritdoc}
   */
  public function handleGetRequest($request) {
    $content = $request->query->all();
    if (empty($content)) {
      return 'No GET parameters given. Please add some parameters to filter results.';
    }
    return $this->search_from_request_content($content);
  }

  /**
   * {@inheritdoc}
   */
  public function handlePostRequest(Request $request) {
    $content = json_decode($request->getContent(), TRUE);
    if (empty($content)) {
      return 'No POST parameters given. Please add some parameters so we can make a statement.';
    }
    $objs = array();
    // Create the referenced components
    if (isset($content['actor'])) {
      $objs['actor'] = $this->post_spectra_statement_entity($content['actor'], 'SpectraNoun');
    }
    if (isset($content['action'])) {
      $objs['action'] = $this->post_spectra_statement_entity($content['action'], 'SpectraVerb');
    }
    if (isset($content['object'])) {
      $objs['object'] = $this->post_spectra_statement_entity($content['object'], 'SpectraNoun');
    }
    if (isset($content['context'])) {
      $objs['context'] = $this->post_spectra_statement_entity($content['context'], 'SpectraNoun');
    }

    // Create the statement
    $objs['statement'] = $this->post_spectra_statement($objs, $content);

    // Finally, handle the data, as it references the statement, not the other way around
    if (isset($content['data'])) {
      $objs['data'] = $this->post_spectra_data($content['data'], $objs['statement']);
    }

    $ret = [];
    foreach ($objs as $key => $obj) {
      if ($key === 'data') {
        $ret[$key] = array();
        foreach ($obj as $data) {
          $ret[$key][] = $data->uuid();
        }
      }
      else {
        $ret[$key] = $obj->uuid();
      }
    }
    return $ret;
  }

  /**
   * Assumptions:
   *  - We are looking for Statement Entities
   *  - If we are looking at actor, action, etc. data, we assume that this will be in an array called "actor," etc.,
   *    and the keys will point to the properties associated with it, i.e. arrays will be no more than 1 level deep.
   *  - Like the POST statements, searching by entity name only may be a string. i.e. "actor = 'name'" is
   *    equal to  "actor = ['actor_name' => 'name']"
   *  - Operations for the query will be in the form of the usual key, suffixed with an "_op" . This is also true for
   *    nested items.
   * Primary GET Parameters (search parameters):
   *  - You may send fields + operators that would go into Drupal::entityQuery() conditions. For associated entities
   *    (actor, action, etc.), you may send these as an array keyed to the entity type (actor, etc.), and a string for
   *    search by name only (you may also pass _op values, i.e. actor_op, to include an operation for name-only search)
   *    * Example: ['actor' => 'name'] becomes
   *      $query->condition(actor_id.entity.name, 'name')
   *    * Example: ['actor' => ['actor_source_id' => 3, 'actor_source_id_op' = '>']] becomes
   *      $query->condition(actor_id.entity.actor_source_id, 3, '>')
   * Special GET Parameters (behaviors):
   *  - load_statements: Returns loaded statements instead of IDs.
   *  - include_entities: Returns non-data entities associated with each statement. Will mark load_statements as true.
   *  - include_data: Returns data entities associated with each statement. Will mark load_statements as true.
   *
   * @param array $content
   * @return array|int
   */
  public function search_from_request_content(array $content) {
    if (isset($content['_format'])) {
      unset($content['_format']);
    }
    $load_statements = FALSE;
    if (isset($content['load_statements'])) {
      $load_statements = $content['load_statements'] ? TRUE : FALSE;
      unset($content['load_statements']);
    }
    $include_data = FALSE;
    if (isset($content['include_data'])) {
      $include_data = $content['include_data'] ? TRUE : FALSE;
      unset($content['include_data']);
    }
    $include_entities = FALSE;
    if (isset($content['include_entities'])) {
      $include_entities = $content['include_entities'] ? TRUE : FALSE;
      unset($content['include_entities']);
    }


    // Set up search parameters.
    $search = array();
    foreach ($content as $k => $value) {
      $key = Xss::filter($k);
      $entity_keys = ['actor', 'action', 'object', 'context', 'actor_op', 'action_op', 'object_op', 'context_op'];
      $nv_map = ['action' => 'verb', 'actor' => 'noun', 'context' => 'noun', 'object' => 'noun'];
      $nv_op_map = ['action_op' => 'verb_op', 'actor_op' => 'noun_op', 'context_op' => 'noun_op', 'object_op' => 'noun_op'];
      // Set up searches for associated entities
      if (in_array($key, $entity_keys)) {
        if (strpos($key, '_op') !== FALSE) {;
          $ent = str_replace('_op', '_id', $key);
          $key = 'name';
          if (isset($search[$ent . '.entity.' . $key])) {
            $search[$ent . '.entity.' . $key]['op'] = Xss::filter($value);
          }
          else {
            $search[$ent . '.entity.' . $key] = ['op' => Xss::filter($value)];
          }
        }
        elseif (is_string($value)) {
          $ent = $key . '_id';
          $key = 'name';
          if (isset($search[$ent . '.entity.' . $key])) {
            $search[$ent . '.entity.' . $key]['value'] = Xss::filter($value);
          }
          else {
            $search[$ent . '.entity.' . $key] = ['value' => Xss::filter($value)];
          }
        }
        elseif (is_array($value) && in_array($key, ['actor', 'action', 'object', 'context'])) {
          $ent = $key . '_id';
          foreach ($value as $k2 => $val) {
            $key2 = Xss::filter($k2);
            if (!isset($search[$ent . '.entity.' . $key2])) {
              $search[$ent . '.entity.' . $key2] = [];
            }
            if (strpos($key2, '_op') !== FALSE || $key2 === 'op') {
              $key2 = str_replace('_op', '', $key2);
              $key2 = str_replace('op', '', $key2);
              $search[$ent . '.entity.' . $key2]['op'] = Xss::filter($val);
            }
            else {
              $search[$ent . '.entity.' . $key2]['value'] = Xss::filter($val);
            }
          }
        }
      }
      // Set up searches for statement data
      else {
        if (!isset($search[$key])) {
          $search[$key] = [];
        }
        if (strpos($key, '_op') !== FALSE) {
          $key = str_replace('_op', '', $key);
          $search[$key]['op'] = Xss::filter($value);
        }
        else {
          $search[$key]['value'] = Xss::filter($value);
        }
      }
    }

    $entities = $this->get_spectra_entities($search);
    if ($load_statements || $include_data || $include_entities) {
      $ret = [];

      // We always load statements if any of the special behaviors are set
      $ents = SpectraStatement::loadMultiple(array_keys($entities));
      foreach ($ents as $id => $ent) {
        $ret[$id] = $ent->toArray();
      }

      // Include Associated Entities
      if ($include_entities) {
        foreach ($ents as $id => $ent) {
          $assoc = $ent->loadAssociatedEntities();
          foreach ($assoc as $type => $assoc_entity) {
            $ret[$id][$type] = $assoc_entity->toArray();
          }
        }
      }

      // Include Data
      if ($include_data) {
        foreach ($ents as $id => $ent) {
          $data = $ent->loadAssociatedData();
          if ($data) {
            $ret[$id]['data'] = [];
            foreach ($data as $i => $d) {
              $ret[$id]['data'][$i] = $d->toArray();
            }
          }
        }
      }

      return $ret;
    }
    else {
      return $entities;
    }
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
  public function get_spectra_entities($search, $type = 'spectra_statement') {
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
   *
   * Posts Spectra entities other than Statements and Data into the database
   *
   * @see post_spectra_data
   * @see post_spectra_statement
   */
  public function post_spectra_statement_entity($data, $type) {
    $t = '\\Drupal\\spectra\\Entity\\' . $type;
    $short = $t::getSpectraEntityType('short');

    $search = array();
    if (is_string($data)) {
      $search['name']['value'] = $data;
      $search['type']['value'] = 'default';
    }
    else if (is_array($data)) {
      foreach($data as $key => $value) {
        strpos($key,'data') === FALSE ? $search[$key]['value'] = $value: NULL;
      }
    }

    // If we have a pre-existing entity, use that. Otherwise, create a new entity

    $ids = $this->get_spectra_entities($search, $t::getSpectraEntityType());

    if ($ids) {
      $id = array_shift($ids);
      $entity = $t::load($id);

      return $entity;
    }
    else {
      if (is_string($data)) {
        $short = $t::getSpectraEntityType('short');
        $new_entity = $t::create(
          array(
            'type' => 'default',
            'name' => $data,
            'data' => json_encode(array()),
          )
        );
        $new_entity->save();
        return $new_entity;
      }
      else if (is_array($data)) {
        // JSON encode the data component
        if (isset($data[$short . 'data'])) {
          $data[$short . '_data'] = json_encode($data['data']);
        }
        elseif (isset($data['data'])) {
          $data['data'] = json_encode($data['data']);
          unset($data['data']);
        }
        $new_entity = $t::create($data);
        $new_entity->save();
        return $new_entity;
      }
    }
  }

  /**
   * @inheritdoc
   */
  public function post_spectra_data($data, $statement) {
    $d = array();
    if (is_array($data)) {
      // If the array is associative, then we assume it is a single item.
      if (!self::is_assoc($data)) {
        for($i = 0; $i < count($data); $i++) {
          $di['statement_id'] = $statement->id();
          // Get the data type
          if (isset($data[$i]['data_type'])) {
            $di['data_type'] = $data[$i]['data_type'];
          }
          elseif (isset($data[$i]['type'])) {
            $di['data_type'] = $data[$i]['type'];
            unset($data[$i]['type']);
          }
          else {
            $di['data_type'] = 'default';
          }
          // Get the data
          if (isset($data[$i]['data_data'])) {
            $di['data_data'] = json_encode($data[$i]['data_data']);
          }
          elseif (isset($data[$i]['data'])) {
            $di['data_data'] = json_encode($data[$i]['data']);
          }
          else {
            $di['data_data'] = json_encode($data[$i]);
          }
          $d[] = $di;
        }
      }
      else {
        $di['statement_id'] = $statement->id();
        // Get the data type
        if (isset($data['data_type'])) {
          $di['data_type'] = $data['data_type'];
        }
        elseif (isset($data['type'])) {
          $di['data_type'] = $data['type'];
          unset($data['type']);
        }
        else {
          $di['data_type'] = 'default';
        }
        // Get the data
        if (isset($data['data_data'])) {
          $di['data_data'] = json_encode($data['data_data']);
        }
        elseif (isset($data['data'])) {
          $di['data_data'] = json_encode($data['data']);
          unset($data['type']);
        }
        else {
          $di['data_data'] = json_encode($data);
        }
        $d[] = $di;
      }
    }
    else {
      $di['statement_id'] = $statement->id();
      $di['data_type'] = 'default';
      $di['data_data'] = json_encode($data);
      $d[] = $di;
    }

    $ret = array();
    for($i = 0; $i < count($d); $i++) {
      $data_entity = SpectraData::create($d[$i]);
      $data_entity->save();
      $ret[] = $data_entity;
    }
    return $ret;
  }

  /**
   * @inheritdoc
   */
  public function post_spectra_statement($objs, $content) {
    // Create the entity, then assign
    $s = array();
    $s['statement_time'] = isset($content['time']) ? $content['time']: time();
    $s['statement_type'] = isset($content['type']) ? $content['type'] : 'default';

    foreach ($objs as $key => $obj) {
      $s[$key . '_id'] = $obj->id();
    }

    $statement = SpectraStatement::create($s);
    $statement->save();

    return $statement;
  }

}