<?php

/**
 * @file
 * Definition of Drupal\number\Plugin\field\formatter\NumberIntegerFormatter.
 */

namespace Drupal\social_comments\Plugin\field\formatter;

use Drupal\field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\Field\FieldItemListInterface;
use Drupal\Component\Utility\Json;

/**
 * Plugin implementation of the 'social_comments_google' formatter.
 *
 * The 'Default' formatter is different for integer fields on the one hand, and
 * for decimal and float fields on the other hand, in order to be able to use
 * different settings.
 *
 * @FieldFormatter(
 *   id = "social_comments_google",
 *   label = @Translation("Social comments google"),
 *   field_types = {
 *     "social_comments_google"
 *   },
 *   settings = {
 *     "max_comments" = "5"
 *   }
 * )
 */
class GoogleFormatter extends FormatterBase {

  /**
   * Get activity ID from URL.
   *
   * @param string $url
   *   Google activity URL.
   *
   * @return string
   *   Activity ID.
   */
  public function getActivityId($url) {
    // Get URL path.
    $url = parse_url($url, PHP_URL_PATH);
    // Explode for arguments.
    $args = explode('/', $url);

    $user_id = isset($args[1]) ? $args[1] : NULL;
    $post_key = isset($args[3]) ? $args[3] : NULL;

    $cache_key = 'social_comments:' . $this->type . ':' . $this->id . ':' . $this->viewMode . ':google:' . $post_key;
    $id = FALSE;

    if ($cache = cache()->get($cache_key)) {
      $id = $cache->data;
    }
    else {
      $response_url = url(
        'https://www.googleapis.com/plus/v1/people/' . $user_id . '/activities/public',
        array(
          'query' => array(
            'key' => $this->api_key,
          ),
        )
      );

      $request = \Drupal::httpClient()->get($response_url);

      try {
        $response = $request->send();
        $data = $response->getBody(TRUE);
      }
      catch (\Exception $e) {
        watchdog_exception('social_comments', $e, $e->getMessage(), array(), WATCHDOG_WARNING);
        return FALSE;
      }

      $result = Json::decode($data);

      if (!empty($result['items'])) {
        foreach ($result['items'] as $item) {
          if (strpos($item['url'], $post_key) && strpos($item['url'], $user_id)) {
            $id = $item['id'];

            // Set data to cache.
            cache()->set($cache_key, $id, $this->expire + REQUEST_TIME);
            break;
          }
        }
      }
    }

    return $id;
  }

  /**
   * Get comments from activity ID.
   *
   * @param string $id
   *   Activity ID.
   *
   * @return array
   *   Array with comments.
   */
  public function getComments($id) {
    $comments = array();
    $cache_key = 'social_comments:' . $this->type . ':' . $this->id . ':' . $this->viewMode . ':google:' . $id;

    if ($cache = cache()->get($cache_key)) {
      $comments = $cache->data;
    }
    else {
      $query = array(
        'key' => $this->api_key,
        'maxResults' => !empty($this->max_comments) ? $this->max_comments : NULL,
      );
      $query = array_filter($query);

      $response_url = url(
        'https://www.googleapis.com/plus/v1/activities/' . $id . '/comments',
        array(
          'query' => $query,
        )
      );

      $request = \Drupal::httpClient()->get($response_url);

      try {
        $response = $request->send();
        $data = $response->getBody(TRUE);
      }
      catch (\Exception $e) {
        drupal_set_message(t('Google comments error'), 'warning');
        watchdog_exception('social_comments', $e, $e->getMessage(), array(), WATCHDOG_WARNING);
        return FALSE;
      }

      $result = Json::decode($data);

      if (!empty($result['items'])) {
        $comments = $this->parseComments($result['items']);
        // Set data to cache.
        cache()->set($cache_key, $comments, $this->expire + REQUEST_TIME);
      }
    }

    return $comments;
  }

  /**
   * Collect data from google response.
   *
   * @param array $items
   *   JSON decoded response string.
   *
   * @return array
   *   Data with comments.
   */
  public function parseComments($items) {
    $comments = array();

    if (is_array($items)) {
      foreach ($items as $item) {
        $data = array();
        $comment = $item['object'];

        // Get user data.
        $user = !empty($item['actor']) ? $item['actor'] : NULL;

        $data['id'] = check_plain($item['id']);
        $data['username'] = !empty($user['displayName']) ? check_plain($user['displayName']) : NULL;
        $data['userphoto'] = !empty($user['image']['url']) ? filter_xss($user['image']['url']) : NULL;
        $data['text'] = filter_xss($comment['content']);
        $data['timestamp'] = strtotime($item['published']);

        $comments[] = $data;
      }
    }

    return $comments;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['max_comments'] = array(
      '#type' => 'number',
      '#title' => t('The maximum numbers of comments to display'),
      '#default_value' => $this->getSetting('max_comments'),
      '#min' => 1,
      '#max' => 10,
      '#description' => t('From 1 to 10. Leave blank to display unlimited.'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $settings = $this->getSettings();

    if (!empty($settings['max_comments'])) {
      $summary[] = t('Dispalayed @count comments', array('@count' => $settings['max_comments']));
    }
    else {
      $summary[] = t('All comments displayed');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities_items) {
    $settings = $this->getSettings();
    // @todo: replace this.
    $this->api_key = config('social_comments.settings')->get('google_api_key');
    $this->expire = config('social_comments.settings')->get('google_cache');
    $this->max_comments = $settings['max_comments'];

    foreach ($entities_items as &$entity) {
      foreach ($entity as &$item) {
        $entity_values = $entity->getEntity()->getValue();
        $this->id = $entity_values['uuid'][0]['value'];
        $this->type = $entity->getEntity()->bundle();
        $url = $item->getValue();
        $id = $this->getActivityId($url['url']);
        $item->comments = $this->getComments($id);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $element = array();
    $entity = $items->getEntity();
    $settings = $this->getSettings();
    $bundle = $entity->bundle();

    foreach ($items as $delta => $item) {
      $item = $item->getValue();
      $element[$delta] = array(
        '#theme' => 'social_comments_items',
        '#comments' => $item['comments'],
        '#type' => 'google',
        '#view_mode' => $this->viewMode,
        '#bundle' => $bundle,
      );
    }

    return $element;
  }

}
