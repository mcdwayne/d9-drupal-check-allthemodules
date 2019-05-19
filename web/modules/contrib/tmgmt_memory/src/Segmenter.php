<?php

namespace Drupal\tmgmt_memory;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\SegmenterInterface;

/**
 * This Service offers a suite of methods to add segments and filter data.
 */
class Segmenter implements SegmenterInterface {

  /**
   * Add the "tmgmt-segment" tags to each paragraph or division/section.
   *
   * This function will return the same data adding the "tmgmt-segment"
   * XML Element with an attribute "id" that will identify the segment.
   * This XML Element will be added as the parent element of each paragraph
   * "<p>" or division/section "<div>".
   *
   * @param string $data
   *   The data to segment.
   *
   * @return string
   *   A string with the data with the tags added.
   */
  protected function segmentData($data) {
    $dom = Html::load('<tmgmt-root>' . $data . '</tmgmt-root>');
    $id = 1;
    $root = $dom->getElementsByTagName('tmgmt-root');
    $childs = $root->item(0)->childNodes;
    $taggable = NULL;
    for ($i = 0; $i < $childs->length; $i++) {
      $child = $childs->item($i);
      if (in_array($child->nodeName, ['p', 'div'])) {
        if ($taggable) {
          /** @var \DOMNode $taggable */
          $parent = $taggable->parentNode;
          $element = $dom->createElement('tmgmt-root');
          $old_nodes = [];
          while ($taggable && $taggable->nodeName != $child->nodeName) {
            $old_nodes[] = $taggable;
            $element->appendChild($taggable->cloneNode(TRUE));
            $taggable = $taggable->nextSibling;
          }
          $parent->replaceChild($element, $old_nodes[0]);
          $this->addTagToNode($dom, $element, $id);
          for ($j = 1; $j < count($old_nodes); $j++) {
            $parent->removeChild($old_nodes[$j]);
          }
          $taggable = NULL;
        }
        $new_dom = Html::load($dom->saveXML($child));
        $paragraphs = $new_dom->getElementsByTagName('p');
        $this->addTagToNodes($new_dom, $paragraphs, $id);
        $paragraphs = $new_dom->getElementsByTagName('div');
        $this->addTagToNodes($new_dom, $paragraphs, $id);
        $body = $new_dom->getElementsByTagName('body');
        $node = $dom->importNode($body->item(0), TRUE);
        $child->parentNode->replaceChild($node, $child);
      }
      elseif (!$taggable && preg_match('/[A-Za-z]|[0-9]/', $child->nodeValue)) {
        $taggable = $child;
      }
    }
    if ($taggable) {
      /** @var \DOMNode $taggable */
      $parent = $taggable->parentNode;
      $element = $dom->createElement('tmgmt-root');
      $old_nodes = [];
      while ($taggable) {
        $old_nodes[] = $taggable;
        $element->appendChild($taggable->cloneNode(TRUE));
        $taggable = $taggable->nextSibling;
      }
      $parent->replaceChild($element, $old_nodes[0]);
      $this->addTagToNode($dom, $element, $id);
      for ($j = 1; $j < count($old_nodes); $j++) {
        $parent->removeChild($old_nodes[$j]);
      }
      $taggable = NULL;
    }
    $new_data = Html::serialize($dom);
    return $this->filterDataByTagName($new_data, 'tmgmt-root');
  }

  /**
   * Add the tmgmt-segment tag to the given nodes.
   *
   * @param \DOMDocument $dom
   *   The DOMDocument.
   * @param \DOMNodeList $nodes
   *   The nodes where add the tag.
   * @param int &$delta
   *   The id of the first segment.
   */
  private function addTagToNodes(\DOMDocument $dom, \DOMNodeList $nodes, &$delta) {
    for ($i = 0; $i < $nodes->length; $i++) {
      $this->addTagToNode($dom, $nodes->item($i), $delta);
    }
  }

  /**
   * Add the tmgmt-segment tag to the given node.
   *
   * @param \DOMDocument $dom
   *   The DOMDocument.
   * @param \DOMNode $node
   *   The nodes where add the tag.
   * @param int &$delta
   *   The id of the first segment.
   */
  private function addTagToNode(\DOMDocument $dom, \DOMNode $node, &$delta) {
    $parent = $node->parentNode;
    $new_node = $dom->createElement('tmgmt-segment');
    $new_node->setAttribute('id', $delta);
    $new_node->appendChild($node->cloneNode(TRUE));
    $delta++;
    $parent->replaceChild($new_node, $node);
  }

