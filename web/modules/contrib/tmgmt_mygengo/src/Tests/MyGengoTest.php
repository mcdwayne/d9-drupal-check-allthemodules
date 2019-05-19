<?php

/**
 * @file
 * Contains Drupal\tmgmt_mygengo\Tests\MyGengoTest.
 */

namespace Drupal\tmgmt_mygengo\Tests;

use Drupal;
use Drupal\Component\Serialization\Json;
use Drupal\tmgmt\Tests\TMGMTTestBase;
use Drupal\tmgmt\Entity\Translator;
use Drupal\Core\Url;
use Drupal\tmgmt\Entity\RemoteMapping;
use Drupal\tmgmt\Entity\JobItem;
use Drupal\tmgmt\Entity\Job;

/**
 * Tests the Gengo translator plugin integration.
 *
 * @group tmgmt_mygengo
 */
class MyGengoTest extends TMGMTTestBase {

  /**
   * @var \Drupal\tmgmt\Entity\Translator $translator
   */
  protected $translator;

  public static $modules = array(
    'tmgmt_mygengo',
    'tmgmt_mygengo_test',
  );

  public function setUp() {
    parent::setUp();
    $this->addLanguage('de');
    $this->translator = Translator::load('mygengo');
    \Drupal::configFactory()->getEditable('tmgmt_mygengo.settings')->set('use_mock_service', TRUE)->save();
  }

