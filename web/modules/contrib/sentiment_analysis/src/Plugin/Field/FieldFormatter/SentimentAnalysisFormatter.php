<?php

namespace Drupal\sentiment_analysis\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\user\Entity;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'sentimentanalysis' formatter.
 *
 * @FieldFormatter (
 *   id = "sentimentanalysis",
 *   label = @Translation("Sentiment Analysis"),
 *   field_types = {
 *     "sentimentanalysis"
 *   }
 * )
 */
class SentimentAnalysisFormatter extends FormatterBase {
   /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $settings = $this->getSettings();
    $summary[] = t('Displays the random string.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();
    $entity = $items->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    foreach ($items as $delta => $item) {
      $sentence = $item->value;
      $config = \Drupal::config('sentiment.settings');
      $api_url = $config->get('api_url');
      $api_key = $config->get('api_key');
      $api_query = $api_url . "?text=" . urlencode($sentence);
      $api_query .= "&apikey=" . $api_key;
      $api_session = curl_init($api_query);
      curl_setopt($api_session, CURLOPT_RETURNTRANSFER, TRUE);
      $apiexec = curl_exec($api_session);
      // Insert Data to sentiment analysis table
      global $base_url;
      $config = \Drupal::config('sentiment.settings');
      $api_url = $config->get('api_url');
      $api_key = $config->get('api_key');
      $current_url = Url::fromRoute('<current>');
      $path = $current_url->getInternalPath();
      $path_args = explode('/', $path);
      $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
      $uid= $user->get('uid')->value;
      // Output of API in JSON format.
      $sentence_result = json_decode($apiexec, TRUE);
      if (!empty($sentence_result) && isset($sentence_result['aggregate'])) {
        $score = $sentence_result['aggregate']['score'];
        $current_path = \Drupal::service('path.current')->getPath();
        $current_url = $base_url.''.$current_path;
        $sentiment = $sentence_result['aggregate']['sentiment'];
        // Insert analyzed Sentiment Data.
        $current_path = \Drupal::service('path.current')->getPath();
        $current_url = $base_url.''.$current_path;
        $query = \Drupal::database()->merge('sentiment_analysis_details');
        $query->key(['sentiment' => $sentiment,'sentence_description' => $sentence,'uid' => $uid,'score' => $score]);
        $query->fields(['entity_id' =>$entity->id() ,'entity_type' =>$entity_type,'bundle' => $bundle,'time' => date('Y-m-d h:i:s a')]);
        $query->execute();
      }
      // Render each element as markup.
      $element[$delta] = array(
        '#type' => 'markup',
        '#markup' => $item->value,
      );
    }

    return $element;
  }
}
