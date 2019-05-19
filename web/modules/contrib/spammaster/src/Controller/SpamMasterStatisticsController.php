<?php

namespace Drupal\spammaster\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class controller.
 */
class SpamMasterStatisticsController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function spammasterstatistics() {

    // Get module settings.
    $spammaster_settings = \Drupal::config('spammaster.settings');

    // Prepare 5 Days.
    $spam_master_today_minus_1 = date('Y-m-d');
    $spam_master_today_minus_2 = date('Y-m-d', strtotime($spam_master_today_minus_1 . '-1 days'));
    $spam_master_today_minus_3 = date('Y-m-d', strtotime($spam_master_today_minus_1 . '-2 days'));
    $spam_master_today_minus_4 = date('Y-m-d', strtotime($spam_master_today_minus_1 . '-3 days'));
    $spam_master_today_minus_5 = date('Y-m-d', strtotime($spam_master_today_minus_1 . '-4 days'));

    // Prepare 3 Months.
    $spam_master_month_minus_1 = date('Y-m');
    $spam_master_month_minus_2 = date('Y-m', strtotime($spam_master_today_minus_1 . '-1 months'));
    $spam_master_month_minus_3 = date('Y-m', strtotime($spam_master_today_minus_1 . '-2 months'));

    // Get count last day of firewall blocks from spammaster_keys.
    $spammaster_spam_firewall_query1 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_firewall_query1->fields('u', ['spamkey']);
    $spammaster_spam_firewall_query1->where('(date LIKE :date AND spamkey = :firewall)', [':date' => $spam_master_today_minus_1 . '%', ':firewall' => 'spammaster-firewall']);
    $spammaster_spam_firewall_result1 = $spammaster_spam_firewall_query1->countQuery()->execute()->fetchField();
    // Get count last 2 days of firewall blocks from spammaster_keys.
    $spammaster_spam_firewall_query2 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_firewall_query2->fields('u', ['spamkey']);
    $spammaster_spam_firewall_query2->where('(date LIKE :date AND spamkey = :firewall)', [':date' => $spam_master_today_minus_2 . '%', ':firewall' => 'spammaster-firewall']);
    $spammaster_spam_firewall_result2 = $spammaster_spam_firewall_query2->countQuery()->execute()->fetchField();
    // Get count last 3 days of firewall blocks from spammaster_keys.
    $spammaster_spam_firewall_query3 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_firewall_query3->fields('u', ['spamkey']);
    $spammaster_spam_firewall_query3->where('(date LIKE :date AND spamkey = :firewall)', [':date' => $spam_master_today_minus_3 . '%', ':firewall' => 'spammaster-firewall']);
    $spammaster_spam_firewall_result3 = $spammaster_spam_firewall_query3->countQuery()->execute()->fetchField();
    // Get count last 4 days of firewall blocks from spammaster_keys.
    $spammaster_spam_firewall_query4 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_firewall_query4->fields('u', ['spamkey']);
    $spammaster_spam_firewall_query4->where('(date LIKE :date AND spamkey = :firewall)', [':date' => $spam_master_today_minus_4 . '%', ':firewall' => 'spammaster-firewall']);
    $spammaster_spam_firewall_result4 = $spammaster_spam_firewall_query4->countQuery()->execute()->fetchField();
    // Get count last 5 days of firewall blocks from spammaster_keys.
    $spammaster_spam_firewall_query5 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_firewall_query5->fields('u', ['spamkey']);
    $spammaster_spam_firewall_query5->where('(date LIKE :date AND spamkey = :firewall)', [':date' => $spam_master_today_minus_5 . '%', ':firewall' => 'spammaster-firewall']);
    $spammaster_spam_firewall_result5 = $spammaster_spam_firewall_query5->countQuery()->execute()->fetchField();