  /**
   * Tests basic API methods of the plugin.
   */
  public function testAPI() {

    $job = $this->createJob();
    $standard = array(
      'quality' => 'standard',
    );
    $job->settings = $standard;
    $job->translator = $this->translator->id();
    \Drupal::state()->set('tmgmt.test_source_data',  array(
      'wrapper' => array(
        '#text' => 'Hello world',
        '#label' => 'Wrapper label',
      ),
    ));
    $item = $job->addItem('test_source', 'test', '1');
    $item->save();

    // The translator should not be available at this point because we didn't
    // define an API key yet.
    $this->assertFalse($job->canRequestTranslation()->getSuccess());

    $this->translator = $job->getTranslator();

    // The gengo API does not require a valid private key to request languages.
    // We explicitly test this behavior here as the mock is implemented in the
    // same way.
    $this->translator->setSetting('api_public_key', 'correct key');
    $this->translator->setSetting('api_private_key', 'wrong key');
    $this->translator->save();

    $this->translator->clearLanguageCache();

    $this->assertTrue($job->canRequestTranslation()->getSuccess());
    $job->requestTranslation();

    // Should have been rejected due to the wrong api key.
    $this->assertTrue($job->isRejected());
    $messages = $job->getMessages();
    $message = end($messages);
    $this->assertEqual('error', $message->getType());
    $this->assert(strpos($message->getMessage(), 'Job has been rejected') !== FALSE,
      t('Job should be rejected as we provided wrong api key.'));

    // Save a correct api key.
    $this->translator->setSetting('api_public_key', 'correct key');
    $this->translator->setSetting('api_private_key', 'correct key');
    $this->translator->save();
    $this->assertTrue($job->canRequestTranslation()->getSuccess());

    $this->translator->clearLanguageCache();

    // Create a new job. Workaround for https://www.drupal.org/node/2695217.
    $job = $this->createJob();
    $standard = array(
      'quality' => 'standard',
    );
    $job->settings = $standard;
    $job->translator = $this->translator->id();
    $item = $job->addItem('test_source', 'test', '1');

    // Make sure the translator returns the correct supported target languages.
    $languages = $job->getTranslator()->getSupportedTargetLanguages('en');
    $this->assertTrue(isset($languages['de']));
    $this->assertTrue(isset($languages['es']));
    $this->assertFalse(isset($languages['it']));
    $this->assertFalse(isset($languages['en']));

    // Note that requesting translation goes with default
    // gengo_auto_approve = 1
    $job->requestTranslation();
    // And therefore the job should be active.
    $this->assertTrue($job->isActive());
    foreach ($job->getItems() as $item) {
      $this->assertTrue($item->isActive());
    }

    // Create a gengo response of translated and approved job.
    $post['job'] = Json::encode(tmgmt_mygengo_test_build_response_job(
      'Hello world',
      'Hallo Welt',
      'approved',
      'standard',
      implode('][', array($job->id(), $item->id(), 'wrapper')),
      $item->getData()['wrapper']['#label']
    ));

    $action = Url::fromRoute('tmgmt_mygengo.callback')->setOptions(array('absolute' => TRUE))->toString();
    $out = $this->curlExec(array(CURLOPT_URL => $action, CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => $post));

    // Response should be empty if everything went ok.
    $this->assertResponse(200);
    $this->assertTrue(empty($out));

    // Clear job item caches.
    \Drupal::entityManager()->getStorage('tmgmt_job_item')->resetCache();

    // Verify the label/slug.
    $this->refreshVariables();
    $data = Drupal::state()->get('tmgmt_mygengo_test_last_gengo_response', FALSE);
    // Find the key under which we can access the job received:
    $jobs = $data->jobs;
    $job_keys = array_keys($jobs);
    $key = array_shift($job_keys);
    $this->assertEqual($data->jobs[$key]['slug'], $item->getSourceLabel() . ' > ' . $item->getData(['wrapper'],'#label'));

    // Now it should be needs review.
    foreach ($job->getItems() as $item) {
      $this->assertTrue($item->isNeedsReview());
    }
    $items = $job->getItems();
    $item = end($items);
    $data = $item->getData();
    $this->assertEqual('Hallo Welt', $data['wrapper']['#translation']['#text']);

    // Test machine translation.
    $job = $this->createJob();
    $machine = array(
      'quality' => 'machine',
    );
    $job->settings = $machine;
    $job->translator = $this->translator->id();
    $job->save();
    \Drupal::state()->set('tmgmt.test_source_data', array(
      'wrapper' => array(
        '#label' => 'Parent label',
        'subwrapper1' => array(
          '#text' => 'Hello world',
          '#label' => 'Sub label 1',
        ),
        'subwrapper2' => array(
          '#text' => 'Hello world again',
          '#label' => 'Sub label 2',
        ),
      ),
      'no_label' => array(
        '#text' => 'No label',
      ),
      'escaping' => array(
        '#text' => 'A text with a @placeholder',
        '#escape' => array(
          14 => array('string' => '@placeholder'),
        )
      ),
    ));
    $item = $job->addItem('test_source', 'test', '1');
    $item->save();

    // Machine translation should immediately go to needs review.
    $job->requestTranslation();
    foreach ($job->getItems() as $item) {
      $this->assertTrue($item->isNeedsReview());
    }
    $items = $job->getItems();
    $item = end($items);
    $data = $item->getData();
    // If received a job item with tier machine the mock service will prepend
    // mt_de_ to the source text.
    $this->assertEqual('mt_de_Hello world', $data['wrapper']['subwrapper1']['#translation']['#text']);
    $this->assertEqual('mt_de_Hello world again', $data['wrapper']['subwrapper2']['#translation']['#text']);
    $this->assertEqual('mt_de_A text with a @placeholder', $data['escaping']['#translation']['#text']);

    // Verify generated labels/slugs.
    $this->refreshVariables();
    $data = \Drupal::state()->get('tmgmt_mygengo_test_last_gengo_response', FALSE);
    $jobs = $data->jobs;

    $subwrapper1_key = $job->id() . '][' . $item->id() . '][wrapper][subwrapper1';
    $no_label_key = $job->id() . '][' . $item->id() . '][no_label';
    $escaping_key = $job->id() . '][' . $item->id() . '][escaping';
    $this->assertEqual($jobs[$subwrapper1_key]['slug'],  $item->getSourceLabel() . ' > Parent label > Sub label 1');
    $this->assertEqual($jobs[$no_label_key]['slug'], $item->getSourceLabel());
    $this->assertEqual($jobs[$escaping_key]['body_src'], 'A text with a @placeholder');

    // Test positions.
    $position = 0;
    foreach ($jobs as $response_job) {
      $this->assertEqual($position++, $response_job['position']);
    }
  }

