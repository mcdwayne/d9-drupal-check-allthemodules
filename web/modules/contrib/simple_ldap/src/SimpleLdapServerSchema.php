<?php

/**
 * @file
 * Contains \Drupal\simple_ldap\SimpleLdapServerSchema
 */

namespace Drupal\simple_ldap;

use Drupal\simple_ldap\SimpleLdapServer;
use Drupal\Component\Utility\Unicode;

class SimpleLdapServerSchema {

  /**
   * @var string
   */
  protected $dn;

  /**
   * @var array
   */
  protected $schema;

  /**
   * @var SimpleLdapServer
   */
  protected $server;

  /**
   * Creates a SimpleLdapServerSchema object.
   *
   * @param SimpleLdapServer $server
   */
  public function __construct(SimpleLdapServer $server) {
    $this->server = $server;

    // Set the DN for finding the server schema, and provide a default if none is given.
    $subschemasubentry = $this->server->getSubschemaSubentry();
    if ($subschemasubentry) {
      $this->dn = $subschemasubentry;
    }
    else {
      $this->dn = 'cn=Subschema';
    }
  }

  /**
   * Provides a list of all attributes that can be retrieved from the server.
   *
   * @return array
   */
  protected function getAllAttributeNames() {
    return array(
      'attributeTypes',
      'dITContentRules',
      'dITStructureRules',
      'matchingRules',
      'matchingRuleUse',
      'nameForms',
      'objectClasses',
      'ldapSyntaxes',
    );
  }

  /**
   * Fetches entries of the given type.
   *
   * @param string $attribute
   *   Name of the schema attribute type to return.
   * @param string $name
   *   If specified, a single entry with this name or OID is returned.
   *
   * @return array
   *   The requested attribute list or entry.
   *
   * @throws SimpleLdapException
   */
  public function getSchemaItem($attribute, $name = NULL) {
    if ($this->schemaItemExists($attribute, $name)) {
      $attribute = Unicode::strtolower($attribute);
      if ($name === NULL) {
        return $this->schema[$attribute];
      }
      else {
        $name = Unicode::strtolower($name);
        if (isset($this->schema[$attribute][$name])) {
          // Return a named attribute.
          return $this->schema[$attribute][$name];
        }
        else {
          // Search for an alias or OID.
          foreach ($this->schema[$attribute] as $attr) {
            foreach ($attr['aliases'] as $alias) {
              if (Unicode::strtolower($alias) == Unicode::strtolower($name)) {
                return $attr;
              }
            }
            if ($attr['oid'] == $name) {
              return $attr;
            }
          }
        }
      }
    }

    throw new SimpleLdapException('The requested entry does not exist: ' . $attribute . ', ' . $name, '');
  }

  /**
   * Load the schema.
   *
   * Schema parsing can be slow, so only the attributes that are specified, and
   * are not already cached, are loaded.
   *
   * @param array $attributes
   *   A list of attributes to load. If not specified, all attributes are
   *   loaded.
   *
   * @throws SimpleLdapException
   */
  protected function loadSchema(array $attributes = array()) {
    $this->server->bind();

    // If no attributes are specified, default to all attributes.
    if (empty($attributes)) {
      $attributes = $this->getAllAttributeNames();
    }

    // Determine which attributes need to be loaded.
    $load = array();
    foreach ($attributes as $attribute) {
      $attribute = Unicode::strtolower($attribute);
      if (!isset($this->schema[$attribute])) {
        $load[] = $attribute;
      }
    }

    if (!empty($load)) {
      $result = $this->server->search($this->dn, 'objectclass=*', 'base', $load);

      // Parse the schema.
      foreach ($load as $attribute) {
        $attribute = Unicode::strtolower($attribute);
        $this->schema[$attribute] = array();

        // Get the values for each attribute.
        if (isset($result[$this->dn][$attribute])) {
          foreach ($result[$this->dn][$attribute] as $value) {
            $parsed = $this->parseSchemaValue($value);
            $this->schema[$attribute][Unicode::strtolower($parsed['name'])] = $parsed;
          }
        }
      }
    }
  }