    // Get count last day of registration blocks from spammaster_keys.
    $spammaster_spam_registration_query1 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_registration_query1->fields('u', ['spamkey']);
    $spammaster_spam_registration_query1->where('(date LIKE :date AND spamkey = :registration)', [':date' => $spam_master_today_minus_1 . '%', ':registration' => 'spammaster-registration']);
    $spammaster_spam_registration_result1 = $spammaster_spam_registration_query1->countQuery()->execute()->fetchField();
    // Get count last 2 days of registration blocks from spammaster_keys.
    $spammaster_spam_registration_query2 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_registration_query2->fields('u', ['spamkey']);
    $spammaster_spam_registration_query2->where('(date LIKE :date AND spamkey = :registration)', [':date' => $spam_master_today_minus_2 . '%', ':registration' => 'spammaster-registration']);
    $spammaster_spam_registration_result2 = $spammaster_spam_registration_query2->countQuery()->execute()->fetchField();
    // Get count last 3 days of registration blocks from spammaster_keys.
    $spammaster_spam_registration_query3 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_registration_query3->fields('u', ['spamkey']);
    $spammaster_spam_registration_query3->where('(date LIKE :date AND spamkey = :registration)', [':date' => $spam_master_today_minus_3 . '%', ':registration' => 'spammaster-registration']);
    $spammaster_spam_registration_result3 = $spammaster_spam_registration_query3->countQuery()->execute()->fetchField();
    // Get count last 4 days of registration blocks from spammaster_keys.
    $spammaster_spam_registration_query4 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_registration_query4->fields('u', ['spamkey']);
    $spammaster_spam_registration_query4->where('(date LIKE :date AND spamkey = :registration)', [':date' => $spam_master_today_minus_4 . '%', ':registration' => 'spammaster-registration']);
    $spammaster_spam_registration_result4 = $spammaster_spam_registration_query4->countQuery()->execute()->fetchField();
    // Get count last 5 days of registration blocks from spammaster_keys.
    $spammaster_spam_registration_query5 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_registration_query5->fields('u', ['spamkey']);
    $spammaster_spam_registration_query5->where('(date LIKE :date AND spamkey = :registration)', [':date' => $spam_master_today_minus_5 . '%', ':registration' => 'spammaster-registration']);
    $spammaster_spam_registration_result5 = $spammaster_spam_registration_query5->countQuery()->execute()->fetchField();

    // Get count last day of comment blocks from spammaster_keys.
    $spammaster_spam_comment_query1 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_comment_query1->fields('u', ['spamkey']);
    $spammaster_spam_comment_query1->where('(date LIKE :date AND spamkey = :comment)', [':date' => $spam_master_today_minus_1 . '%', ':comment' => 'spammaster-comment']);
    $spammaster_spam_comment_result1 = $spammaster_spam_comment_query1->countQuery()->execute()->fetchField();
    // Get count last 2 days of comment blocks from spammaster_keys.
    $spammaster_spam_comment_query2 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_comment_query2->fields('u', ['spamkey']);
    $spammaster_spam_comment_query2->where('(date LIKE :date AND spamkey = :comment)', [':date' => $spam_master_today_minus_2 . '%', ':comment' => 'spammaster-comment']);
    $spammaster_spam_comment_result2 = $spammaster_spam_comment_query2->countQuery()->execute()->fetchField();
    // Get count last 3 days of comment blocks from spammaster_keys.
    $spammaster_spam_comment_query3 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_comment_query3->fields('u', ['spamkey']);
    $spammaster_spam_comment_query3->where('(date LIKE :date AND spamkey = :comment)', [':date' => $spam_master_today_minus_3 . '%', ':comment' => 'spammaster-comment']);
    $spammaster_spam_comment_result3 = $spammaster_spam_comment_query3->countQuery()->execute()->fetchField();
    // Get count last 4 days of comment blocks from spammaster_keys.
    $spammaster_spam_comment_query4 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_comment_query4->fields('u', ['spamkey']);
    $spammaster_spam_comment_query4->where('(date LIKE :date AND spamkey = :comment)', [':date' => $spam_master_today_minus_4 . '%', ':comment' => 'spammaster-comment']);
    $spammaster_spam_comment_result4 = $spammaster_spam_comment_query4->countQuery()->execute()->fetchField();
    // Get count last 5 days of comment blocks from spammaster_keys.
    $spammaster_spam_comment_query5 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_comment_query5->fields('u', ['spamkey']);
    $spammaster_spam_comment_query5->where('(date LIKE :date AND spamkey = :comment)', [':date' => $spam_master_today_minus_5 . '%', ':comment' => 'spammaster-comment']);
    $spammaster_spam_comment_result5 = $spammaster_spam_comment_query5->countQuery()->execute()->fetchField();

