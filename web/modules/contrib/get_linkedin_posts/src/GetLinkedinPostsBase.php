<?php

namespace Drupal\get_linkedin_posts;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class GetLinkedinPostsImport.
 */
class GetLinkedinPostsBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a GetLinkedinPostsBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, ConfigFactoryInterface $config_factory) {
    $this->entityManager = $entity_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * Import Linkedin posts.
   */
  public function import() {
    $config = $this->configFactory->get('get_linkedin_posts.settings');

    if ($config->get('linkedin_import')) {

      if ($config->get('linkedin_access_token') && $config->get('linkedin_access_token_expires')) {

        if ($config->get('linkedin_access_token_expires') < time()) {
          \Drupal::logger('get_linkedin_posts')
            ->error('Linkedin access token was expired. Get new token on this module configuration page.');
        }
        else {
          $actual_token = $config->get('linkedin_access_token');
          $posts_qty = $config->get('linkedin_count');

          if ($config->get('linkedin_companies')) {

            $company_array = explode(' ', $config->get('linkedin_companies'));

            foreach ($company_array as $company_val) {

              $url = 'https://api.linkedin.com/v1/companies/' . trim($company_val) . '/updates?count=' . $posts_qty . '&type=status-update&format=json';

              try {
                $response = \Drupal::httpClient()->get($url, [
                  'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $actual_token,
                  ],
                ]);

                $data = (string) $response->getBody();
                $posts_array = Json::decode($data);
                if (!isset($posts_array['values'])) {
                  return;
                }

                $linkedin_posts = $posts_array['values'];

                if ($linkedin_posts) {
                  $max_timestamp = $this->getMaxNodeTimestamp($company_val);
                  foreach ($linkedin_posts as $linkedin_post) {
                    if ($linkedin_post['timestamp'] <= $max_timestamp) {
                      continue;
                    }
                    else {
                      $this->createLinkedinNode($linkedin_post);
                    }
                  }
                  \Drupal::logger('get_linkedin_posts')
                    ->notice('Posts saved from Linkedin.');
                }
              }
              catch (\Exception $exc_linkedin) {
                \Drupal::logger('get_linkedin_posts')
                  ->error('Post save from Linkedin fails. Or member does not have permission to get company.' . $exc_linkedin->getMessage());
              }
            }
          }
        }
      }
      else {
        \Drupal::logger('get_linkedin_posts')
          ->error('You should setup Linkedin access token on this module configuration page.');
      }
    }
  }

  /**
   * Create nodes of type Linkedin.
   *
   * @param array $linkedin_post
   *   Linkedin post.
   */
  public function createLinkedinNode(array $linkedin_post) {

    $storage = \Drupal::entityTypeManager()->getStorage('node');

    $node = $storage->create([
      'type' => 'linkedin_post',
      'title' => !empty($linkedin_post['updateContent']['companyStatusUpdate']['share']['content']['title']) ? $linkedin_post['updateContent']['companyStatusUpdate']['share']['content']['title'] : $linkedin_post['updateContent']['company']['name'],
      'field_linkedin_post_id' => $linkedin_post['updateContent']['companyStatusUpdate']['share']['id'],
      'field_linkedin_company_name' => $linkedin_post['updateContent']['company']['name'],
      'field_linkedin_company_id' => $linkedin_post['updateContent']['company']['id'],
      'field_linkedin_description' => !empty($linkedin_post['updateContent']['companyStatusUpdate']['share']['content']['description']) ? $linkedin_post['updateContent']['companyStatusUpdate']['share']['content']['description'] : '',
      'field_linkedin_eyebrowurl' => !empty($linkedin_post['updateContent']['companyStatusUpdate']['share']['content']['eyebrowUrl']) ? $linkedin_post['updateContent']['companyStatusUpdate']['share']['content']['eyebrowUrl'] : '',
      'field_linkedin_shortenedurl' => !empty($linkedin_post['updateContent']['companyStatusUpdate']['share']['content']['shortenedUrl']) ? $linkedin_post['updateContent']['companyStatusUpdate']['share']['content']['shortenedUrl'] : '',
      'field_linkedin_submittedimageurl' => !empty($linkedin_post['updateContent']['companyStatusUpdate']['share']['content']['submittedImageUrl']) ? $linkedin_post['updateContent']['companyStatusUpdate']['share']['content']['submittedImageUrl'] : '',
      'field_linkedin_submittedurl' => !empty($linkedin_post['updateContent']['companyStatusUpdate']['share']['content']['submittedUrl']) ? $linkedin_post['updateContent']['companyStatusUpdate']['share']['content']['submittedUrl'] : '',
      'field_linkedin_thumbnailurl' => !empty($linkedin_post['updateContent']['companyStatusUpdate']['share']['content']['thumbnailUrl']) ? $linkedin_post['updateContent']['companyStatusUpdate']['share']['content']['thumbnailUrl'] : '',
      'field_linkedin_title' => !empty($linkedin_post['updateContent']['companyStatusUpdate']['share']['content']['title']) ? $linkedin_post['updateContent']['companyStatusUpdate']['share']['content']['title'] : '',
      'field_linkedin_post_content' => [
        'value' => $this->linkedinMakeLinksClickable($linkedin_post['updateContent']['companyStatusUpdate']['share']['comment']),
        'format' => 'full_html',
      ],
      'field_linkedin_timestamp' => $linkedin_post['timestamp'],
      'created' => ($linkedin_post['timestamp'] / 1000),
      'uid' => '1',
      'status' => 1,
    ]);

    if (isset($linkedin_post['updateContent']['companyStatusUpdate']['share']['content']['submittedImageUrl'])) {
      $data = file_get_contents($linkedin_post['updateContent']['companyStatusUpdate']['share']['content']['submittedImageUrl']);
      $dir = 'public://linkedin/';
      if ($data && file_prepare_directory($dir, FILE_CREATE_DIRECTORY)) {
        $file = file_save_data($data, $dir . 'linkedin_image_' . $linkedin_post['timestamp'], FILE_EXISTS_RENAME);
        $node->set('field_linkedin_local_image', $file);
      }
    }

    $node->save();
  }

  /**
   * Remove nodes of type Linkedin depended on expiry settings in the module.
   */
  public function removeOldPosts() {
    $config = $this->configFactory->get('get_linkedin_posts.settings');
    $expiry_period = $config->get('linkedin_expire');

    if ($expiry_period) {
      $storage = $this->entityManager->getStorage('node');
      $query = $storage->getQuery();
      $query->condition('created', time() - $expiry_period, '<');
      $query->condition('type', 'linkedin_post');
      $result = $query->execute();
      $nodes = $storage->loadMultiple($result);
      $storage->delete($nodes);
    }
  }

  /**
   * Send email to admin and Linkedin user about expiry token.
   */
  public function sendEmailExpiry() {

    $config = $this->configFactory->get('get_linkedin_posts.settings');

    if (!empty($config->get('linkedin_admin_email'))) {
      $send_notification_to = \Drupal::config('system.site')
          ->get('mail') . ',' . $config->get('linkedin_admin_email');
    }
    else {
      $send_notification_to = \Drupal::config('system.site')->get('mail');
    }

    if ($config->get('linkedin_access_token') && $config->get('linkedin_access_token_expires')) {
      $token_expires = $config->get('linkedin_access_token_expires');
      if (($token_expires > time()) && ($token_expires - time() <= 604800) && (\Drupal::state()
            ->get('get_linkedin_posts_letter_sent', 0) != 1)
      ) {

        /** @var \Drupal\Core\Datetime\DateFormatterInterface $formatter */
        $date_formatter = \Drupal::service('date.formatter');

        $expiry_period = $date_formatter->formatDiff(\Drupal::time()
          ->getRequestTime(), $config->get('linkedin_access_token_expires'), [
            'granularity' => 3,
            'return_as_object' => FALSE,
          ]);

        $mailManager = \Drupal::service('plugin.manager.mail');
        $module = 'get_linkedin_posts';
        $key = 'linkedin_token_expiry';
        $params['message'] = 'Linkedin token will be expired ' . $expiry_period;
        $params['time_to_expiry'] = $expiry_period;
        $langcode = \Drupal::currentUser()->getPreferredLangcode();
        $send = TRUE;

        $result = $mailManager->mail($module, $key, $send_notification_to, $langcode, $params, NULL, $send);

        if ($result['result'] !== TRUE) {
          $get_linkedin_posts_info = $this->t('There was a problem sending expiry token message and it was not sent.');
          \Drupal::logger('get_linkedin_posts')
            ->error($get_linkedin_posts_info);
        }
        else {
          $get_linkedin_posts_info = $this->tt('The expiry token message has been sent.');
          \Drupal::logger('get_linkedin_posts')
            ->notice($get_linkedin_posts_info);

          \Drupal::state()->set('get_linkedin_posts_letter_sent', 1);
        }
      }
    }
    else {
      $expiry_period = $this->t('Linkedin access token was expired or you should to generate new token.');
    }
  }

  /**
   * Select MAX timestamp of present Linkedin node with particular company Name.
   *
   * @param string $company_val
   *   Company ID.
   *
   * @return int
   *   Timestamp max.
   */
  public function getMaxNodeTimestamp($company_val) {

    $storage = $this->entityManager->getStorage('node');
    $query = $storage->getAggregateQuery();
    $query->condition('field_linkedin_company_id', $company_val);
    $query->aggregate('field_linkedin_timestamp', 'MAX');
    $result = $query->execute();

    if (isset($result[0]['field_linkedin_timestamp_max'])) {
      return $result[0]['field_linkedin_timestamp_max'];
    }

    // If you are here - there is no any linkedin node
    // for this company. So let's return 2010-01-01 here.
    return 1262304000;
  }

  /**
   * Replace links.
   *
   * @param string $text
   *   Text for replace.
   *
   * @return mixed
   *   Text with links.
   */
  public function linkedinMakeLinksClickable($text) {
    return preg_replace('!(((f|ht)tp(s)?://)[-a-zA-Zа-яА-Я()0-9@:%_+.~#?&;//=]+)!i', '<a href="$1" target="_blank">$1</a>', $text);
  }

  /**
   * Run all tasks.
   */
  public function getAll() {
    $config = $this->configFactory->get('get_linkedin_posts.settings');

    if ($config->get('linkedin_import')) {

      if ($config->get('linkedin_access_token') && $config->get('linkedin_access_token_expires')) {
        $this->import();
        $this->removeOldPosts();
        $this->sendEmailExpiry();
      }
    }
    else {
      \Drupal::logger('get_linkedin_posts')
        ->error('You should setup Linkedin access token on this module configuration page.');
    }
  }

}
