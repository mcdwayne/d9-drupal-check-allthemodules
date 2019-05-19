<?php

/**
 * @file
 * Contains \Drupal\wisski_triplify\Plugin\wisski_pipe\Processor\TriplifyLinks.
 */

namespace Drupal\wisski_triplify\Plugin\wisski_pipe\Processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\wisski_apus\AnnotationHelper;
use Drupal\wisski_pipe\ProcessorBase;
use Drupal\wisski_salz\AdapterHelper;
use Drupal\wisski_salz\Entity\Adapter;
use Drupal\wisski_salz\Plugin\wisski_salz\Engine\Sparql11Engine;
use DOMDocument;

/**
 * @Processor(
 *   id = "triplify_html_links",
 *   label = @Translation("Triplify HTML Links"),
 *   description = @Translation("Generate triples from Links in HTML."),
 *   tags = { "triplify", "html" }
 * )
 */
class TriplifyLinks extends ProcessorBase {
  
  protected $reference_properties;

  protected $write_adapter;

  protected $direct_write;


  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);
    // setConfiguration() sets default values on $this->configuration
    $this->reference_properties = $this->configuration['reference_properties'];
  }


  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $conf = array(
      'reference_properties' => $this->reference_properties,
    ) + parent::getConfiguration();
    return $conf;
  }

  
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $conf = array(
      'reference_properties' => array(),
    ) + parent::defaultConfiguration();
    return $conf;
  }

  
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    
    $form['#tree'] = TRUE;

/*    $adapters = Adapter::loadMultiple();
    $adapter_options = array("-" => $this->t("- None -"));
    foreach ($adapters as $adapter) {
      $engine = $adapter->getEngine();
      if ($engine instanceof Sparql11Engine) {
        $adapter_options[$adapter->id()] = $adapter->label();
      }
    }

    $form['write_adapter'] = array(
      '#type' => 'select',
      '#title' => $this->t('Adapter to write to'),
      '#description' => $this->t('The adapter must be a sparql adapter'),
      '#default_value' => ($this->write_adapter === NULL) ? '-' : $this->write_adapter,
      '#options' => $adapter_options,
    );
*/
    $form['reference_properties']['default_property']['property'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Reference property'),
      '#default_value' => (isset($this->reference_properties) && isset($this->reference_properties['default_property'])) ? $this->reference_properties['default_property']['property'] : '',
    );
    $form['reference_properties']['default_property']['inverse'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use property inversely'),
      '#default_value' => (isset($this->reference_properties) && isset($this->reference_properties['default_property'])) ? $this->reference_properties['default_property']['inverse'] : 0,
      '#description' => $this->t('If checked, the property will be interpreted inversely, ie. going from the referred instance to the document.'),
    );

    return $form;

  }


  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    
    $this->reference_properties = array();

    $ref_props = $form_state->getValue('reference_properties');
    if (isset($ref_props['default_property']['property']) && !empty($ref_props['default_property']['property'])) {
      $this->reference_properties['default_property'] = array(
        'property' => $ref_props['default_property']['property'],
        'inverse' => $ref_props['default_property']['inverse'],
      );
    }

/*    $this->write_adapter = $form_state->getValue('write_adapter');
    if ($this->write_adapter == '-') {
      $this->write_adapter = NULL;
    }
*/
  }

  
  /**
   * {@inheritdoc}
   */
  public function doRun() {
    
    if (isset($this->data['html'])) {
      $html_fragment = $this->data['html'];
    } elseif (isset($this->data['text'])) {
      $html_fragment = $this->data['text'];
    }
    if (empty($html_fragment)) {
      $this->data['triples'] = array();
      return;
    }
    $doc_inst = $this->data['entity_uri'];
    if (isset($this->data['disamb_uri'])) {
      $doc_inst = $this->data['disamb_uri'];
    }
    $this->write_adapter = isset($this->data['adapter_id']) ? $this->data['adapter_id'] : NULL;

    $xhtml = "<div>$html_fragment</div>"; // encapsulate: text may be xml/html fragment (leading/trailing chars or multiple root tags)
    $doc = new DOMDocument();
    $this->logDebug("Try to dom-parse fragment as xml: $xhtml");
    if (!$doc->loadXML($xhtml, LIBXML_NOERROR)) {
      $this->logDebug("Try to dom-parse fragment as html: $xhtml");
      if (!$doc->loadHTML('<?xml encoding="UTF-8">' . $xhtml)) {
        $this->logError($this->t('Could not parse text. No XML/HTML.'));
      }
    }
    $this->logDebug("Parsing complete");
    
    // gather annotations here
    $annos = array();

    // get all links
    $nodelist = $doc->getElementsByTagName('a');
    $l = $nodelist->length;
    // ... iterate them and make annos out of them
    for ($i = 0; $i < $l; $i++) {
      $a_element = $nodelist->item($i);
      if ($a_element->hasAttribute('about') || $a_element->hasAttribute('href')) {
        $anno = new \stdClass();
        $anno->body = new \stdClass();
        $anno->body->elements = array($a_element);
        $anno = AnnotationHelper::parseAnnotation($anno);
        if (!empty($anno)) {
          if (!isset($anno->id) || empty($anno->id)) {
            $anno->id = AnnotationHelper::generateAnnotationId(); 
          }
          $annos[$anno->id] = $anno;
        }
      }
    }

    $triples = $this->triplifyAnnos($annos, $doc_inst);
    
    $graph_uri = $doc_inst;
    $this->data['triples'][] = array(
      'triples' => $triples,
      //'graph' => "<$doc_inst>", // if no graph present, the doc graph is used
      'processor' => $this->getUuid(),
    );
    
  }

  
  protected function triplifyAnnos($annos, $doc_inst) {
    
    // some shortcuts
    $ref_props = $this->reference_properties;

    $triples = array();
    
    foreach ($annos as $id => $anno) {
      
      $clazz = isset($anno->target->type) ? $anno->target->type : NULL;
      
      $entity_infos = isset($anno->target->_entity_infos) ? $anno->target->_entity_infos : array();
      
      // we know that $anno->target->ref is set and that it's an array
      foreach ((array) $anno->target->ref as $ref_uri) {
        
        // we try to get the preferred uri for the write adapter
        // otherwise we use the given one
        if (isset($entity_infos[$ref_uri])) {
          $entity_id = $entity_infos[$ref_uri][0];
          if (!empty($this->write_adapter)) {
            $new_ref_uri = AdapterHelper::getUrisForDrupalId($entity_id, $this->write_adapter);
            if (!empty($new_ref_uri)) {
              $ref_uri = $new_ref_uri;
            }
          }
          // TODO: we could also get the bundle/class from [1] and use it as $clazz
        }

        // generate triple for the classification
        // if it is given explicitly
        if (!empty($clazz)) {
          $triples = "<$ref_uri> a <$clazz> .\n";
        }
 
        // generate triples for the reference statement
        // init the reference property to the default
        if (!empty($doc_inst)) {
          $ref_prop = $ref_props['default_property'];
          if (!empty($clazz) && isset($ref_props[$clazz]) && !empty($ref_props[$clazz]['property'])) {
            $ref_prop = $ref_props[$clazz];
          }
          if ($ref_prop['inverse']) {
            $triples[] = "<$ref_uri> " . $ref_prop['property'] . " <$doc_inst> .";
          } else {
            $triples[] = "<$doc_inst> " . $ref_prop['property'] . " <$ref_uri> .";
          }
        }

      }

    }

    return $triples;

  }


}






