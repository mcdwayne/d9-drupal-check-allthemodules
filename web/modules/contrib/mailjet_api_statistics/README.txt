Mailjet Api Statistics
---
This module for Drupal 8.x. provides send Email through Mailjet, and check statistics of it
https://dev.mailjet.com/guides/?php#statistics

Mailjet helps to send and track emails in real time,
while ensuring their deliverability.

INSTALLATION
------------

1) Run composer $ require drupal/mailjet_api_statistics
2) Log in as administrator in Drupal.
3) Enable the Mailjet Api statistics module on the Modules page.
4) Fill in required settings on the admin/config/mailjet_api_statistics
5) Run $ composer update

CONFIGURATION
-------------
Set Mailjet API key and Mailjet API secret key from your mailjet account.
Optional, you can choose debug mode, if you want to save all response to log.

HOW TO USE
------------

1) To sent email
use Drupal\mailjet_api_statistics\Includes\MailjetApiStatistics;
$params = [];
    $params['from_email'] = 'pilot@mailjet.com';
    $params['from_name'] = 'Mailjet Pilot';
    $params['subject'] = 'Your email flight plan!';
    $params['text_part'] = 'Dear passenger, welcome to mailjet!';
    $params['html_part'] = '<h3>Dear passenger, welcome to Mailjet!</h3><br />Force be with you!';
    $params['recipients'] = [
      [
        'Email' => "passenger@mailjet.com",
      ],
    ];
$mail = new MailjetApiStatistics();
$response = $mail->mailjet_api_statistics_basic_email($params);
$message_id = $response->getData()['Sent']['0']['MessageID']

2) Too get statistics
use Drupal\mailjet_api_statistics\Includes\MailjetApiStatistics;
// $message_id - it is id of the message you are interested in
$test = new MailjetApiStatistics();
$response = $test->mailjet_api_statistics_get_basic_information_about_message($message_id);

MAINTAINERS
-----------

Current maintainers:
 * Alex Domasevich (alexdoma) - https://www.drupal.org/u/alexdoma
 * Alex Kriver (AlexKriver) - https://www.drupal.org/u/alexkriver