  public function testOrderModeCallback() {
    \Drupal::state()->set('tmgmt_mygengo_test_order_mode', 1);

    $this->translator->setSetting('api_public_key', 'correct key');
    $this->translator->setSetting('api_private_key', 'correct key');
    $this->translator->save();

    // Test machine translation.
    $job = $this->createJob();
    $standard = array(
      'quality' => 'standard',
    );
    $job->settings = $standard;
    $job->translator = $this->translator->id();
    $job->save();
    \Drupal::state()->set('tmgmt.test_source_data', array(
      'wrapper' => array(
        '#label' => 'Parent label',
        'subwrapper1' => array(
          '#text' => 'Hello world',
          '#label' => 'Sub label 1',
        ),
        'subwrapper2' => array(
          '#text' => 'Hello world again',
          '#label' => 'Sub label 2',
        ),
      ),
      'no_label' => array(
        '#text' => 'No label',
      ),
    ));
    $item = $job->addItem('test_source', 'test', '1');
    $item->save();

    $job->requestTranslation();
    $this->assertTrue($job->isActive());
    $this->refreshVariables();
    $orders = \Drupal::state()->get('tmgmt_mygengo_test_orders', array());
    $order_id = key($orders);
    $remotes = RemoteMapping::loadByLocalData($job->id());
    // Remotes should have been created with the order id and without job id.
    $this->assertEqual(count($remotes), 3, '3 remote mappings created.');

    $remotes = RemoteMapping::loadByLocalData($job->id(), $item->id(), 'wrapper][subwrapper1');
    $remote = reset($remotes);
    $this->assertEqual($remote->getRemoteIdentifier1(), $order_id);
    $this->assertEqual($remote->getRemoteIdentifier2(), '');
    $this->assertEqual($remote->getJobItem()->id(), $item->id());

    $remotes = RemoteMapping::loadByLocalData($job->id(), $item->id(), 'no_label');
    $remote = reset($remotes);
    $this->assertEqual($remote->getRemoteIdentifier1(), $order_id);
    $this->assertEqual($remote->getRemoteIdentifier2(), '');
    $this->assertEqual($remote->getJobItem()->id(), $item->id());

    // Create a gengo response of the job.
    // Create a gengo response of translated and approved job.
    /* $post['job'] = Json::encode(tmgmt_mygengo_test_build_response_job(
      'Hello world',
      'Hallo Welt',
      'approved',
      'standard',
      implode('][', array($job->id(), $item->id(), 'wrapper')),
      $item->getData()['wrapper']['#label']
    ));
    */

    $gengo_job = $orders[$order_id][$job->id() . '][' . $item->id() . '][wrapper][subwrapper1'];
    $post['job'] = Json::encode($gengo_job);

    $action = Url::fromRoute('tmgmt_mygengo.callback')->setOptions(array('absolute' => TRUE))->toString();
    $out = $this->curlExec(array(CURLOPT_URL => $action, CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => $post));

    // Response should be empty if everything went ok.
    $this->assertResponse(200);
    $this->assertTrue(empty($out));

    Drupal::entityManager()->getStorage('tmgmt_remote')->resetCache();
    $remotes = RemoteMapping::loadByLocalData($job->id(), $item->id(), 'wrapper][subwrapper1');
    $remote = reset($remotes);
    $this->assertEqual($remote->getRemoteIdentifier1(), $order_id);
    $this->assertEqual($remote->getRemoteIdentifier2(), $gengo_job['job_id']);
    $this->assertEqual($remote->word_count->value, $gengo_job['unit_count']);
    $this->assertEqual($remote->getRemoteData('credits'), $gengo_job['credits']);
    $this->assertEqual($remote->getRemoteData('tier'), $gengo_job['tier']);
  }

