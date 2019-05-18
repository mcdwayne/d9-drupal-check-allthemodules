<?php

namespace Drupal\opencalais_ui;

class JsonProcessor {

  /**
   * The decoded Open Calais response.
   *
   * @var array
   */
  protected $decoded_response;

  /**
   * The keywords found for the analyzed text.
   *
   * @var array
   */
  protected $keywords = [];

  /**
   * Parse the Json response. It is processed in two stages. The first stage
   * identifies all entities, events, and facts. The second stage adds relevance
   * and geo info to those previously identified terms. The 2nd pass is required
   * because sometimes the relevance/geo data appears in the document before the
   * term has been identified.
   *
   * @param $json
   *    The json to parse
   *
   * @return array
   *    An array of CalaisMetadata objects.
   */
  public function parse_json($json) {
    $this->decoded_response = json_decode($json, TRUE);
    $this->build_entities();
    return $this->keywords;
  }

  /**
   * Build the set of entities from this RDF triples returned from Calais.
   */
  protected function build_entities() {
    foreach ($this->decoded_response as $guid => $data) {
      if (isset($data['_typeGroup'])) {
        switch ($data['_typeGroup']) {
          case 'socialTag':
            $this->extractSocialTags($guid, $data);
            break;
          case 'topics':
            $this->extractTopicTags($guid, $data);
            break;
          case 'industry':
            $this->extractIndustryTags($guid, $data);
            break;
          case 'entities':
            $this->extractEntities($guid, $data);
            break;
          case 'relations':
            // @todo implement extractRelations().
            break;
          case 'language':
            $this->extractDefaultLangID($guid, $data);
            break;
        }
      }
    }
    return $this->keywords;
  }

  /**
   * Extracts Social Tags from the returned data.
   *
   * @param $guid
   *   The guid for the current Calais Term
   * @param $data
   *   The indexed triple for the current Calais Term/GUID
   */
  protected function extractSocialTags($guid, $data) {
    $tag_val = $data['name'];
    $importance = $data['importance'];
    $this->keywords['social_tags'][$tag_val] = [
      'name' => $tag_val,
      'importance' => $importance
    ];
  }

  /**
   * Extracts Topic Tags from the returned data.
   *
   * @param $guid
   *   The guid for the current Calais Term
   * @param $data
   *   The indexed triple for the current Calais Term/GUID
   */
  protected function extractTopicTags($guid, $data) {
    $tag_val = $data['name'];
    $score = $data['score'];
    $this->keywords['topic_tags'][$tag_val] = [
      'name' => $tag_val,
      'score' => $score
    ];
  }

  /**
   * Extracts Industry Tags from the returned data.
   *
   * @param $guid
   *   The guid for the current Calais Term
   * @param $data
   *   The indexed triple for the current Calais Term/GUID
   */
  protected function extractIndustryTags($guid, $data) {
    $tag_val = $data['name'];
    $relevance = $data['relevance'];
    $this->keywords['industry_tags'][$tag_val] = [
      'name' => $tag_val,
      'relevance' => $relevance
    ];
  }

  /**
   * Extracts the entities from the returned data.
   *
   * @param $guid
   *   The guid for the current Calais Term
   * @param $data
   *   The indexed triple for the current Calais Term/GUID
   */
  protected function extractEntities($guid, $data) {
    $entity_type = $data['_type'];
    $entity_value = [
      'name' => $data['name'],
      'relevance' => $data['relevance']
    ];
    $this->keywords['entities'][$entity_type][] = $entity_value;
  }

  /**
   * Extracts the Default Language from the returned data.
   *
   * @param $guid
   *   The guid for the current Calais Term
   * @param $data
   *   The indexed triple for the current Calais Term/GUID
   */
  protected function extractDefaultLangID($guid, $data) {
    $tag_val = $data['language'];
    $this->keywords['default_language'][$tag_val] = $tag_val;
  }

}
