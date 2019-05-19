<?php
namespace Drupal\sms_aliyun\Plugin\SmsGateway;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Json;
use Drupal\sms\Direction;
use Drupal\sms\Entity\SmsGatewayInterface;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessage;
use Drupal\sms\Message\SmsMessageResultStatus;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\SmsProcessingReponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Flc\Dysms\Client;
use Flc\Dysms\Request\SendSms;

/**
 * @SmsGateway(
 *    id = "aliyun",
 *    label = @Translation("Aliyun"),
 *    outgoing_message_max_recipients = 1,
 *    reports_push = TRUE,
 * )
 */
class Aliyun extends SmsGatewayPluginBase {
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'access_key_id' => '',
      'access_key_secret' => '',
      'signname' => '',
      'templatecode' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
   public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
     $form = parent::buildConfigurationForm($form, $form_state);

     $config = $this->getConfiguration();

     $form['aliyun'] = [
      '#type' => 'details',
      '#title' => $this->t('Aliyun'),
      '#open' => TRUE,
      ];

     $form['aliyun']['access_key_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Access Key Id'),
        '#default_value' => $config['access_key_id'],
        '#placeholder' => '16 bits',
        '#required' => TRUE,
      ];

      $form['aliyun']['access_key_secret'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Access Key Secret'),
        '#default_value' => $config['access_key_secret'],
        '#placeholder' => '30 bits',
        '#required' => TRUE,
        ];

      $form['aliyun']['signname'] = [
        '#type'           => 'textfield',
        '#title'          => $this->t('Signname'),
        '#default_value'  => $config['signname'],
        '#placeholder'    => 'Your signname',
        '#required'       => TRUE,
       ];
      
      $form['aliyun']['templatecode'] = [
        '#type'           => 'textfield',
        '#title'          => $this->t('Your templatecode'),
        '#default_value'  => $config['templatecode'],
        '#placeholder'    => 'Your templatecode',
        '#required'       => TRUE,
       ];


      return $form;
   }

   /**
    * {@inheritdoc}
    */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['access_key_id'] = trim($form_state->getValue('access_key_id'));
    $this->configuration['access_key_secret'] = trim($form_state->getValue('access_key_secret'));
    $this->configuration['signname'] = trim($form_state->getValue('signname'));
    $this->configuration['templatecode'] = trim($form_state->getValue('templatecode'));
  }

  /*
   * {@inheritdoc}
   */
  public function send(SmsMessageInterface $sms_message) {
    $recipient = $sms_message->getRecipients()[0];
    $result = new SmsMessageResult();
  
    $accesskeyid      = $this->configuration['access_key_id'];
    $accesskeysecret  = $this->configuration['access_key_secret'];
    $signname         = $this->configuration['signname'];
    $templatecode     = $this->configuration['templatecode'];
    $param            = $sms_message->getMessage();

    $config = [
      'accessKeyId'     => $accesskeyid,
      'accessKeySecret' => $accesskeysecret,
    ];
    $report = new SmsDeliveryReport();
    $report->setRecipient($recipient);
    $client     = new Client($config);
    $sendSms    = new SendSms;
    $sendSms->setPhoneNumbers($recipient);
    $sendSms->setSignName($signname);
    $sendSms->setTemplateCode($templatecode);
    $sendSms->setTemplateParam(['number' => $param]);
    
    $resp = $client->execute($sendSms);
    $report->setStatus(SmsMessageReportStatus::QUEUED);
    $report->setMessageId($resp->RequestId);
    \Drupal::logger('sms_aliyun')->notice($resp->Message);
    if (!(isset($resp->Code) && $resp->Code == 'OK')) {
      $results = [
        'status'  => FALSE,
        'message' => t('An error occurred during the HTTP request: RequestId: @requestid; Code: @code; Message: @message;. Please see <a href = "@aliyun_url">the aliyun docs</a> for more information',
        [
          '@code'           => $resp->Code,
          '@requestid'     => $resp->RequestId,
          '@message'        => $resp->Message,
          '@aliyun_url'     => 'https://help.aliyun.com',
        ]
        ),
      ];
      \Drupal::logger('sms_aliyun')->notice($results['message']);
      $report->setStatus(SmsMessageReportStatus::ERROR);
      $report->setStatusMessage($results['message']);
    }
    if ($report->getStatus()) {
      $result->addReport($report);
    }
    return $result;
  }

}