  public function testOrderModePullJob() {
    \Drupal::state()->set('tmgmt_mygengo_test_order_mode', 1);
    $this->loginAsAdmin();
    $this->translator->setSetting('api_public_key', 'correct key');
    $this->translator->setSetting('api_private_key', 'correct key');
    $this->translator->save();
    $job = $this->createJob();
    $standard = array(
      'quality' => 'standard',
    );
    $job->settings = $standard;
    $job->translator = $this->translator->id();
    $job->save();
    \Drupal::state()->set('tmgmt.test_source_data', array(
      'title' => array(
        '#text' => 'Hello world',
        '#label' => 'Title',
      ),
      'body' => array(
        '#text' => 'This is some testing content',
        '#label' => 'Body',
      ),
    ));
    $item = $job->addItem('test_source', 'test', '1');
    $item->save();
    \Drupal::state()->set('tmgmt.test_source_data', array(
      'title' => array(
        '#text' => 'Hello world 2',
        '#label' => 'Title',
      ),
      'body' => array(
        '#text' => 'This is some testing content 2',
        '#label' => 'Body',
      ),
      'another_field' => array(
        '#text' => 'More testing',
        '#label' => 'Another',
      ),
    ));
    $item2 = $job->addItem('test_source', 'test', '2');
    $item2->save();

    $job->requestTranslation();

    // Add the jobs as a response.
    $this->refreshVariables();
    $orders = \Drupal::state()->get('tmgmt_mygengo_test_orders', array());
    $order_id = key($orders);
    \Drupal::state()->set('tmgmt_mygengo_test_last_gengo_response', (object) array('jobs' => $orders[$order_id]));

    // Pull jobs from gengo.
    $this->drupalPostForm('admin/tmgmt/jobs/' . $job->id(), array(), t('Pull translations'));
    $this->assertText(t('All available translations from Gengo have been pulled.'));

    // Check the updated mappings of item 1.
    $remotes = RemoteMapping::loadByLocalData($job->id(), $item->id());
    $this->assertEqual(count($remotes), 2, '2 remotes for item 1');

    $gengo_job = $orders[$order_id][$job->id() . '][' . $item->id() . '][body'];

    \Drupal::entityManager()->getStorage('tmgmt_remote')->resetCache();
    $remotes = RemoteMapping::loadByLocalData($job->id(), $item->id(), 'body');
    $remote = reset($remotes);
    $this->assertEqual($remote->getRemoteIdentifier1(), $order_id);
    $this->assertEqual($remote->getRemoteIdentifier2(), $gengo_job['job_id']);
    $this->assertEqual($remote->word_count->value, $gengo_job['unit_count']);
    $this->assertEqual($remote->getRemoteData('credits'), $gengo_job['credits']);
    $this->assertEqual($remote->getRemoteData('tier'), $gengo_job['tier']);

    // And item 2.
    $remotes = RemoteMapping::loadByLocalData($job->id(), $item2->id());
    $this->assertEqual(count($remotes), 3, '3 remotes for item 2');
    $gengo_job = $orders[$order_id][$job->id() . '][' . $item2->id() . '][body'];

    \Drupal::entityManager()->getStorage('tmgmt_remote')->resetCache();
    $remotes = RemoteMapping::loadByLocalData($job->id(), $item2->id(), 'body');
    $remote = reset($remotes);
    $this->assertEqual($remote->getRemoteIdentifier1(), $order_id);
    $this->assertEqual($remote->getRemoteIdentifier2(), $gengo_job['job_id']);
    $this->assertEqual($remote->word_count->value, $gengo_job['unit_count']);
    $this->assertEqual($remote->getRemoteData('credits'), $gengo_job['credits']);
    $this->assertEqual($remote->getRemoteData('tier'), $gengo_job['tier']);
  }