    // Get count last day of contact blocks from spammaster_keys.
    $spammaster_spam_contact_query1 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_contact_query1->fields('u', ['spamkey']);
    $spammaster_spam_contact_query1->where('(date LIKE :date AND spamkey = :contact)', [':date' => $spam_master_today_minus_1 . '%', ':contact' => 'spammaster-contact']);
    $spammaster_spam_contact_result1 = $spammaster_spam_contact_query1->countQuery()->execute()->fetchField();
    // Get count last 2 days of contact blocks from spammaster_keys.
    $spammaster_spam_contact_query2 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_contact_query2->fields('u', ['spamkey']);
    $spammaster_spam_contact_query2->where('(date LIKE :date AND spamkey = :contact)', [':date' => $spam_master_today_minus_2 . '%', ':contact' => 'spammaster-contact']);
    $spammaster_spam_contact_result2 = $spammaster_spam_contact_query2->countQuery()->execute()->fetchField();
    // Get count last 3 days of contact blocks from spammaster_keys.
    $spammaster_spam_contact_query3 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_contact_query3->fields('u', ['spamkey']);
    $spammaster_spam_contact_query3->where('(date LIKE :date AND spamkey = :contact)', [':date' => $spam_master_today_minus_3 . '%', ':contact' => 'spammaster-contact']);
    $spammaster_spam_contact_result3 = $spammaster_spam_contact_query3->countQuery()->execute()->fetchField();
    // Get count last 4 days of contact blocks from spammaster_keys.
    $spammaster_spam_contact_query4 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_contact_query4->fields('u', ['spamkey']);
    $spammaster_spam_contact_query4->where('(date LIKE :date AND spamkey = :contact)', [':date' => $spam_master_today_minus_4 . '%', ':contact' => 'spammaster-contact']);
    $spammaster_spam_contact_result4 = $spammaster_spam_contact_query4->countQuery()->execute()->fetchField();
    // Get count last 5 days of contact blocks from spammaster_keys.
    $spammaster_spam_contact_query5 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_contact_query5->fields('u', ['spamkey']);
    $spammaster_spam_contact_query5->where('(date LIKE :date AND spamkey = :contact)', [':date' => $spam_master_today_minus_5 . '%', ':contact' => 'spammaster-contact']);
    $spammaster_spam_contact_result5 = $spammaster_spam_contact_query5->countQuery()->execute()->fetchField();