  /**
   * {@inheritdoc}
   */
  public function filterData($data) {
    return $this->filterDataByTagName($data, 'tmgmt-segment');
  }

  /**
   * Remove the given tag from the data.
   *
   * This function will return the same data removing the given
   * XML Element.
   *
   * @param string $data
   *   A string with the XML serialized data.
   * @param string $tag_name
   *   The name of the tag we want to remove from the data.
   *
   * @return string
   *   A string with the data without the tags.
   */
  private function filterDataByTagName($data, $tag_name) {
    $dom = Html::load($data);
    $nodes = $dom->getElementsByTagName($tag_name);
    $this->removeTags($nodes);
    return Html::serialize($dom);
  }

  /**
   * Remove the tmgmt-segment tag from the given nodes.
   *
   * @param \DOMNodeList $nodes
   *   The nodes where remove the tag.
   */
  private function removeTags(\DOMNodeList $nodes) {
    while ($nodes->length > 0) {
      $node = $nodes->item(0);
      $parent = $node->parentNode;
      $childs = $node->childNodes;
      if ($childs->length > 0) {
        $last_child = $childs->item($childs->length - 1);
        $parent->replaceChild($last_child, $node);
        for ($i = $childs->length - 1; $i >= 0; $i--) {
          $child = $childs->item($i);
          $parent->insertBefore($child, $last_child);
          $last_child = $child;
        }
      }
      else {
        $parent->removeChild($node);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSegmentsOfData($serialized_data) {
    $dom = Html::load($serialized_data);
    $nodes = $dom->getElementsByTagName('tmgmt-segment');
    $segments = [];
    for ($i = 0; $i < $nodes->length; $i++) {
      /** @var \DOMNode $node */
      $node = $nodes->item($i);
      $newdoc = new \DOMDocument();
      $cloned = $node->firstChild->cloneNode(TRUE);
      $newdoc->appendChild($newdoc->importNode($cloned, TRUE));
      $elements = $newdoc->getElementsByTagName('tmgmt-segment');
      for ($j = 0; $j < $elements->length; $j++) {
        /** @var \DOMNode $node */
        $item = $elements->item($j);
        $parent = $item->parentNode;
        $parent->removeChild($item);
      }
      $segments[$node->attributes->getNamedItem('id')->nodeValue] = [
        'hash' => hash('sha256', $newdoc->saveXML($newdoc->firstChild)),
        'id' => $node->attributes->getNamedItem('id')->nodeValue,
        'data' => $newdoc->saveXML($newdoc->firstChild),
      ];
    }
    return $segments;
  }

  /**
   * {@inheritdoc}
   */
  public function getSegmentedData($data) {
    /** @var \Drupal\tmgmt\Data $data_service */
    $data_service = \Drupal::service('tmgmt.data');
    $fields = $data_service->flatten($data);
    foreach ($fields as $key => $field) {
      if (isset($field['#translate']) && $field['#translate']) {
        $segmented_text = NULL;
        if (!isset($field['#segmented_text'])) {
          $segmented_text = $this->segmentData($field['#text']);
          if ($segmented_text) {
            $field['#segmented_text'] = $segmented_text;
          }
        }
        else {
          $segmented_text = $field['#segmented_text'];
        }
        if ($segmented_text && isset($field['#translation'])) {
          $segmented_text = $this->segmentData($field['#translation']['#text']);
          if ($segmented_text) {
            $field['#translation']['#segmented_text'] = $segmented_text;
          }
        }
        NestedArray::setValue($data, \Drupal::service('tmgmt.data')
          ->ensureArrayKey($key), $field);
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function validateFormTranslation(FormStateInterface &$form_state, $element, JobItemInterface $job_item) {
    if (isset($element['value'])) {
      $translation_text = $element['value']['#value'];
    }
    else {
      $translation_text = $element['#value'];
    }
    $translated_segmented_data = $this->segmentData($translation_text);
    if ($translated_segmented_data) {
      $array_key = explode('|', $element['#parents'][0]);
      $data = $job_item->getData($array_key);
      if (isset($data['#segmented_text'])) {
        $segmented_data = $data['#segmented_text'];
        $segments = $this->getSegmentsOfData($segmented_data);
        $translated_segments = $this->getSegmentsOfData($translated_segmented_data);
        if (count($segments) != count($translated_segments)) {
          $form_state->setError($element, t('The translation has different amount of segments than the source text.'));
        }
      }
    }
  }
}
