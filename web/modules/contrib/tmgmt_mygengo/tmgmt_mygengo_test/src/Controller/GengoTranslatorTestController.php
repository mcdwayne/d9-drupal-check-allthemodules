<?php

/**
 * @file
 * Contains \Drupal\tmgmt_mygengo_test\Controller\GengoTranslatorTestController.
 */

namespace Drupal\tmgmt_mygengo_test\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns autocomplete responses for block categories.
 */
class GengoTranslatorTestController {

  /**
   * Mock service - Job PUT - used for job review.
   *
   * @param int $job_id
   *   Gengo job id.
   * @param Request $request
   *   The request object.
   */
  public function serviceJob($job_id, Request $request) {
    $data = array();
    parse_str($request->getContent(), $data);

    $data = Json::decode($data['data']);

    if ($data['action'] == 'revise') {

      $comment = new \stdClass();
      $comment->body = $data['comment'];
      $comment->ctime = REQUEST_TIME;
      $comment->author = 'yogi bear';

      $comments = \Drupal::state()->get('tmgmt_mygengo_test_comments', array());
      $comments[$job_id][] = $comment;
      \Drupal::state()->set('tmgmt_mygengo_test_comments', $comments);
    }

    return new JsonResponse(array(
      'opstat' => 'ok',
      'response' => '',
    ));
  }

  /**
   * Mock service to return previously submitted jobs.
   */
  public function jobsGet() {
    return new JsonResponse(array(
      'opstat' => 'ok',
      'response' => \Drupal::state()->get('tmgmt_mygengo_test_last_gengo_response'),
    ));
  }

  /**
   * Mock service to return information about an order.
   *
   * @param int $order_id
   *   Order ID.
   */
  public function serviceOrderGet($order_id) {
    $orders = \Drupal::state()->get('tmgmt_mygengo_test_orders', array());
    if (isset($orders[$order_id])) {
      $order = (object) array(
        'order_id' => $order_id,
        'jobs_available' => array(),
        'jobs_queued' => 0,
        'total_jobs' => count($orders[$order_id]),
      );
      foreach ($orders[$order_id] as $job) {
        $order->jobs_available[] = $job['job_id'];
      }
      return new JsonResponse(array(
        'opstat' => 'ok',
        'response' => array('order' => $order),
      ));
    }
    return new JsonResponse(array(
      'opstat' => 'error',
      'err' => array('code' => 123, 'msg' => 'Order id ' . $order_id . ' does not exist'),
    ));
  }

  /**
   * Mock service call to create a comment.
   *
   * @param int $job_id
   *   Remote job id.
   * @param Request $request
   *   The request object.
   */
  public function serviceCommentCreate($job_id, Request $request) {
    $comment = new \stdClass();
    $data = Json::decode($request->request->get('data'));
    $comment->body = $data['body'];
    $comment->ctime = REQUEST_TIME;
    $comment->author = 'yogi bear';

    $comments = \Drupal::state()->get('tmgmt_mygengo_test_comments', array());
    $comments[$job_id][] = $comment;
    \Drupal::state()->set('tmgmt_mygengo_test_comments', $comments);

    return new JsonResponse(array(
      'opstat' => 'ok',
      'response' => array(),
    ));
  }

  /**
   * Mock service call to fetch remote comments.
   *
   * @param int $job_id
   *   Remote job id.
   */
  public function serviceCommentsGet($job_id) {
    $response = new \stdClass();
    $comments = \Drupal::state()->get('tmgmt_mygengo_test_comments', array());
    if (!isset($comments[$job_id])) {
      $comments[$job_id] = array();
    }

    $response->thread = $comments[$job_id];

    return new JsonResponse(array(
      'opstat' => 'ok',
      'response' => $response,
    ));
  }