    // Get count last day of honeypot blocks from spammaster_keys.
    $spammaster_spam_honeypot_query1 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_honeypot_query1->fields('u', ['spamkey']);
    $spammaster_spam_honeypot_query1->where('(date LIKE :date AND spamkey = :honeypot)', [':date' => $spam_master_today_minus_1 . '%', ':honeypot' => 'spammaster-honeypot']);
    $spammaster_spam_honeypot_result1 = $spammaster_spam_honeypot_query1->countQuery()->execute()->fetchField();
    // Get count last 2 days of honeypot blocks from spammaster_keys.
    $spammaster_spam_honeypot_query2 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_honeypot_query2->fields('u', ['spamkey']);
    $spammaster_spam_honeypot_query2->where('(date LIKE :date AND spamkey = :honeypot)', [':date' => $spam_master_today_minus_2 . '%', ':honeypot' => 'spammaster-honeypot']);
    $spammaster_spam_honeypot_result2 = $spammaster_spam_honeypot_query2->countQuery()->execute()->fetchField();
    // Get count last 3 days of honeypot blocks from spammaster_keys.
    $spammaster_spam_honeypot_query3 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_honeypot_query3->fields('u', ['spamkey']);
    $spammaster_spam_honeypot_query3->where('(date LIKE :date AND spamkey = :honeypot)', [':date' => $spam_master_today_minus_3 . '%', ':honeypot' => 'spammaster-honeypot']);
    $spammaster_spam_honeypot_result3 = $spammaster_spam_honeypot_query3->countQuery()->execute()->fetchField();
    // Get count last 4 days of honeypot blocks from spammaster_keys.
    $spammaster_spam_honeypot_query4 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_honeypot_query4->fields('u', ['spamkey']);
    $spammaster_spam_honeypot_query4->where('(date LIKE :date AND spamkey = :honeypot)', [':date' => $spam_master_today_minus_4 . '%', ':honeypot' => 'spammaster-honeypot']);
    $spammaster_spam_honeypot_result4 = $spammaster_spam_honeypot_query4->countQuery()->execute()->fetchField();
    // Get count last 5 days of honeypot blocks from spammaster_keys.
    $spammaster_spam_honeypot_query5 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_honeypot_query5->fields('u', ['spamkey']);
    $spammaster_spam_honeypot_query5->where('(date LIKE :date AND spamkey = :honeypot)', [':date' => $spam_master_today_minus_5 . '%', ':honeypot' => 'spammaster-honeypot']);
    $spammaster_spam_honeypot_result5 = $spammaster_spam_honeypot_query5->countQuery()->execute()->fetchField();

    // Get count last day of recaptcha blocks from spammaster_keys.
    $spammaster_spam_recaptcha_query1 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_recaptcha_query1->fields('u', ['spamkey']);
    $spammaster_spam_recaptcha_query1->where('(date LIKE :date AND spamkey = :recaptcha)', [':date' => $spam_master_today_minus_1 . '%', ':recaptcha' => 'spammaster-recaptcha']);
    $spammaster_spam_recaptcha_result1 = $spammaster_spam_recaptcha_query1->countQuery()->execute()->fetchField();
    // Get count last 2 days of recaptcha blocks from spammaster_keys.
    $spammaster_spam_recaptcha_query2 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_recaptcha_query2->fields('u', ['spamkey']);
    $spammaster_spam_recaptcha_query2->where('(date LIKE :date AND spamkey = :recaptcha)', [':date' => $spam_master_today_minus_2 . '%', ':recaptcha' => 'spammaster-recaptcha']);
    $spammaster_spam_recaptcha_result2 = $spammaster_spam_recaptcha_query2->countQuery()->execute()->fetchField();
    // Get count last 3 days of recaptcha blocks from spammaster_keys.
    $spammaster_spam_recaptcha_query3 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_recaptcha_query3->fields('u', ['spamkey']);
    $spammaster_spam_recaptcha_query3->where('(date LIKE :date AND spamkey = :recaptcha)', [':date' => $spam_master_today_minus_3 . '%', ':recaptcha' => 'spammaster-recaptcha']);
    $spammaster_spam_recaptcha_result3 = $spammaster_spam_recaptcha_query3->countQuery()->execute()->fetchField();
    // Get count last 4 days of recaptcha blocks from spammaster_keys.
    $spammaster_spam_recaptcha_query4 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_recaptcha_query4->fields('u', ['spamkey']);
    $spammaster_spam_recaptcha_query4->where('(date LIKE :date AND spamkey = :recaptcha)', [':date' => $spam_master_today_minus_4 . '%', ':recaptcha' => 'spammaster-recaptcha']);
    $spammaster_spam_recaptcha_result4 = $spammaster_spam_recaptcha_query4->countQuery()->execute()->fetchField();
    // Get count last 5 days of recaptcha blocks from spammaster_keys.
    $spammaster_spam_recaptcha_query5 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_recaptcha_query5->fields('u', ['spamkey']);
    $spammaster_spam_recaptcha_query5->where('(date LIKE :date AND spamkey = :recaptcha)', [':date' => $spam_master_today_minus_5 . '%', ':recaptcha' => 'spammaster-recaptcha']);
    $spammaster_spam_recaptcha_result5 = $spammaster_spam_recaptcha_query5->countQuery()->execute()->fetchField();

