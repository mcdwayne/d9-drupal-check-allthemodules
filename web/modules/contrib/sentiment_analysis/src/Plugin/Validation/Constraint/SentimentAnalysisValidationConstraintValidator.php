<?php

namespace Drupal\sentiment_analysis\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Url;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the SentimentAnalysis constraint.
 */
class SentimentAnalysisValidationConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
  	$sentence = $items;
    global $base_url;
    if(!empty($sentence)){
    	$config = \Drupal::config('sentiment.settings');
      $api_url = $config->get('api_url');
      $api_key = $config->get('api_key');
      $api_query = $api_url . "?text=" . urlencode($sentence);
      $api_query .= "&apikey=" . $api_key;
      $api_session = curl_init($api_query);
      curl_setopt($api_session, CURLOPT_RETURNTRANSFER, TRUE);
      $apiexec = curl_exec($api_session);
      // Output of API in JSON format.
      $sentence_result = json_decode($apiexec, TRUE);
      // Insert Data to sentiment analysis table
      $sentiment = $sentence_result['aggregate']['sentiment'];
      $score = $sentence_result['aggregate']['score'];
      $current_path = \Drupal::service('path.current')->getPath();
      $current_url = $base_url.''.$current_path;
      $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
      $uid= $user->get('uid')->value;
      $query = \Drupal::database()->insert('sentiment_analysis_details');
      $query->fields(['sentiment' ,'sentence_description' ,'score' ,'uid' ,'page_url' ,'time']);
      $query->values([$sentiment, $sentence, $score, $uid, $current_url, date('Y-m-d h:i:s a')]);
      $query->execute();
      if (!empty($sentence_result)  && isset($sentence_result['aggregate'])) {
        if ($sentence_result['aggregate']['sentiment'] == 'negative') {
            foreach ($sentence_result['negative'] as $negative_list) {
              $sentiment_word = $negative_list['sentiment'];
              return $this->context->addViolation(':sentiment_word is a sentiment word.', array(":sentiment_word" => $sentiment_word));
            }
        }
      }
    }
  }
}