  public function testAvailableStatus() {
    $this->loginAsAdmin();

    // Make sure we have correct keys.
    $this->translator->setSetting('api_public_key', 'correct key');
    $this->translator->setSetting('api_private_key', 'correct key');

    $this->translator->save();

    $job = $this->createJob();
    // Set quality to machine so it gets translated right away.
    $machine = array(
      'quality' => 'machine',
    );
    $job->settings = $machine;
    $job->translator = $this->translator->id();
    $job->save();
    \Drupal::state()->set('tmgmt.test_source_data', array(
      'wrapper' => array(
        '#label' => 'Parent label',
        'subwrapper' => array(
          '#text' => 'Hello world',
          '#label' => 'Sub label 1',
        ),
      ),
    ));
    $item = $job->addItem('test_source', 'test', '1');
    $item->save();

    $job->requestTranslation();

    // Make sure machine translation was received.
    \Drupal::entityManager()->getStorage('tmgmt_job_item')->resetCache();
    $items = $job->getItems();
    $item = end($items);
    $data = $item->getData();
    $this->assertEqual('mt_de_Hello world', $data['wrapper']['subwrapper']['#translation']['#text']);

    // Create another job with "same source" text. The translator service will
    // return an existing translation with status available.
    $job = $this->createJob();
    // Tell the mock service to return available translation.
    $availablestandard = array(
      'quality' => 'availablestandard',
    );
    $job->settings = $availablestandard;
    $job->translator = $this->translator->id();
    $job->save();
    \Drupal::state()->set('tmgmt.test_source_data', array(
      'wrapper' => array(
        '#label' => 'Text label',
        '#text' => 'Lazy-Loading Some text that has been submitted and translated.',
      ),
    ));
    $item = $job->addItem('test_source', 'test', '1');
    $item->save();

    $job->requestTranslation();

    // See if available translation from gengo has updated our translation.
    \Drupal::entityManager()->getStorage('tmgmt_job_item')->resetCache();
    $items = $job->getItems();
    $item = end($items);
    $data = $item->getData();
    $this->assertEqual('Translated Some text that has been submitted and translated.', $data['wrapper']['#translation']['#text']);
  }

  /**
   * Tests that duplicated strings can be translated correctly.
   */
  public function testDuplicateStrings() {
    $this->loginAsAdmin();

    // Make sure we have correct keys.
    $this->translator->setSetting('api_public_key', 'correct key');
    $this->translator->setSetting('api_private_key', 'correct key');

    $this->translator->save();

    $job = $this->createJob();
    // Set quality to machine so it gets translated right away.
    // @todo Add tests for standard.
    $machine = array(
      'quality' => 'machine',
    );
    $job->settings = $machine;
    $job->translator = $this->translator->id();
    $job->save();
    \Drupal::state()->set('tmgmt.test_source_data', array(
      'wrapper' => array(
        '#label' => 'Parent label',
        'duplicate1' => array(
          '#text' => 'This text is a duplicate',
          '#label' => 'Duplicate label 1',
        ),
        'duplicate2' => array(
          '#text' => 'This text is a duplicate',
          '#label' => 'Duplicate label 2',
        ),
      ),
    ));
    $item1 = $job->addItem('test_source', 'test', '1');
    $item1->save();
    \Drupal::state()->set('tmgmt.test_source_data', array(
      'wrapper' => array(
        '#label' => 'Parent label',
        'duplicate1' => array(
          '#text' => 'Not duplicate but same key',
          '#label' => 'Not duplicate',
        ),
        'real_duplicate' => array(
          '#text' => 'This text is a duplicate',
          '#label' => 'Duplicate label 3',
        ),
      ),
    ));
    $item2 = $job->addItem('test_source', 'test', '2');
    $item2->save();

    $job->requestTranslation();

    // Make sure the duplicated and not duplicated texts are translated.
    \Drupal::entityManager()->getStorage('tmgmt_job_item')->resetCache();
    list($item1, $item2) = array_values($job->getItems());

    // Item 1.
    $this->assertTrue($item1->isNeedsReview());
    $data = $item1->getData();
    $this->assertEqual('mt_de_This text is a duplicate', $data['wrapper']['duplicate1']['#translation']['#text']);
    $this->assertEqual('mt_de_This text is a duplicate', $data['wrapper']['duplicate2']['#translation']['#text']);

    // Item 2.
    $data = $item2->getData();
    $this->assertTrue($item2->isNeedsReview());
    $this->assertEqual('mt_de_This text is a duplicate', $data['wrapper']['real_duplicate']['#translation']['#text']);
    $this->assertEqual('mt_de_Not duplicate but same key', $data['wrapper']['duplicate1']['#translation']['#text']);
  }

