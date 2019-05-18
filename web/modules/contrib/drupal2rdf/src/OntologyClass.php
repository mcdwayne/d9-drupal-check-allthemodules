<?php

namespace Drupal\semantic_map;
use Symfony\Component\Yaml\Yaml;

class OntologyClass{

  protected $ontologies_file = __DIR__ . '/../resources/yaml/ontologies.yml';
  protected $yaml;

  public function __construct() {
    $this->yaml = new Yaml();
  }

  // returns an array of the ontologies_file
  public function getArray(){
    $array = $this->yaml->parse(file_get_contents($this->ontologies_file));
    return $array;
  }

  // takes in a String (label) and returns a single ontology array
  public function getOntology(String $key){
    $ontologies = $this->getArray();
    $ontology = $ontologies[$key];
    return $ontology;
  }

  // returns an array of all classes for given ontology
  public function getClasses(array $arr){
    $key = $arr['id'];
    $data = __DIR__ . '/../resources/onts_yaml/' . $key . '_classes.yml';
    $classes = $this->yaml->parse(file_get_contents($data));

    return $classes;
  }

  // returns an array of all properties for given ontology
  public function getProperties(array $arr){
    $key = $arr['id'];
    $data = __DIR__ . '/../resources/onts_yaml/' . $key . '_properties.yml';
    $properties = $this->yaml->parse(file_get_contents($data));

    return $properties;
  }

  // returns an array of all labels
  public function getLabels(){
    $arr = $this->getArray();
    $label = array_column($arr, 'label');
    return $label;
  }

  // returns the Label of the given ontology array
  public function getLabel(array $arr){
    $label = $arr['label'];
    return $label;
  }

  // returns an array of all id's
  public function getIds(){
    $arr = $this->getArray();
    $id = array_column($arr, 'id');
    return $id;
  }

  // return the ID of the given ontology array
  public function getId(array $arr){
    $id = $arr['id'];
    return $id;
  }

  // returns an array of all descriptions
  public function getDescriptions(){
    $arr = $this->getArray();
    $description = array_column($arr, 'description');
    return $description;
  }

  // returns the Description of the given ontology array
  public function getDescription(array $arr){
    $description = $arr['id'];
    return $description;
  }

}

?>