  /**
   * Parse a schema value into a usable array.
   *
   * @param string $value
   *
   * @return array
   *  An array of meta-data about the schema attribute passed in.
   *  Some keys that might be in the array:
   *    - oid - will always be there
   *    - name
   *    - syntax
   *    - max_length
   *    - aliases
   *
   * @link
   *   http://pear.php.net/package/Net_LDAP2/
   *
   * @license
   *   http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
   */
  protected function parseSchemaValue($value) {
    // Tokens that have no associated value.
    $novalue = array(
      'single-value',
      'obsolete',
      'collective',
      'no-user-modification',
      'abstract',
      'structural',
      'auxiliary',
    );

    // Tokens that can have multiple values.
    $multivalue = array('must', 'may', 'sup');

    // Initialization.
    $schema_entry = array('aliases' => array());

    // Get an array of tokens.
    $tokens = $this->tokenize($value);

    // Remove left paren.
    if ($tokens[0] == '(') {
      array_shift($tokens);
    }

    // Remove right paren.
    if ($tokens[count($tokens) - 1] == ')') {
      array_pop($tokens);
    }

    // The first token is the OID.
    $schema_entry['oid'] = array_shift($tokens);

    // Loop through the tokens until there are none left.
    while (count($tokens) > 0) {
      $token = Unicode::strtolower(array_shift($tokens));
      if (in_array($token, $novalue)) {
        // Single value token.
        $schema_entry[$token] = 1;
      }
      else {
        // This one follows a string or a list if it is multivalued.
        if (($schema_entry[$token] = array_shift($tokens)) == '(') {
          // This creates the list of values and cycles through the tokens until
          // the end of the list is reached ')'.
          $schema_entry[$token] = array();
          while ($tmp = array_shift($tokens)) {
            if ($tmp == ')') {
              break;
            }
            if ($tmp != '$') {
              array_push($schema_entry[$token], $tmp);
            }
          }
        }
        // Create an array if the value should be multivalued but was not.
        if (in_array($token, $multivalue) && !is_array($schema_entry[$token])) {
          $schema_entry[$token] = array($schema_entry[$token]);
        }
      }
    }

    // Get the max length from syntax.
    if (array_key_exists('syntax', $schema_entry)) {
      if (preg_match('/{(\d+)}/', $schema_entry['syntax'], $matches)) {
        $schema_entry['max_length'] = $matches[1];
      }
    }

    // Force a name.
    if (empty($schema_entry['name'])) {
      $schema_entry['name'] = $schema_entry['oid'];
    }

    // Make one name the default and put the others into aliases.
    if (is_array($schema_entry['name'])) {
      $aliases = $schema_entry['name'];
      $schema_entry['name'] = array_shift($aliases);
      $schema_entry['aliases'] = $aliases;
    }

    return $schema_entry;
  }

  /**
   * Tokenizes the given value into an array of tokens.
   *
   * @param string $value
   *
   * @return array
   *
   * @link
   *   http://pear.php.net/package/Net_LDAP2/
   *
   * @license
   *   http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
   */
  protected function tokenize($value) {
    $tokens = array();
    $matches = array();

    // This one is taken from perl-lap, modified for php.
    $pattern = "/\s* (?:([()]) | ([^'\s()]+) | '((?:[^']+|'[^\s)])*)') \s*/x";

    // This one matches one big pattern wherein only one of the three subpatterns
    // matched. We are interested in the subpatterns that matched. If it matched
    // its value will be non-empty and so it is a token. Tokens may be round
    // brackets, a string, or a string enclosed by "'".
    preg_match_all($pattern, $value, $matches);

    // Loop through all tokens (full pattern match).
    for ($i = 0; $i < count($matches[0]); $i++) {
      // Loop through each sub-pattern.
      for ($j = 1; $j < 4; $j++) {
        // Pattern match in this sub-pattern.
        $token = trim($matches[$j][$i]);
        if (!empty($token)) {
          $tokens[$i] = $token;
        }
      }
    }

    return $tokens;
  }

