<?php

namespace Drupal\forena\FrxPlugin\Document;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\forena\DocManager;
use Drupal\forena\Form\EmailMergeForm;
use Drupal\forena\ReportManager;

/**
 * Provides MS Excel Exports
 *
 * @FrxDocument(
 *   id= "email",
 *   name="Email Merge",
 *   ext="email"
 * )
 */
class EmailMerge extends DocumentBase {

  public $emails = [];
  public $count;
  public $prompt_subject;
  public $prompt_body;


  public function header() {
    $this->write_buffer='';
  }

  public function flush() {
    $content = [];
    $body = $this->write_buffer;
    $doc = new \DOMDocument('1.0', 'UTF-8');
    $doc->strictErrorChecking = FALSE;
    libxml_use_internal_errors(true);
    $doc->loadHTML($body);
    libxml_clear_errors();
    $xml = simplexml_import_dom($doc);
    if (!$xml) return $content;
    $docs = $xml->xpath('.//*[@class="email-document"]');
    $this->prompt_subject = TRUE;
    $this->prompt_body = TRUE;
    /** @var \SimpleXMLElement $doc */
    if ($docs) foreach ($docs as $doc) {

      // From
      $from = $doc->xpath('.//*[@class="email-header-from"]');
      $from = $from ? html_entity_decode(strip_tags($from[0])) : '';

      // Subject
      $subject = $doc->xpath('.//*[@class="email-header-subject"]');
      if ($subject) $this->prompt_subject = FALSE;
      $subject = $subject ? (string)$subject[0] : '';

      // To
      $to = $doc->xpath('.//*[@class="email-header-to"]');
      $to = $to ? html_entity_decode(strip_tags($to[0]->asXML())) : '';
      if ($to) $this->prompt_to = FALSE;
      $body = $doc->xpath('.//*[@class="email-body"]');
      if ($body) $this->prompt_body = FALSE;
      $body = $body ? $body[0]->asXML() : $body;

      // Assemble email
      $email = array(
        'to' => $to,
        'from' => $from,
        'parms' => array(
          'subject' => $subject,
          'body' => $body,
        ),
      );

      // Check for cc
      $cc = $doc->xpath('.//*[@class="email-header-cc"]');
      if ($cc) {
        $email['parms']['headers']['Cc'] = html_entity_decode(strip_tags($cc[0]->asXML()));

      }

      // Check for bcc
      $bcc = $doc->xpath('.//*[@class="email-header-bcc"]');
      if ($bcc) {
        $email['parms']['headers']['Bcc'] = html_entity_decode(strip_tags($bcc[0]->asXML()));
      }
      $this->emails [] = $email;
    }
    $count = count($docs);
    $this->count = $count;
    if ($count) {
      $content['email_form'] = \Drupal::formBuilder()->getForm(EmailMergeForm::class);
    }
    else {
      $this->app()->error(t('No mail merge information in report. Displaying report instead.'));
      $output = $body;
      $content = array(
          'content' => array('#markup' => $output),
      );
    }
    return $content;
  }

  public function sendMail($email, $max, $subject='', $body='') {
    $i=0;
    if (!$max) $max = count($this->emails);
    foreach ($this->emails as $doc) {
      $i++;

      $to = !empty($email) ? $email : $doc['to'];
      $from = $doc['from'];
      // Replace body
      if (!empty($body)) {
        $doc['parms']['body'] = $body;
      }
      // Replace subject
      if (!empty($subject)) {
        $doc['parms']['subject'] = $subject;
      }
      // If we're in test mode foce unset of header.
      if ($email) {
        // Remove bcc and cc
        unset($doc['parms']['headers']);
      }
      /** @var MailManagerInterface $mailManager */
      $mailManager = \Drupal::service('plugin.manager.mail');

      if ($i <= $max) {
        $mailManager->mail('forena', 'mailmerge', $to, \Drupal::languageManager()->getLanguages(), $doc['parms'], $from, TRUE);
      }
    }
  }

}