    // Get total count from module settings.
    $total_count = $spammaster_settings->get('spammaster.total_block_count');
    if (empty($total_count)) {
      $total_count = '0';
    }
    // Get total count firewall.
    $spammaster_total_firewall = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_total_firewall->fields('u', ['spamkey']);
    $spammaster_total_firewall->where('(spamkey = :firewall)', [':firewall' => 'spammaster-firewall']);
    $total_count_firewall = $spammaster_total_firewall->countQuery()->execute()->fetchField();
    // Get total count registration.
    $spammaster_total_registration = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_total_registration->fields('u', ['spamkey']);
    $spammaster_total_registration->where('(spamkey = :registration)', [':registration' => 'spammaster-registration']);
    $total_count_registration = $spammaster_total_registration->countQuery()->execute()->fetchField();
    // Get total count comment.
    $spammaster_total_comment = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_total_comment->fields('u', ['spamkey']);
    $spammaster_total_comment->where('(spamkey = :comment)', [':comment' => 'spammaster-comment']);
    $total_count_comment = $spammaster_total_comment->countQuery()->execute()->fetchField();
    // Get total count contact.
    $spammaster_total_contact = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_total_contact->fields('u', ['spamkey']);
    $spammaster_total_contact->where('(spamkey = :contact)', [':contact' => 'spammaster-contact']);
    $total_count_contact = $spammaster_total_contact->countQuery()->execute()->fetchField();
    // Get total count honeypot.
    $spammaster_total_honeypot = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_total_honeypot->fields('u', ['spamkey']);
    $spammaster_total_honeypot->where('(spamkey = :honeypot)', [':honeypot' => 'spammaster-honeypot']);
    $total_count_honeypot = $spammaster_total_honeypot->countQuery()->execute()->fetchField();
    // Get total count recaptcha.
    $spammaster_total_recaptcha = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_total_recaptcha->fields('u', ['spamkey']);
    $spammaster_total_recaptcha->where('(spamkey = :recaptcha)', [':recaptcha' => 'spammaster-recaptcha']);
    $total_count_recaptcha = $spammaster_total_recaptcha->countQuery()->execute()->fetchField();

