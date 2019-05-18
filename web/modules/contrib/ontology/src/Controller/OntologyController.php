<?php

/**
 * @file
 * Contains \Drupal\ontology\Controller\OntologyController.
 */

namespace Drupal\ontology\Controller;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

// XML Namespaces.
define('OWL_NS_RDF', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
define('OWL_NS_OWL', 'http://www.w3.org/2002/07/owl#');
define('OWL_NS_XSD', 'http://www.w3.org/2001/XMLSchema#');
define('OWL_NS_RDFS', 'http://www.w3.org/2000/01/rdf-schema#');

/**
 * Add attribute to DOM Element using optional namespace and value prefix entity.
 */
function _ontology_attr($doc, $element, $attribute, $value, $ns = '', $entity = '') {
  if (!empty($ns)) {
    $attr = $doc->createAttributeNS($ns, $attribute);
  }
  else {
    $attr = $doc->createAttribute($attribute);
  }
  if (!empty($entity)) {
    $ent = $doc->createEntityReference($entity);
    $attr->appendChild($ent);
  }
  $attr->appendChild($doc->createTextNode($value));
  $element->setAttributeNode($attr);
}

class OntologyController extends ControllerBase {
 
  /**
   * OWL ontology callback.
   */
  public function owl() {
    // Ontology URL.
    $url = \Drupal::request()->getRequestUri();

    // XML header, doctype, entities and root node.
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml = "\r\n<!DOCTYPE rdf:RDF [\r\n";
    foreach (rdf_get_namespaces() as $prefix => $namespace) {
      $xml .= "    <!ENTITY $prefix \"$namespace\" >\r\n";
    }
    $xml .= "\r\n]>\r\n<rdf:RDF xmlns:rdf=\"" . OWL_NS_RDF . "\"></rdf:RDF>";

    $doc = new \DOMDocument('1.0', 'UTF-8');
    $doc->loadXML($xml);
    $doc->preserveWhiteSpace = FALSE;
    $doc->formatOutput = TRUE;

    // Add namespaces to root node.
    $rdf = $doc->documentElement;
    $rdf->setAttribute('xml:base', $url);
    foreach (rdf_get_namespaces() as $prefix => $namespace) {
      $rdf->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:' . $prefix, $namespace);
    }

    // Add ontology.
    $ontology = $rdf->appendChild($doc->createElementNS(OWL_NS_OWL, 'Ontology'));
    $ontology->setAttributeNS(OWL_NS_RDF, 'about', $url);

    // Entity types.
    $rdf->appendChild($doc->createComment(' Classes for Drupal entities with RDF mapping '));
    
    $entityManager = \Drupal::service('entity_field.manager');
    $field_map = $entityManager->getFieldMap();

    $entity_types = \Drupal::service('entity_type.bundle.info')->getAllBundleInfo();
    
    foreach ($entity_types as $entity_type => $bundles) {
      // Add OWL class for each entity type.
      $rdf->appendChild($doc->createComment(" Classes for {$entity_type} entities "));
      $rdf->appendChild($doc->createComment(" $url#$entity_type "));
      $class = $rdf->appendChild($doc->createElementNS(OWL_NS_OWL, 'Class'));
      $class->setAttributeNS(OWL_NS_RDF, 'about', "#$entity_type");

      // Add subclass for each bundle.
      foreach ($bundles as $bundle => $bundle_info) {
        // Add OWL class for each bundle.
        $rdf_mapping = rdf_get_mapping($entity_type, $bundle);

        // If single bundle with the same name as entity type.
        $bundle_about = $bundle;
        if ($bundle == $entity_type) {
          // Add human readable name as label.
          $class->appendChild($doc->createElementNS(OWL_NS_RDFS, 'label', $bundle_info['label']));
        }
        else {
          // Add OWL class for each bundle.
          $rdf->appendChild($doc->createComment(" $url#$bundle "));
          $subclass = $rdf->appendChild($doc->createElementNS(OWL_NS_OWL, 'Class'));
          // Add prefix to ensure unique URL for bundle class.
          if ($bundle_about == 'default') {  
            $bundle_about = $entity_type . '--' . $bundle_about;
          }
          $subclass->setAttributeNS(OWL_NS_RDF, 'about', "#$bundle_about");
          $subclass->appendChild($doc->createElementNS(OWL_NS_RDFS, 'label', $bundle_info['label']));
          $subclass_of = $subclass->appendChild($doc->createElementNS(OWL_NS_RDFS, 'subClassOf'));
          $subclass_of->setAttributeNS(OWL_NS_RDF, 'resource', "#$entity_type");
          if ($entity_type == 'commerce_product_variation') {    
             $rdf_mapping->setBundleMapping(array(
               'types' => array(
                 'schema:Offer',
               ),
            ))->setFieldMapping('price', array(
              'properties' => array(
                'schema:price',
            ),
            ));//->save();         
          }
          
          // Set bundle RDF types as OWL super classes.
          foreach (($rdf_mapping->getPreparedBundleMapping())['types'] as $type) {
            $subclass_of = $subclass->appendChild($doc->createElementNS(OWL_NS_RDFS, 'subClassOf'));
            $subclass_of->setAttributeNS(OWL_NS_RDF, 'resource', $type);
          }
        }
        
        // Get RDF mapped bundle fields.
        $mapped_fields = array();
        foreach ($field_map[$entity_type] as $field_name => $field) {
          if (isset($field['bundles'][$bundle])) {
            $field_rdf_mapping = $rdf_mapping->getPreparedFieldMapping($field_name);
            if (empty($field_rdf_mapping)) {
              continue;
            }
            // TODO: Get real field label.
            $mapped_fields[$field_name] = array(
              'type' => $field['type'],
              'label' => $field_name,
              'rdf_mapping' => $field_rdf_mapping,
            );
          }
        }
               
        // Add RDF mapped fields as OWL class properties.
        foreach ($mapped_fields as $field_name => $field) {
          $rdf->appendChild($doc->createComment(" Property for field {$field['label']}"));

          // Store Schema.org and rel type RDF mappings as OWL object properties and datatype properties for the rest.
          //if (!empty($field['rdf_mapping']['mapping_type']) && $field['rdf_mapping']['mapping_type'] == 'rel') {
            $property_type = 'ObjectProperty';
          //}
          //else {
          //  $property_type = 'DatatypeProperty';
          //}
          $property = $rdf->appendChild($doc->createElementNS(OWL_NS_OWL, $property_type));
          $property->setAttributeNS(OWL_NS_RDF, 'about', "#$field_name");

          // Add human readable name as label.
          if ($field['label'] != $field_name) {
            $property->appendChild($doc->createElementNS(OWL_NS_RDFS, 'label', $field['label']));
          }

          // Add field RDF mapping as equivalent properties.
          foreach ($field['rdf_mapping']['properties'] as $prop) {
            $equivalent_property = $property->appendChild($doc->createElementNS(OWL_NS_OWL, 'equivalentProperty'));
            $datatype = explode(':', $prop);
            _ontology_attr($doc, $equivalent_property, 'resource', $datatype[1], OWL_NS_RDF, $datatype[0]);
          }

          // OWL property domain.
          $domain = $property->appendChild($doc->createElementNS(OWL_NS_RDFS, 'domain'));
          $domain->setAttributeNS(OWL_NS_RDF, 'resource', "#$bundle_about");

          // OWL property range.
          $range = $property->appendChild($doc->createElementNS(OWL_NS_RDFS, 'range'));
          if (!empty($field['rdf_mapping']['datatype'])) {
            // Use mapped datatype, if we have one.
            $datatype = explode(':', $field['rdf_mapping']['datatype']);
          }
          else {
            // Use &xsd;string for other properties.
            $datatype = array('xsd', 'string');
          }
          _ontology_attr($doc, $range, 'resource', $datatype[1], OWL_NS_RDF, $datatype[0]);
        }
      }
    }
    
    // Return XML response.
    $response = new Response();
    $response->setContent($doc->saveXML());
    return $response;
  }
 
} 