  /**
   * Returns whether the given item exists.
   *
   * @param string $attribute
   *   Name of the schema attribute type to check.
   * @param string $name
   *   Name or OID of a single entry to check. If NULL, then this function will
   *   return whether or not the given attribute type is empty.
   *
   * @return boolean
   *   TRUE if the item exists, FALSE otherwise.
   *
   * @throw SimpleLdapException
   */
  public function schemaItemExists($attribute, $name = NULL) {
    // Make sure the schema for the requested type is loaded.
    $this->loadSchema(array($attribute));

    // Check to see if the requested schema entry exists.
    $attribute = Unicode::strtolower($attribute);
    if (isset($this->schema[$attribute])) {
      if ($name === NULL) {
        return (count($this->schema[$attribute]) > 0);
      }
      else {
        if (isset($this->schema[$attribute][Unicode::strtolower($name)])) {
          // An attribute of the given name exists.
          return TRUE;
        }
        else {
          // Search for an alias or OID.
          foreach ($this->schema[$attribute] as $attr) {
            foreach ($attr['aliases'] as $alias) {
              if (Unicode::strtolower($alias) == Unicode::strtolower($name)) {
                return TRUE;
              }
            }
            if ($attr['oid'] == $name) {
              return TRUE;
            }
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * Return default attribute mappings from LDAP to Drupal based on Server-type.
   *
   * @return array
   */
  public function getDefaultAttributeSettings() {
    if ($this->server->getServerType() == 'Active Directory') {
      return array(
        'object_class' => array('user'),
        'name_attribute' => 'samaccountname',
        'mail_attribute' => 'mail',
      );
    }
    else {
      return array(
        'object_class' => array('inetorgperson'),
        'name_attribute' => 'cn',
        'mail_attribute' => 'mail',
      );
    }
  }

    /**
     * Return a list of attributes defined for the objectclass.
     *
     * @param string $objectclass
     *   The objectclass to query for attributes.
     * @param boolean $recursive
     *   If TRUE, the attributes of the parent objectclasses will also
     *   be retrieved.
     *
     * @return array
     *  A list of MAY/MUST attributes
     */
  public function getAttributesByObjectClass($objectclass, $recursive = FALSE) {
    $may_attributes = $this->getMayAttributes($objectclass, $recursive);
    $must_attributes = $this->getMustAttributes($objectclass, $recursive);

    return array_merge($may_attributes, $must_attributes);
   }

  /**
   * Return a list of attributes specified as MAY for the objectclass.
   *
   * @param string $objectclass
   *   The objectclass to query for attributes.
   * @param boolean $recursive
   *   If TRUE, the attributes of the parent objectclasses will also be
   *   retrieved.
   *
   * @return array
   *   A list of the MAY attributes.
   *
   * @throws SimpleLdapException
   */
  private function getMayAttributes($objectclass, $recursive = FALSE) {
    $oc = $this->getSchemaItem('objectclasses', $objectclass);
    $may = array();

    if (isset($oc['may'])) {
      $may = $oc['may'];
    }

    if ($recursive && isset($oc['sup'])) {
      foreach ($oc['sup'] as $sup) {
        $may = array_merge($may, $this->getMayAttributes($sup, TRUE));
      }
    }

    return $may;
  }

  /**
   * Return a list of attributes specified as MUST for the objectclass.
   *
   * @param string $objectclass
   *   The objectclass to query for attributes.
   * @param boolean $recursive
   *   If TRUE, the attributes of the parent objectclasses will also be
   *   retrieved.
   *
   * @return array
   *   A list of the MUST attributes.
   *
   * @throws SimpleLdapException
   */
  public function getMustAttributes($objectclass, $recursive = FALSE) {
    $oc = $this->getSchemaItem('objectclasses', $objectclass);
    $must = array();

    if (isset($oc['must'])) {
      $must = $oc['must'];
    }

    if ($recursive && isset($oc['sup'])) {
      foreach ($oc['sup'] as $sup) {
        $must = array_merge($must, $this->getMustAttributes($sup, TRUE));
      }
    }

    return $must;
  }
}