    // Get month total firewall.
    $spammaster_spam_firewall_month1 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_firewall_month1->fields('u', ['spamkey']);
    $spammaster_spam_firewall_month1->where('(date LIKE :date AND spamkey = :firewall)', [':date' => $spam_master_month_minus_1 . '%', ':firewall' => 'spammaster-firewall']);
    $spammaster_firewall_month_result1 = $spammaster_spam_firewall_month1->countQuery()->execute()->fetchField();
    // Get -1 month total firewall.
    $spammaster_spam_firewall_month2 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_firewall_month2->fields('u', ['spamkey']);
    $spammaster_spam_firewall_month2->where('(date LIKE :date AND spamkey = :firewall)', [':date' => $spam_master_month_minus_2 . '%', ':firewall' => 'spammaster-firewall']);
    $spammaster_firewall_month_result2 = $spammaster_spam_firewall_month2->countQuery()->execute()->fetchField();
    // Get -2 month total firewall.
    $spammaster_spam_firewall_month3 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_firewall_month3->fields('u', ['spamkey']);
    $spammaster_spam_firewall_month3->where('(date LIKE :date AND spamkey = :firewall)', [':date' => $spam_master_month_minus_3 . '%', ':firewall' => 'spammaster-firewall']);
    $spammaster_firewall_month_result3 = $spammaster_spam_firewall_month3->countQuery()->execute()->fetchField();
    // Get month total registration.
    $spammaster_spam_registration_month1 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_registration_month1->fields('u', ['spamkey']);
    $spammaster_spam_registration_month1->where('(date LIKE :date AND spamkey = :registration)', [':date' => $spam_master_month_minus_1 . '%', ':registration' => 'spammaster-registration']);
    $spammaster_registration_month_result1 = $spammaster_spam_registration_month1->countQuery()->execute()->fetchField();
    // Get -1 month total registration.
    $spammaster_spam_registration_month2 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_registration_month2->fields('u', ['spamkey']);
    $spammaster_spam_registration_month2->where('(date LIKE :date AND spamkey = :registration)', [':date' => $spam_master_month_minus_2 . '%', ':registration' => 'spammaster-registration']);
    $spammaster_registration_month_result2 = $spammaster_spam_registration_month2->countQuery()->execute()->fetchField();
    // Get -2 month total registration.
    $spammaster_spam_registration_month3 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_registration_month3->fields('u', ['spamkey']);
    $spammaster_spam_registration_month3->where('(date LIKE :date AND spamkey = :registration)', [':date' => $spam_master_month_minus_3 . '%', ':registration' => 'spammaster-registration']);
    $spammaster_registration_month_result3 = $spammaster_spam_registration_month3->countQuery()->execute()->fetchField();
    // Get month total comment.
    $spammaster_spam_comment_month1 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_comment_month1->fields('u', ['spamkey']);
    $spammaster_spam_comment_month1->where('(date LIKE :date AND spamkey = :comment)', [':date' => $spam_master_month_minus_1 . '%', ':comment' => 'spammaster-comment']);
    $spammaster_comment_month_result1 = $spammaster_spam_comment_month1->countQuery()->execute()->fetchField();
    // Get -1 month total comment.
    $spammaster_spam_comment_month2 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_comment_month2->fields('u', ['spamkey']);
    $spammaster_spam_comment_month2->where('(date LIKE :date AND spamkey = :comment)', [':date' => $spam_master_month_minus_2 . '%', ':comment' => 'spammaster-comment']);
    $spammaster_comment_month_result2 = $spammaster_spam_comment_month2->countQuery()->execute()->fetchField();
    // Get -2 month total comment.
    $spammaster_spam_comment_month3 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_comment_month3->fields('u', ['spamkey']);
    $spammaster_spam_comment_month3->where('(date LIKE :date AND spamkey = :comment)', [':date' => $spam_master_month_minus_3 . '%', ':comment' => 'spammaster-comment']);
    $spammaster_comment_month_result3 = $spammaster_spam_comment_month3->countQuery()->execute()->fetchField();
    // Get month total contact.
    $spammaster_spam_contact_month1 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_contact_month1->fields('u', ['spamkey']);
    $spammaster_spam_contact_month1->where('(date LIKE :date AND spamkey = :contact)', [':date' => $spam_master_month_minus_1 . '%', ':contact' => 'spammaster-contact']);
    $spammaster_contact_month_result1 = $spammaster_spam_contact_month1->countQuery()->execute()->fetchField();
    // Get -1 month total contact.
    $spammaster_spam_contact_month2 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_contact_month2->fields('u', ['spamkey']);
    $spammaster_spam_contact_month2->where('(date LIKE :date AND spamkey = :contact)', [':date' => $spam_master_month_minus_2 . '%', ':contact' => 'spammaster-contact']);
    $spammaster_contact_month_result2 = $spammaster_spam_contact_month2->countQuery()->execute()->fetchField();
    // Get -2 month total contact.
    $spammaster_spam_contact_month3 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_contact_month3->fields('u', ['spamkey']);
    $spammaster_spam_contact_month3->where('(date LIKE :date AND spamkey = :contact)', [':date' => $spam_master_month_minus_3 . '%', ':contact' => 'spammaster-contact']);
    $spammaster_contact_month_result3 = $spammaster_spam_contact_month3->countQuery()->execute()->fetchField();
    // Get month total honeypot.
    $spammaster_spam_honeypot_month1 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_honeypot_month1->fields('u', ['spamkey']);
    $spammaster_spam_honeypot_month1->where('(date LIKE :date AND spamkey = :honeypot)', [':date' => $spam_master_month_minus_1 . '%', ':honeypot' => 'spammaster-honeypot']);
    $spammaster_honeypot_month_result1 = $spammaster_spam_honeypot_month1->countQuery()->execute()->fetchField();
    // Get -1 month total honeypot.
    $spammaster_spam_honeypot_month2 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_honeypot_month2->fields('u', ['spamkey']);
    $spammaster_spam_honeypot_month2->where('(date LIKE :date AND spamkey = :honeypot)', [':date' => $spam_master_month_minus_2 . '%', ':honeypot' => 'spammaster-honeypot']);
    $spammaster_honeypot_month_result2 = $spammaster_spam_honeypot_month2->countQuery()->execute()->fetchField();
    // Get -2 month total honeypot.
    $spammaster_spam_honeypot_month3 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_honeypot_month3->fields('u', ['spamkey']);
    $spammaster_spam_honeypot_month3->where('(date LIKE :date AND spamkey = :honeypot)', [':date' => $spam_master_month_minus_3 . '%', ':honeypot' => 'spammaster-honeypot']);
    $spammaster_honeypot_month_result3 = $spammaster_spam_honeypot_month3->countQuery()->execute()->fetchField();
    // Get month total recaptcha.
    $spammaster_spam_recaptcha_month1 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_recaptcha_month1->fields('u', ['spamkey']);
    $spammaster_spam_recaptcha_month1->where('(date LIKE :date AND spamkey = :recaptcha)', [':date' => $spam_master_month_minus_1 . '%', ':recaptcha' => 'spammaster-recaptcha']);
    $spammaster_recaptcha_month_result1 = $spammaster_spam_recaptcha_month1->countQuery()->execute()->fetchField();
    // Get -1 month total recaptcha.
    $spammaster_spam_recaptcha_month2 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_recaptcha_month2->fields('u', ['spamkey']);
    $spammaster_spam_recaptcha_month2->where('(date LIKE :date AND spamkey = :recaptcha)', [':date' => $spam_master_month_minus_2 . '%', ':recaptcha' => 'spammaster-recaptcha']);
    $spammaster_recaptcha_month_result2 = $spammaster_spam_recaptcha_month2->countQuery()->execute()->fetchField();
    // Get -2 month total recaptcha.
    $spammaster_spam_recaptcha_month3 = \Drupal::database()->select('spammaster_keys', 'u');
    $spammaster_spam_recaptcha_month3->fields('u', ['spamkey']);
    $spammaster_spam_recaptcha_month3->where('(date LIKE :date AND spamkey = :recaptcha)', [':date' => $spam_master_month_minus_3 . '%', ':recaptcha' => 'spammaster-recaptcha']);
    $spammaster_recaptcha_month_result3 = $spammaster_spam_recaptcha_month3->countQuery()->execute()->fetchField();

