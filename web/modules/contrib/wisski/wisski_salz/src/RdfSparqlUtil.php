<?php

namespace Drupal\wisski_salz;

use EasyRdf_Resource;


/**
 * Utility class for RDF and SPARQL handling
 *
 * @author Martin Scholz
 */
class RdfSparqlUtil {
  

  /** Takes an EasyRDF Sparql Result and transforms it into NTriples arrays
   * that get grouped by their graph. The resulting triples can also be used
   * in Sparql queries and are already escaped and all: URIs are without prefix
   * and between '<>' while bnodes begin with '_:' and literals are escaped and
   * enclosed in '"' with lang/datatype appended.
   *
   * @param result the EasyRdf_Sparql_Result. It must be a result of a SELECT
   *               query with at least the variables s p o set. (for subject, 
   *               predicate, and object, resp.) Optionally, a g for the graph
   *               may be present.
   *
   * @return an array of arrays where the keys of the outer array are the graph
   *         URIs/bnodes and the inner arrays are the triples. Each item is a
   *         string containing one triple without the trailing dot.
   *         If the graph is not set, the triple is grouped into graph with an 
   *         empty string ''.
   */
  public function sparqlResultToNTriplesByGraph($result) {
    $triples_by_graph = array();
    foreach ($result as $row) {
      $graph = isset($row->g) ? ($row->g->isBNode() ? $row->g->getBNodeId() : ('<' . $row->g->getUri() . '>')) : '';
      if (!isset($triples_by_graph[$graph])) {
        $triples_by_graph[$graph] = array();
      }
      $s = $this->formatTripleSlot($row->s);
      $p = $this->formatTripleSlot($row->p);
      $o = $this->formatTripleSlot($row->o);
      $triples_by_graph[$graph][] = "$s $p $o";
    }
    return $triples_by_graph;
  }
  

  /** Takes an EasyRDF Sparql Result and transforms it into an NQuads array.
   *
   * @param result the EasyRdf_Sparql_Result. It must be a result of a SELECT
   *               query with at least the variables s p o set. (for subject, 
   *               predicate, and object, resp.) Optionally, a g for the graph
   *               may be present.
   *
   * @return an array of quads. Each item is a string containing one quad 
   *         without the trailing dot. If the graph is missing, the 4th slot
   *         is left empty resulting in a triple, effectively. (This conforms
   *         to nquads spec.)
   */
  public function sparqlResultToNQuads($result) {
    $nquads = array();
    foreach ($this->sparqlResultToNTriplesByGraph($result) as $graph => $triples) {
      foreach ($triples as $triple) {
        $nquads[] = "$triple $graph";
      }
    }
    return $nquads;
  }

  
  /** Takes an EasyRDF Resource or Literal and formats it so that it can be
   * in Sparql or various RDF formats like ntriples, nquads, turtle...
   *
   * @param thing the EasyRdf_Resource or EasyRdf_Literal
   *
   * @return a string with the formatted thing
   */
  public function formatTripleSlot($thing) {
    if ($thing instanceof EasyRdf_Resource) {
      if ($thing->isBNode()) {
        return $thing->getBNodeId();
      }
      else {
        return '<' . $thing->getUri() . '>';
      }
    }
    else {
      // a literal
      $literal = '"' . $this->escapeSparqlLiteral($thing->getValue()) .'"';
      if ($thing->getLang()) {
        return $literal . '@' . $thing->getLang();
      }
      else {
        return $literal . '^^<' . $thing->getDatatypeUri() . '>';
      }
    }
  }



  /** Escapes a string according to http://www.w3.org/TR/rdf-sparql-query/#rSTRING_LITERAL.
  * @param literal the literal as a string
  * @param escape_backslash if FALSE, the pattern will not escape backslashes.
  *    This may be used to prevent double escapes
  * @return the escaped string
  * @author Martin Scholz
  */
  public function escapeSparqlLiteral($literal, $escape_backslash = TRUE) {
    // this is the minimal escaping strategy which does not include non-ASCII
    // chars.
    // We don't use it as it is safer to also escape non-ASCII chars
    // $sic  = array("\\",   '"',   "'",   "\b",  "\f",  "\n",  "\r",  "\t");
    // $corr = array($escape_backslash ? "\\\\" : "\\", '\\"', "\\'", "\\b", "\\f", "\\n", "\\r", "\\t");
    // $literal = str_replace($sic, $corr, $literal);
    
    // we require literal to be a string. if it is another scalar we quitely 
    // cast it, all other types must fail
    if (!is_scalar($literal)) {
      $error = 'First parameter expected to be string, got ' . gettype($literal);
      throw new \IllegalArgumentException($error);
    }
    $literal = (string) $literal;
    // we use the json encoding function as json has the same escaping strategy
    // It also escapes all non-ASCII chars.
    // It adds '"' at front and end; we have to trim them.
    $escaped = json_encode($literal, JSON_UNESCAPED_SLASHES); 
    $escaped = substr($escaped, 1, -1); 
    $sic = array("'");
    $corr = array("\'");
    $escaped = str_replace($sic, $corr, $escaped);
    return $escaped;
  }


  /** Escapes the special characters for a sparql regex.
  * @param regex the pattern as a string
  * @param also_literal if TRUE, the pattern will also go through @see escapeSparqlLiteral
  * @return the escaped string
  * @author Martin Scholz
  */
  public function escapeSparqlRegex($regex, $also_literal = FALSE) {
    // we require literal to be a string. if it is another scalar we quitely 
    // cast it, all other types must fail
    if (!is_scalar($regex)) {
      $error = 'First parameter expected to be string, got ' . gettype($regex);
      throw new \IllegalArgumentException($error);
    }
    $regex = (string) $regex;
    // these are the special regex chars: \.*+?^$()[]{}|
    $sic = array('\\', '.', '*', '+', '?', '^', '$', '(', ')', '[', ']', '{', '}', '|');
    $corr = array('\\\\', '\\\\.', '\\\\*', '\\\\+', '\\\\?', '\\\\^', '\\\\$', '\\\\(', '\\\\)', '\\\\[', '\\\\]', '\\\\{', '\\\\}', '\\\\|');
    $regex = str_replace($sic, $corr, $regex);
    return $also_literal ? $this->escapeSparqlLiteral($regex) : $regex;
  }



}