  public function testComments() {
    $this->loginAsAdmin();

    // Create job with two job items.
    $this->translator->setSetting('api_public_key', 'correct key');
    $this->translator->setSetting('api_private_key', 'correct key');
    $this->translator->save();
    $job = $this->createJob();
    $standard = array(
      'quality' => 'standard',
    );
    $job->settings = $standard;
    $job->translator = $this->translator->id();
    $job->save();
    \Drupal::state()->set('tmgmt.test_source_data', array(
      'title' => array(
        '#text' => 'Hello world',
        '#label' => 'Title',
      ),
      'body' => array(
        '#text' => 'This is some testing content',
        '#label' => 'Body',
      ),
    ));
    $item = $job->addItem('test_source', 'test', '1');
    $item->save();
    \Drupal::state()->set('tmgmt.test_source_data', array(
      'title' => array(
        '#text' => 'Nice day',
        '#label' => 'Title',
      ),
      'body' => array(
        '#text' => 'It is nice day out there',
        '#label' => 'Body',
      ),
    ));
    $item = $job->addItem('test_source', 'test', '2');
    $item->save();

    // Request translation which also must create remote job mappings.
    $job->requestTranslation();

    // Get mapping for first data item of second job item -> Title "Nice day".
    $remotes = RemoteMapping::loadByLocalData($job->id(), $item->id(), 'title');
    $remote = reset($remotes);
    $this->drupalPostAjaxForm('admin/tmgmt/items/' . $item->id(), array(), array($remote->getRemoteIdentifier2() . '_comment_form' => '✉'));
    $this->assertText(t('New comment'));
    $comment = $this->randomMachineName();
    $edit = array(
      $remote->getRemoteIdentifier2() . '_comment' => $comment,
    );
    $this->drupalPostAjaxForm(NULL, $edit, array($remote->getRemoteIdentifier2() . '_submit' => t('Submit comment')));

    // Reload the review form again and check if comment text is present.
    $this->drupalGet('admin/tmgmt/items/' . $item->id());
    $this->assertText($comment);

    // Put first data item (Title "Nice day") into translated status so we can
    // request a revision.
    /* @var \Drupal\tmgmt_mygengo\Plugin\tmgmt\Translator\MyGengoTranslator $plugin */
    $plugin = $job->getTranslator()->getPlugin();
    $data = array(
      'status' => 'reviewable',
      'body_tgt' => 'Nice day translated',
    );
    $key = $item->id() . '][' . $remote->data_item_key->value;
    $plugin->saveTranslation($job, $key, $data);

    // Request a review.
    $comment = $this->randomMachineName();
    $this->drupalPostAjaxForm('admin/tmgmt/items/' . $item->id(), array(), array($remote->getRemoteIdentifier2() . '_revision_form' => '✍'));
    $edit = array(
      $remote->getRemoteIdentifier2() . '_comment' => $comment,
    );
    $this->drupalPostAjaxForm(NULL, $edit, array($remote->getRemoteIdentifier2() . '_submit' => t('Request revision')));

    $job = Job::load(($job->id()));
    $data = $job->getData(\Drupal::service('tmgmt.data')->ensureArrayKey($key));
    // Test the data item status - should be back to pending.
    $this->assertEqual($data[$item->id()]['#status'], TMGMT_DATA_ITEM_STATE_PENDING);
    // Reload the review form again and check if comment text is present.
    $this->drupalGet('admin/tmgmt/items/' . $item->id());
    $this->assertText($comment);
  }

  public function testPullJob() {
    $this->loginAsAdmin();
    $this->translator->setSetting('api_public_key', 'correct key');
    $this->translator->setSetting('api_private_key', 'correct key');
    $this->translator->save();
    $job = $this->createJob();
    $standard = array(
      'quality' => 'standard',
    );
    $job->settings = $standard;
    $job->translator = $this->translator->id();
    $job->save();
    \Drupal::state()->set('tmgmt.test_source_data', array(
      'title' => array(
        '#text' => 'Hello world',
        '#label' => 'Title',
      ),
      'body' => array(
        '#text' => 'This is some testing content',
        '#label' => 'Body',
      ),
    ));
    $item = $job->addItem('test_source', 'test', '1');
    $item->save();

    $job->requestTranslation();
    $this->refreshVariables();

    // Load fake gengo response and simulate the that the title job
    // gets translated.
    $data = \Drupal::state()->get('tmgmt_mygengo_test_last_gengo_response');
    $key = $job->id() . '][' . $item->id() . '][title';
    $data->jobs[$key]['status'] = 'approved';
    $data->jobs[$key]['body_tgt'] = 'Title translated';
    \Drupal::state()->set('tmgmt_mygengo_test_last_gengo_response', $data);

    // Pull jobs from gengo.
    $this->drupalPostForm('admin/tmgmt/jobs/' . $job->id(), array(), t('Pull translations'));
    $this->assertText(t('All available translations from Gengo have been pulled.'));

    // Reload item data.
    \Drupal::entityManager()->getStorage('tmgmt_job_item')->resetCache();
    $item = JobItem::load($item->id());
    $item_data = $item->getData();

    // Title should be translated by now.
    $this->assertEqual($item_data['title']['#translation']['#text'], 'Title translated');
    $this->assertEqual($item_data['title']['#status'], TMGMT_DATA_ITEM_STATE_TRANSLATED);
    // Body should be untouched.
    $this->assertTrue(empty($item_data['body']['#translation']));
    $this->assertEqual($item_data['body']['#status'], TMGMT_DATA_ITEM_STATE_PENDING);
  }