    return [
      '#theme' => 'statistics',
      '#attached' => [
        'library' => [
          'spammaster/spammaster-styles',
        ],
      ],
      '#spam_master_today_minus_1' => $spam_master_today_minus_1,
      '#spam_master_today_minus_2' => $spam_master_today_minus_2,
      '#spam_master_today_minus_3' => $spam_master_today_minus_3,
      '#spam_master_today_minus_4' => $spam_master_today_minus_4,
      '#spam_master_today_minus_5' => $spam_master_today_minus_5,
      '#totalitems_firewall_blocked_1' => $spammaster_spam_firewall_result1,
      '#totalitems_firewall_blocked_2' => $spammaster_spam_firewall_result2,
      '#totalitems_firewall_blocked_3' => $spammaster_spam_firewall_result3,
      '#totalitems_firewall_blocked_4' => $spammaster_spam_firewall_result4,
      '#totalitems_firewall_blocked_5' => $spammaster_spam_firewall_result5,
      '#totalitems_registration_blocked_1' => $spammaster_spam_registration_result1,
      '#totalitems_registration_blocked_2' => $spammaster_spam_registration_result2,
      '#totalitems_registration_blocked_3' => $spammaster_spam_registration_result3,
      '#totalitems_registration_blocked_4' => $spammaster_spam_registration_result4,
      '#totalitems_registration_blocked_5' => $spammaster_spam_registration_result5,
      '#totalitems_comment_blocked_1' => $spammaster_spam_comment_result1,
      '#totalitems_comment_blocked_2' => $spammaster_spam_comment_result2,
      '#totalitems_comment_blocked_3' => $spammaster_spam_comment_result3,
      '#totalitems_comment_blocked_4' => $spammaster_spam_comment_result4,
      '#totalitems_comment_blocked_5' => $spammaster_spam_comment_result5,
      '#totalitems_contact_blocked_1' => $spammaster_spam_contact_result1,
      '#totalitems_contact_blocked_2' => $spammaster_spam_contact_result2,
      '#totalitems_contact_blocked_3' => $spammaster_spam_contact_result3,
      '#totalitems_contact_blocked_4' => $spammaster_spam_contact_result4,
      '#totalitems_contact_blocked_5' => $spammaster_spam_contact_result5,
      '#totalitems_honeypot_blocked_1' => $spammaster_spam_honeypot_result1,
      '#totalitems_honeypot_blocked_2' => $spammaster_spam_honeypot_result2,
      '#totalitems_honeypot_blocked_3' => $spammaster_spam_honeypot_result3,
      '#totalitems_honeypot_blocked_4' => $spammaster_spam_honeypot_result4,
      '#totalitems_honeypot_blocked_5' => $spammaster_spam_honeypot_result5,
      '#totalitems_recaptcha_blocked_1' => $spammaster_spam_recaptcha_result1,
      '#totalitems_recaptcha_blocked_2' => $spammaster_spam_recaptcha_result2,
      '#totalitems_recaptcha_blocked_3' => $spammaster_spam_recaptcha_result3,
      '#totalitems_recaptcha_blocked_4' => $spammaster_spam_recaptcha_result4,
      '#totalitems_recaptcha_blocked_5' => $spammaster_spam_recaptcha_result5,
      '#total_count' => $total_count,
      '#total_count_firewall' => $total_count_firewall,
      '#total_count_registration' => $total_count_registration,
      '#total_count_comment' => $total_count_comment,
      '#total_count_contact' => $total_count_contact,
      '#total_count_honeypot' => $total_count_honeypot,
      '#total_count_recaptcha' => $total_count_recaptcha,
      '#spam_master_month_minus_1' => $spam_master_month_minus_1,
      '#spam_master_month_minus_2' => $spam_master_month_minus_2,
      '#spam_master_month_minus_3' => $spam_master_month_minus_3,
      '#total_month_firewall_1' => $spammaster_firewall_month_result1,
      '#total_month_firewall_2' => $spammaster_firewall_month_result2,
      '#total_month_firewall_3' => $spammaster_firewall_month_result3,
      '#total_month_registration_1' => $spammaster_registration_month_result1,
      '#total_month_registration_2' => $spammaster_registration_month_result2,
      '#total_month_registration_3' => $spammaster_registration_month_result3,
      '#total_month_comment_1' => $spammaster_comment_month_result1,
      '#total_month_comment_2' => $spammaster_comment_month_result2,
      '#total_month_comment_3' => $spammaster_comment_month_result3,
      '#total_month_contact_1' => $spammaster_contact_month_result1,
      '#total_month_contact_2' => $spammaster_contact_month_result2,
      '#total_month_contact_3' => $spammaster_contact_month_result3,
      '#total_month_honeypot_1' => $spammaster_honeypot_month_result1,
      '#total_month_honeypot_2' => $spammaster_honeypot_month_result2,
      '#total_month_honeypot_3' => $spammaster_honeypot_month_result3,
      '#total_month_recaptcha_1' => $spammaster_recaptcha_month_result1,
      '#total_month_recaptcha_2' => $spammaster_recaptcha_month_result2,
      '#total_month_recaptcha_3' => $spammaster_recaptcha_month_result3,
    ];
  }

}
