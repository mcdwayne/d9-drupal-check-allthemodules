<?php

namespace Drupal\spammaster\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class controller.
 */
class SpamMasterCommentController extends ControllerBase {

  protected $form = NULL;
  protected $formstate = NULL;
  protected $spammasterip = NULL;
  protected $spammasteremail = NULL;
  protected $spammastercomment = NULL;

  /**
   * {@inheritdoc}
   */
  public function spammastercommentcheck($form, $formstate, $spammasterip, $spammasteremail, $spammastercomment) {

    $this->form = $form;
    $this->formstate = $formstate;
    $this->spammasterip = $spammasterip;
    $this->spammasteremail = $spammasteremail;
    $this->spammastercomment = $spammastercomment;
    $spammaster_date = date("Y-m-d H:i:s");
    $spammaster_settings = \Drupal::config('spammaster.settings');
    $spammaster_license = $spammaster_settings->get('spammaster.license_key');
    $spammaster_status = $spammaster_settings->get('spammaster.license_status');
    $spammaster_total_block_count = $spammaster_settings->get('spammaster.total_block_count');
    $spammaster_settings_protection = \Drupal::config('spammaster.settings_protection');
    $spammaster_block_message = $spammaster_settings_protection->get('spammaster.block_message');
    $blog_threat_ip = \Drupal::request()->getClientIp();
    if ($spammaster_status == 'VALID' || $spammaster_status == 'MALFUNCTION_1' || $spammaster_status == 'MALFUNCTION_2') {
      // Local db check.
      $spammaster_spam_buffer_query = \Drupal::database()->select('spammaster_threats', 'u');
      $spammaster_spam_buffer_query->fields('u', ['threat']);
      $spammaster_spam_buffer_query->where('(threat = :ip OR threat = :email)', [':ip' => $spammasterip, ':email' => $spammasteremail]);
      $spammaster_spam_buffer_result = $spammaster_spam_buffer_query->execute()->fetchObject();
      $result_comment_content_trim = substr($spammastercomment, 0, 360);
      if (empty($result_comment_content_trim)) {
        $result_comment_content_trim = 'your-comment';
      }
      // Local db positive, throw error, watchdog, insert.
      if (!empty($spammaster_spam_buffer_result)) {
        $formstate->setErrorByName('mail', 'SPAM MASTER: ' . $spammaster_block_message);

        // Insert local db.
        $spammaster_db_ip = \Drupal::database()->select('spammaster_threats', 'u');
        $spammaster_db_ip->fields('u', ['threat']);
        $spammaster_db_ip->where('(threat = :ip)', [':ip' => $spammasterip]);
        $spammaster_db_ip_result = $spammaster_db_ip->execute()->fetchObject();
        if (empty($spammaster_db_ip_result)) {
          $spammaster_db_ip_insert = db_insert('spammaster_threats')->fields([
            'date' => $spammaster_date,
            'threat' => $spammasterip,
          ])->execute();
        }

        $spammaster_total_block_count_1 = ++$spammaster_total_block_count;
        \Drupal::configFactory()->getEditable('spammaster.settings')
          ->set('spammaster.total_block_count', $spammaster_total_block_count_1)
          ->save();

        $spammaster_db_ip_insert = db_insert('spammaster_keys')->fields([
          'date' => $spammaster_date,
          'spamkey' => 'spammaster-comment',
          'spamvalue' => 'Spam Master: comment buffer block, Ip: ' . $spammasterip . ', Comment: ' . $result_comment_content_trim,
        ])->execute();

        \Drupal::logger('spammaster-comment')->notice('Spam Master: buffer comment block, Ip: ' . $spammasterip . ', Comment: ' . $result_comment_content_trim);
      }
      // Web api check.
      else {
        // Create data to be posted.
        $blog_license_key = $spammaster_license;
        $blog_threat_type = 'comment';
        if (empty($spammasteremail) || is_array($spammasteremail)) {
          $spammasteremail = 'drup@' . date('Ymdhis') . '.drup';
        }
        $blog_threat_email = $spammasteremail;
        $blog_threat_content = $result_comment_content_trim;
        $blog_web_address = \Drupal::request()->getHost();
        $address_unclean = $blog_web_address;
        $address = preg_replace('#^https?://#', '', $address_unclean);
        $blog_server_ip = $_SERVER['SERVER_ADDR'];
        // If empty ip.
        if (empty($blog_server_ip) || $blog_server_ip == '0') {
          @$blog_server_ip = 'I ' . gethostbyname($_SERVER['SERVER_NAME']);
        }
        $spam_master_leaning_url = 'aHR0cHM6Ly9zcGFtbWFzdGVyLnRlY2hnYXNwLmNvbS93cC1jb250ZW50L3BsdWdpbnMvc3BhbS1tYXN0ZXItYWRtaW5pc3RyYXRvci9pbmNsdWRlcy9sZWFybmluZy9nZXRfbGVhcm5fY29tLnBocA==';
        // Call drupal hhtpclient.
        $client = \Drupal::httpClient();
        // Post data.
        $request = $client->post(base64_decode($spam_master_leaning_url), [
          'form_params' => [
            'blog_license_key' => $blog_license_key,
            'blog_threat_ip' => $blog_threat_ip,
            'blog_threat_type' => $blog_threat_type,
            'blog_threat_email' => $blog_threat_email,
            'blog_threat_content' => $blog_threat_content,
            'blog_web_adress' => $address,
            'blog_server_ip' => $blog_server_ip,
          ],
        ]);
        // Decode json data.
        $response = json_decode($request->getBody(), TRUE);
        if (empty($response)) {
        }
        else {
          // Insert local db.
          $spammaster_db_ip = \Drupal::database()->select('spammaster_threats', 'u');
          $spammaster_db_ip->fields('u', ['threat']);
          $spammaster_db_ip->where('(threat = :ip)', [':ip' => $spammasterip]);
          $spammaster_db_ip_result = $spammaster_db_ip->execute()->fetchObject();
          if (empty($spammaster_db_ip_result)) {
            $spammaster_db_ip_insert = db_insert('spammaster_threats')->fields([
              'date' => $spammaster_date,
              'threat' => $spammasterip,
            ])->execute();
          }
          $spammaster_db_email = \Drupal::database()->select('spammaster_threats', 'u');
          $spammaster_db_email->fields('u', ['threat']);
          $spammaster_db_email->where('(threat = :email)', [':email' => $spammasteremail]);
          $spammaster_db_email_result = $spammaster_db_email->execute()->fetchObject();
          if (empty($spammaster_db_email_result)) {
            $spammaster_db_email_insert = db_insert('spammaster_threats')->fields([
              'date' => $spammaster_date,
              'threat' => $spammasteremail,
            ])->execute();
          }
          // Web positive, throw error.
          $formstate->setErrorByName('mail', 'SPAM MASTER: ' . $spammaster_block_message);
          // Watchdog log.
          \Drupal::logger('spammaster-comment')->notice('Spam Master: comment rbl block, Ip: ' . $spammasterip . ', Comment: ' . $result_comment_content_trim);
          $spammaster_total_block_count_1 = ++$spammaster_total_block_count;
          \Drupal::configFactory()->getEditable('spammaster.settings')
            ->set('spammaster.total_block_count', $spammaster_total_block_count_1)
            ->save();
          $spammaster_db_ip_insert = db_insert('spammaster_keys')->fields([
            'date' => $spammaster_date,
            'spamkey' => 'spammaster-comment',
            'spamvalue' => 'Spam Master: comment rbl block, Ip: ' . $spammasterip . ', Comment: ' . $result_comment_content_trim,
          ])->execute();
        }
      }
    }
  }

}