  public function testGengoCheckoutForm() {
    $this->loginAsAdmin();
    $this->translator->setSetting('api_public_key', 'correct key');
    $this->translator->setSetting('api_private_key', 'correct key');
    $this->translator->save();
    $job = $this->createJob();
    $standard = array(
      'quality' => 'standard',
    );
    $job->settings = $standard;
    $job->translator = $this->translator->id();
    $job->save();
    \Drupal::state()->set('tmgmt.test_source_data', array(
      'title' => array(
        '#text' => 'Hello world',
        '#label' => 'Title',
      ),
      'body' => array(
        '#text' => 'This is some testing content',
        '#label' => 'Body',
      ),
    ));
    $item = $job->addItem('test_source', 'test', '1');
    $item->save();

    $this->drupalGet('admin/tmgmt/jobs/' . $job->id());
    $needed_credits = $this->xpath('//div[@id=:id]', array(':id' => 'edit-settings-price-quote'));
    // The quote service returns two jobs each worth 2.
    $this->assertTrue(strpos($needed_credits[0]->asXML(), '4') !== FALSE);

    $word_count = $this->xpath('//div[@id=:id]', array(':id' => 'edit-settings-price-quote'));
    // The quote service returns two jobs each having 2 words.
    $this->assertTrue(strpos($word_count[0]->asXML(), '4') !== FALSE);

    $remaining_credit = $this->xpath('//div[@id=:id]', array(':id' => 'edit-settings-remaining-credits'));
    // The account balance service returns static value of 25.32 USD.
    $this->assertTrue(strpos($remaining_credit[0]->asXML(), '25.32 USD') !== FALSE);

    $eta = $this->xpath('//div[@id=:id]', array(':id' => 'edit-settings-eta'));
    // The quote service returns ETA of now + one day.
    $out = $eta[0]->asXML();
    // Check both cases to prevent case when we are here at the minute border
    // and one second ahead of the page render.
    $check_1 = strpos($out, format_date(time() + 60 * 60 * 24, "long")) !== FALSE;
    $check_2 = strpos($out, format_date((time() + 60 * 60 * 24) - 1, "long")) !== FALSE;
    $this->assertTrue($check_1 || $check_2);
  }

  /**
   * Tests the UI of the plugin.
   */
  protected function testGengoUi() {
    $this->loginAsAdmin();
    $this->drupalGet('admin/tmgmt/translators/manage/mygengo');

    // Try to connect with invalid credentials.
    $edit = [
      'settings[api_public_key]' => 'wrong key',
      'settings[api_private_key]' => 'wrong key',
    ];
    $this->drupalPostForm(NULL, $edit, t('Connect'));
    $this->assertText(t('The "Gengo API Public key" is not correct.'));

    // Test connection with valid credentials.
    $edit = [
      'settings[api_public_key]' => 'correct key',
      'settings[api_private_key]' => 'correct key',
    ];
    $this->drupalPostForm(NULL, $edit, t('Connect'));
    $this->assertText('Successfully connected!');

    // Assert that default remote languages mappings were updated.
    $this->assertOptionSelected('edit-remote-languages-mappings-en', 'en');
    $this->assertOptionSelected('edit-remote-languages-mappings-de', 'de');

    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText(t('@label configuration has been updated.', ['@label' => $this->translator->label()]));
  }

}
