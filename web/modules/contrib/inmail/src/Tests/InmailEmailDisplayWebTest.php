<?php

namespace Drupal\inmail\Tests;

/**
 * Tests all 'Email display' cases.
 *
 * @group inmail
 * @requires module past_db
 */
class InmailEmailDisplayWebTest extends InmailWebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'inmail_mailmute',
    'field_ui',
    'past_db',
    'past_testhidden',
    'inmail_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Make sure new users are blocked until approved by admin.
    \Drupal::configFactory()->getEditable('user.settings')
      ->set('register', USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL)
      ->save();
    // Enable logging of raw mail messages.
    \Drupal::configFactory()->getEditable('inmail.settings')
      ->set('log_raw_emails', TRUE)
      ->save();
  }

  /**
   * Tests the message Email Display behaviour of the Inmail Message element.
   */
  public function testEmailDisplay() {
    $this->doTestSimpleEmailDisplay();
    // Header field tests.
    $this->doTestMultipleFromRecipients();
    $this->doTestMissingToEmailDisplay();
    $this->doTestSameReplyToAsFromDisplay();
    $this->doTestMultipleReplyToDisplay();
    $this->doTestMultipleRecipients();
    $this->doTestMultipleBccRecipients();
    $this->doTestNoSubjectDisplay();
    $this->doTestHtmlOnlyHeaderFields();
    // Body message tests.
    $this->doTestMultipartAlternativeHtmlPlaintext();
    $this->doTestHtmlOnlyBodyMessage();
    $this->doTestXssEmailDisplay();
  }

  /**
   * Tests simple email message.
   */
  public function doTestSimpleEmailDisplay() {
    $raw_multipart = $this->getMessageFileContents('normal-forwarded.eml');
    $this->processRawMessage($raw_multipart);
    $event = $this->getLastEventByMachinename('process');

    // Check if the header fields are properly displayed in 'teaser' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/teaser');
    $this->assertAddressHeaderField('From', 'arild@masked1.se', 'Arild Matsson');
    $this->assertAddressHeaderField('To', 'inmail_test@example.com', 'Arild Matsson');
    $this->assertAddressHeaderField('CC', 'inmail_other@example.com', 'Someone Else');
    $this->assertNoElementHeaderField('Date', '2014-10-21 11:21:01');
    $this->assertNoElementHeaderField('Received', '2014-10-21 11:21:02');
    $this->assertElementHeaderField('Subject', 'BMH testing sample');
    $this->assertNoLink('Unsubscribe');
    // Assert only 'plain-text' raw element is present.
    $this->assertNoText('just because I have no HTML mailbox');
    $this->assertRawBody('Plain', 'Hey, it would be really bad for a mail handler to classify this as a bounce
just because I have no mailbox outside my house.');

    // Check if the header fields are properly displayed in 'full' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/full');
    $this->assertAddressHeaderField('From', 'arild@masked1.se', 'Arild Matsson');
    $this->assertAddressHeaderField('To', 'inmail_test@example.com', 'Arild Matsson');
    $this->assertAddressHeaderField('CC', 'inmail_other@example.com', 'Someone Else');
    $this->assertElementHeaderField('Date', '2014-10-21 20:21:01');
    $this->assertElementHeaderField('Received', '2014-10-21 20:21:02');
    $this->assertElementHeaderField('Subject', 'BMH testing sample');
    // @todo use assertUnsubscribeHeaderField()/assertNoUnsubscribeHeaderField()?
    $this->assertLink('Unsubscribe');
    // Assert both 'plain-text' and 'HTML' body parts in 'full' view mode.
    $this->assertRaw('<a href="#inmail-message__body__html">HTML</a>');
    $this->assertRaw('<a href="#inmail-message__body__content">Plain</a>');
    // Script tags are removed for security reasons.
    $this->assertRawBody('HTML', '<div dir="ltr">Hey, it would be really bad for a mail handler to classify this as a bounce just because I have no HTML mailbox outside my house.</div>');
    $this->assertRawBody('Plain', 'Hey, it would be really bad for a mail handler to classify this as a bounce<br/>
just because I have no mailbox outside my house.');

    // Test the access to past event created by non-inmail module.
    // @see \Drupal\inmail_test\Controller\EmailDisplayController.
    $event = past_event_create('past', 'test1', 'Test log entry');
    $event->save();
    $this->drupalGet('admin/inmail-test/email/' . $event->id());
    // Should throw a NotFoundHttpException.
    $this->assertResponse(404);
    $this->assertText('Page not found');
  }

  /**
 * Tests email rendering with multiple 'From' recipients.
 */
  public function doTestMultipleFromRecipients() {
    $raw_multiple_from = $this->getMessageFileContents('/addresses/multiple-from.eml');
    $this->processRawMessage($raw_multiple_from);
    $event = $this->getLastEventByMachinename('process');

    // Assert all 'From' recipients are properly shown in 'full' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/full');
    $this->assertAddressHeaderField('From', 'andy@example.com', 'Andy', 1);
    $this->assertAddressHeaderField('From', 'roger@example.com', 'Roger', 2);
    $this->assertAddressHeaderField('From', 'rafael@example.com', 'Rafael', 3);
    $this->assertAddressHeaderField('reply to', 'novak@example.com');
    $this->assertAddressHeaderField('To', 'novak@example.com', 'Novak');

    // Assert all 'From' recipients are properly shown in 'teaser' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/teaser');
    $this->assertAddressHeaderField('From', 'andy@example.com', 'Andy', 1);
    $this->assertAddressHeaderField('From', 'roger@example.com', 'Roger', 2);
    $this->assertAddressHeaderField('From', 'rafael@example.com', 'Rafael', 3);
    // Never display 'Reply-To' in 'teaser'.
    $this->assertNoAddressHeaderField('reply to', 'novak@example.com');
    $this->assertAddressHeaderField('To', 'novak@example.com', 'Novak');
  }

  /**
   * Tests an email message without the 'To' header field.
   */
  public function doTestMissingToEmailDisplay() {
    // According to RFC 2822, 'To' header field is not strictly necessary.
    $raw_missing_to = $this->getMessageFileContents('/addresses/missing-to-field.eml');
    $this->processRawMessage($raw_missing_to);
    $event = $this->getLastEventByMachinename('process');

    // Check that the raw message is logged.
    $this->assertEqual($event->getArgument('email')->getData(), $raw_missing_to);

    // Assert no 'To' header field is displayed in 'full' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/full');
    $this->assertNoAddressHeaderField('To');

    // Assert no 'To' header field is displayed in 'teaser' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/teaser');
    $this->assertNoAddressHeaderField('To');
  }

  /**
   * Tests an email with same 'Reply-To' and 'From' header fields.
   */
  public function doTestSameReplyToAsFromDisplay() {
    $raw_same_reply_to_as_from = $this->getMessageFileContents('/addresses/reply-to-same-as-from.eml');
    $this->processRawMessage($raw_same_reply_to_as_from);
    $event = $this->getLastEventByMachinename('process');

    // Do not display 'Reply-To' in 'full', if it is the same as 'From'.
    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/full');
    $this->assertAddressHeaderField('From', 'bob@example.com', 'Bob');
    $this->assertNoAddressHeaderField('reply to', 'bob@example.com', 'Bob');

    // Never display 'Reply-To' in 'teaser'.
    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/teaser');
    $this->assertAddressHeaderField('From', 'bob@example.com', 'Bob');
    $this->assertNoAddressHeaderField('reply to', 'bob@example.com', 'Bob');
  }

  /**
   * Tests an email with multiple 'Reply-To' mailboxes, including an identical.
   */
  public function doTestMultipleReplyToDisplay() {
    $raw_multiple_reply_to = $this->getMessageFileContents('/addresses/reply-to-multiple.eml');
    $this->processRawMessage($raw_multiple_reply_to);
    $event = $this->getLastEventByMachinename('process');

    // Even if one of the 'Reply-To' addresses is identical to 'From', all
    // mailboxes should be visible in 'full' view mode header.
    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/full');
    $this->assertAddressHeaderField('From', 'bob@example.com', 'Bob');
    $this->assertAddressHeaderField('reply to', 'bob@example.com', 'Bob', 1);
    $this->assertAddressHeaderField('reply to', 'bobby@example.com', 'Bobby', 2);

    // Never display 'Reply-To' in 'teaser'.
    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/teaser');
    $this->assertAddressHeaderField('From', 'bob@example.com', 'Bob');
    $this->assertNoAddressHeaderField('reply to', 'bob@example.com', 'Bob', 1);
    $this->assertNoAddressHeaderField('reply to', 'bobby@example.com', 'Bobby', 2);
    // Do not display Date in teaser (bug found in #2824195, see comment #14).
    $this->assertNoElementHeaderField('Date');
  }

  /**
   * Tests the proper rendering of an email with multiple recipients.
   */
  public function doTestMultipleRecipients() {
    $raw_multipart = $this->getMessageFileContents('/addresses/multiple-recipients.eml');
    \Drupal::state()->set('inmail.test.success', '');
    $this->processRawMessage($raw_multipart);
    $event = $this->getLastEventByMachinename('process');

    // Assert all recipients are properly displayed in 'full' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/full');
    $this->assertAddressHeaderField('From', 'inmail_from@example.com', 'Arthur Smith');
    $this->assertAddressHeaderField('reply to', 'inmail_reply_to1@example.com', 'Rachel', 1);
    $this->assertAddressHeaderField('reply to', 'inmail_reply_to2@example.com', 'Ronald', 2);
    $this->assertAddressHeaderField('To', 'inmail_to1@example.com', 'Bonnie', 1);
    $this->assertAddressHeaderField('To', 'inmail_to2@example.com', 'Bob', 2);
    $this->assertAddressHeaderField('CC', 'inmail_cc1@example.com', 'Christine', 1);
    $this->assertAddressHeaderField('CC', 'inmail_cc2@example.com', 'Carl', 2);

    // Assert the recipients are properly displayed in 'teaser' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/teaser');
    $this->assertAddressHeaderField('From', 'inmail_from@example.com', 'Arthur Smith');
    // Never display 'Reply-To' in 'teaser'.
    $this->assertNoAddressHeaderField('reply to', 'inmail_reply_to1@example.com', 'Rachel', 1);
    $this->assertNoAddressHeaderField('reply to', 'inmail_reply_to2@example.com', 'Ronald', 2);
    $this->assertAddressHeaderField('To', 'inmail_to1@example.com', 'Bonnie', 1);
    $this->assertAddressHeaderField('To', 'inmail_to2@example.com', 'Bob', 2);
    $this->assertAddressHeaderField('CC', 'inmail_cc1@example.com', 'Christine', 1);
    $this->assertAddressHeaderField('CC', 'inmail_cc2@example.com', 'Carl', 2);
  }

  /**
   * Tests the email with multiple Bcc recipients.
   */
  public function doTestMultipleBccRecipients() {
    $raw_multipart = $this->getMessageFileContents('/addresses/multiple-bcc-recipients.eml');
    \Drupal::state()->set('inmail.test.success', '');
    $this->processRawMessage($raw_multipart);
    $event = $this->getLastEventByMachinename('process');

    // Assert all recipients are properly displayed in 'full' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/full');
    $this->assertAddressHeaderField('From', 'bob@example.com', 'Bob');
    $this->assertAddressHeaderField('To', 'alice@example.com', 'Alice');
    $this->assertAddressHeaderField('Bcc', 'big_brother@example.com', 'BigBrother', 1);
    $this->assertAddressHeaderField('Bcc', 'president@example.com', 'President', 2);
    $this->assertAddressHeaderField('Bcc', 'vp@example.com', 'ViceP', 3);

    // Assert that 'Bcc' is not displayed in 'teaser' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/teaser');
    $this->assertNoElementHeaderField('Bcc');
  }

  /**
   * Tests an email message without the 'Subject' header field.
   */
  public function doTestNoSubjectDisplay() {
    // According to RFC 2822, 'Subject' header field is not strictly necessary.
    $raw_missing_subject = $this->getMessageFileContents('/simple/missing-subject-field.eml');
    $this->processRawMessage($raw_missing_subject);
    $event = $this->getLastEventByMachinename('process');

    // Check that 'Subject' default empty text is shown for 'full' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/full');
    $this->assertElementHeaderField('Subject', '(no subject)');

    // Check that 'Subject' default empty text is shown for 'teaser' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/teaser');
    $this->assertElementHeaderField('Subject', '(no subject)');
  }

  /**
   * Tests the header fields for HTML-only email message example.
   */
  public function doTestHtmlOnlyHeaderFields() {
    $raw_html_only = $this->getMessageFileContents('/simple/html-text.eml');
    $this->processRawMessage($raw_html_only);
    $event = $this->getLastEventByMachinename('process');

    // Check the HTML-only header fields for 'full' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/full');
    $this->assertAddressHeaderField('From', 'bob@example.com', 'Bob');
    $this->assertAddressHeaderField('To', 'alice@example.com', 'Alice');
    $this->assertElementHeaderField('Date', '2016-10-29 00:32:00');
    $this->assertElementHeaderField('Received', '2016-10-28 23:41:00');
    $this->assertElementHeaderField('Subject', 'Happy Birthday!');

    // Check the HTML-only header fields for 'teaser' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/teaser');
    $this->assertAddressHeaderField('From', 'bob@example.com', 'Bob');
    $this->assertAddressHeaderField('To', 'alice@example.com', 'Alice');
    $this->assertNoElementHeaderField('Date', '2016-10-29 00:32:00');
    $this->assertNoElementHeaderField('Received', '2016-10-28 23:41:00');
  }

  /**
   * Tests proper iteration and rendering of multipart message.
   */
  public function doTestMultipartAlternativeHtmlPlaintext() {
    $raw_multipart = $this->getMessageFileContents('/simple/multipart-alternative-html-plaintext.eml');
    $this->processRawMessage($raw_multipart);
    $event = $this->getLastEventByMachinename('process');

    // Check message plain-text/HTML body parts in 'full' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/full');
    $this->assertRaw('<a href="#inmail-message__body__html">HTML</a>');
    $this->assertRaw('<a href="#inmail-message__body__content">Plain</a>');
    // Assert the markup inside 'HTML' tab.
    $this->assertRawBody('HTML', '<div dir="ltr"><div>Hello my dear Alice! I am sending you this wonderful HTML greeting from El Dorado.</div></div>');
    // Assert new line separator is replaced with '<br/>' tag in 'Plain' tab.
    $this->assertRawBody('Plain', 'Hello my dear Alice! I am sending you this wonderful greeting from El<br/>
Dorado.');

    // Check message plain-text/HTML body parts in 'teaser' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/teaser');
    $this->assertRawBody('Subject', 'Multipart-alternative HTML');
    // Assert only 'plain-text' raw element is present.
    $this->assertNoRawBody('HTML', 'this wonderful HTML greeting from El Dorado');
    $this->assertRawBody('Plain', 'Hello my dear Alice! I am sending you this wonderful greeting from El
Dorado.');
  }

  /**
   * Tests proper iteration and rendering of HTML-only message.
   */
  public function doTestHtmlOnlyBodyMessage() {
    $raw_html_only = $this->getMessageFileContents('/simple/html-text.eml');
    $this->processRawMessage($raw_html_only);
    $event = $this->getLastEventByMachinename('process');

    // Check the HTML-only body fields for 'full' view mode.
    // There should be two tabs since the plain text is generated from HTML.
    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/full');
    $this->assertRaw('<a href="#inmail-message__body__html">HTML</a>');
    $this->assertRaw('<a href="#inmail-message__body__content">Plain</a>');
    // Assert the markup inside 'HTML' tab.
    $this->assertRawBody('HTML', '<div dir="ltr">
  <p>Hey Alice,</p>
  <p>Skype told me its your birthday today? Congratulations!</p>
  <p>Wish I could be there and celebrate with you...</p>
  <p>Sending you virtual cake, french cheese and champagne (without alcohol, just for you!) :P</p>
  <p>Cheerious,</p>
  <p>Bob</p>
</div>');
    // Assert new line separators are replaced with '<br />' tags.
    $this->assertRawBody('Plain', 'Hey Alice,<br/>
  Skype told me its your birthday today? Congratulations!<br/>
  Wish I could be there and celebrate with you...<br/>
  Sending you virtual cake, french cheese and champagne (without alcohol, just for you!) :P<br/>
  Cheerious,<br/>
  Bob');

    // Check the HTML-only body fields for 'teaser' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/teaser');
    $this->assertRawBody('Subject', 'Happy Birthday!');
    $this->assertNoRawBody('HTML', '<div dir="ltr">
  <p>Hey Alice,</p>
  <p>Skype told me its your birthday today? Congratulations!</p>
  <p>Wish I could be there and celebrate with you...</p>
  <p>Sending you virtual cake, french cheese and champagne (without alcohol, just for you!) :P</p>
  <p>Cheerious,</p>
  <p>Bob</p>
</div>');
    $this->assertRawBody('Plain', 'Hey Alice,
  Skype told me its your birthday today? Congratulations!
  Wish I could be there and celebrate with you...
  Sending you virtual cake, french cheese and champagne (without alcohol, just for you!) :P
  Cheerious,
  Bob');
  }

  /**
   * Tests a XSS case and that its raw mail message is logged.
   */
  public function doTestXssEmailDisplay() {
    $raw_message = $this->getMessageFileContents('simple/xss.eml');

    // In reality the message would be passed to the processor through a drush
    // script or a mail deliverer.
    // Process the raw mail message.
    $this->processRawMessage($raw_message);

    // Check that the raw message is logged.
    $event = $this->getLastEventByMachinename('process');
    $this->assertEqual($event->getArgument('email')->getData(), $raw_message);

    $this->drupalGet('admin/inmail-test/email/' . $event->id() . '/full');

    $this->assertNoRaw("<script>alert(");
    $this->assertNoRaw("<script>alert('xss_attack0')</script>");
    $this->assertNoRaw("<script>alert('xss_attack1')</script>");
    $this->assertNoRaw("<script>alert('xss_attack2')</script>");
    $this->assertNoRaw("<script>alert('xss_attack3')</script>");
  }

}