  /**
   * Gengo mock service.
   *
   * @param Request $request
   *   The request object.
   */
  public function serviceTranslate(Request $request) {

    if ($error_response = tmgmt_mygengo_test_authenticate($request)) {
      return $error_response;
    }

    $response = new \stdClass();

    $sources = array();

    // Use case when jobs have been submitted to gengo.
    if ($request->request->get('data')) {
      $data = Json::decode($request->request->get('data'));

      foreach ($data['jobs'] as $id => $job) {

        // Simulate API behavior to ignore multiple jobs with the same source.
        if (array_search($job['body_src'], $sources)) {
          continue;
        }

        // Keep track of source strings.
        $sources[$id] = $job['body_src'];

        // Machine translation - simulate returning translation job right away.
        if ($job['tier'] == 'machine') {
          $body_tgt = 'mt_de_' . $job['body_src'];
          $response->jobs[$id] = tmgmt_mygengo_test_build_response_job($job['body_src'], $body_tgt, 'approved', $job['tier'], $job['custom_data'], $job['slug'], $job['position']);
        }
        // Hack to tell mock service that translation should be returned right
        // away as available.
        elseif (strpos($job['body_src'], 'Lazy-Loading') !== FALSE) {
          $body_tgt = str_replace('Lazy-Loading', 'Translated', $job['body_src']);
          $response->jobs[$id] = tmgmt_mygengo_test_build_response_job($job['body_src'], $body_tgt, 'available', $job['tier'], $job['custom_data'], $job['slug'], $job['position']);
        }
        // Otherwise we have submitted a job, however just return the job object
        // without translation and in pending state.
        else {
          $response->jobs[$id] = tmgmt_mygengo_test_build_response_job($job['body_src'], NULL, 'pending', $job['tier'], $job['custom_data'], $job['slug'], $job['position']);
        }
      }

      // If order mode is enabled, return an order_id instead of the jobs.
      if (\Drupal::state()->get('tmgmt_mygengo_test_order_mode')) {
        $orders = \Drupal::state()->get('tmgmt_mygengo_test_orders', array());
        if (empty($orders)) {
          $order_id = 1;
        }
        else {
          $order_id = count($orders) + 1;
        }
        $orders[$order_id] = $response->jobs;
        \Drupal::state()->set('tmgmt_mygengo_test_orders', $orders);
        unset($response->jobs);
        $response->order_id = $order_id;
      }

      // Save the response so that it can be further examined.
      \Drupal::state()->set('tmgmt_mygengo_test_last_gengo_response', $response);
    }

    // @todo To fix tests I believe this has to be changed in some cases. Just
    //   return the $response not the structure.
    return new JsonResponse(array(
      'opstat' => 'ok',
      'response' => $response,
    ));
  }

  /**
   * Page callback account balance.
   */
  public function serviceAccountBalance() {
    $balance = new \stdClass();
    $balance->credits = 25.32;
    $balance->currency = 'USD';
    return new JsonResponse(array(
      'opstat' => 'ok',
      'response' => $balance,
    ));
  }

  /**
   * Page callback for getting the supported languages.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The languages request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response with the language or an error.
   */
  public function serviceGetLanguages(Request $request) {
    // At this moment the gengo specification require the api_sig
    // @see http://developers.gengo.com/v2/api_methods/service/#languages-get
    // But to get the languages we just need the api_key, so the api_sig
    // is not checked.
    if ($error_response = tmgmt_mygengo_test_authenticate($request, FALSE)) {
      return $error_response;
    }

    $languages = array(
      'de' => array(
        'lc' => 'de',
      ),
      'en' => array(
        'lc' => 'en',
      ),
      'es' => array(
        'lc' => 'es',
      ),
    );

    // Remote source language.
    if (isset($_GET['lc_src'])) {
      unset($languages[$_GET['lc_src']]);
    }

    return new JsonResponse(array(
      'opstat' => 'ok',
      'response' => $languages,
    ));
  }

  /**
   * Page callback for getting language pairs.
   */
  public function serviceGetLanguagePairs() {

    $pairs = array();

    $pair = new \stdClass();
    $pair->lc_src = 'en';
    $pair->lc_tgt = 'de';
    $pair->tier = 'standard';
    $pair->unit_price = '0.0500';
    $pair->currency = 'USD';
    $pairs[] = $pair;

    $pair->tier = 'pro';
    $pair->unit_price = '0.1000';
    $pairs[] = $pair;

    $pair->tier = 'ultra';
    $pair->unit_price = '0.1500';
    $pairs[] = $pair;

    return new JsonResponse(array(
      'opstat' => 'ok',
      'response' => $pairs,
    ));
  }

  /**
   * Page callback for getting a jobs quote.
   *
   * Note that this mock service returns just static info. There is no logic
   * that would somehow react on what has been submitted as job data.
   */
  public function serviceGetQuote() {

    $quote = new \stdClass();
    $quote->jobs = array();

    $quote->jobs[0] = new \stdClass();
    $quote->jobs[0]->unit_count = 2;
    $quote->jobs[0]->credits = 2;
    $quote->jobs[0]->eta = 60 * 60 * 24; // One day.
    $quote->jobs[0]->type = 'text';
    $quote->jobs[0]->currency = 'USD';

    $quote->jobs[1] = new \stdClass();
    $quote->jobs[1]->unit_count = 2;
    $quote->jobs[1]->credits = 2;
    $quote->jobs[1]->eta = 60 * 60 * 24; // One day.
    $quote->jobs[1]->type = 'text';
    $quote->jobs[1]->currency = 'USD';

    return new JsonResponse(array(
      'opstat' => 'ok',
      'response' => $quote,
    ));
  }

  /**
   * Returns account statistics.
   */
  public function serviceAccountStats(Request $request) {
    if ($error_response = tmgmt_mygengo_test_authenticate($request)) {
      return $error_response;
    }

    $stats = new \stdClass();
    $stats->credits_spent = 0;
    $stats->user_since = 1459122054;
    $stats->currency = 'USD';
    $stats->billing_type = 'Pre-pay';
    $stats->customer_type = 'Retail';

    return new JsonResponse(array(
      'opstat' => 'ok',
      'response' => $stats,
    ));
  }

}
