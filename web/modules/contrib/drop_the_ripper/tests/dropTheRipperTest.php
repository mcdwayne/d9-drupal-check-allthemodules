<?php

namespace Unish;

/**
 * @group commands
 * @group dtr
 */
class dropTheRipperTest extends CommandUnishTestCase {

    /**
     * {@inheritdoc}
     */
    public function setUp() {
        if (UNISH_DRUPAL_MAJOR_VERSION < 7) {
            $this->markTestSkipped('DtR supports D7 and D8');
        }
        if (!$this->getSites()) {
            $this->setUpDrupal(1, TRUE);
            $this->siteOptions = array(
                'root' => $this->webroot(),
                'uri' => key($this->getSites()),
                'yes' => NULL,
            );
            $this->setUpDtrUsersAndRoles();
        }
        else {
            $this->siteOptions = array(
                'root' => $this->webroot(),
                'uri' => key($this->getSites()),
                'yes' => NULL,
            );
        }
    }

    public function setUpDtrUsersAndRoles() {
        // Create some users.
        $users = array(
            'arthur' => array(
                'password' => '12345', // top 25
            ),
            'ford' => array(
                'password' => 'abc123', // top 25
            ),
            'zahphod' => array(
                'password' => 'computer', // top 25
            ),
            'marvin' => array(
                'password' => 'changeme', // top 50
            ),
            'trillian' => array(
                'password' => 'XkOjV?2#Mt3|7baT', // not in wordlist
            ),
            'slartibartfast' => array(
                'password' => 'gandalf', // top 100
            ),
        );
        foreach ($users as $user => $account) {
            $this->drush('user-create', array($user), $this->siteOptions + array('password' => $account['password']));
        }
        // Also set user 1's password; "admin" is quite a long way down the
        // wordlist, but should be guessed based on the user name.
        $this->drush('user-password', array('admin'), $this->siteOptions + array('password' => 'admin'));

        // Create some custom roles
        // (n.b. there's a fairly limited set of permissions available)
        $roles = array(
            'earthman' => array(
                'perms' => array(
                    'access content',
                ),
                'users' => array(
                    'arthur',
                    'trillian'
                ),
            ),
            'hitchhiker' => array(
                'perms' => array(
                    'access user profiles',
                    'change own username',
                ),
                'users' => array(
                    'arthur',
                    'ford',
                    'trillian',
                ),
            ),
            'android' => array(
                'perms' => array(
                    'administer software updates', // restricted
                    'select account cancellation method', // restricted
                ),
                'users' => array(
                    'marvin',
                ),
            ),
            'designer' => array(
                'perms' => array(
                    'administer site configuration', // restricted
                    'administer themes',
                ),
                'users' => array(
                    'slartibartfast',
                ),
            ),
        );
        foreach ($roles as $name => $role) {
            $this->drush('role-create', array($name), $this->siteOptions);
            $this->drush('role-add-perm', array($name, implode(',', $role['perms'])), $this->siteOptions);
            $this->drush('user-add-role', array($name, implode(',', $role['users'])), $this->siteOptions);
        }
    }

    /**
     * Ensure that a log message does not appear in the Drush log.
     *
     * @param $log Parsed log entries from backend invoke
     * @param $message The expected message that must be contained in
     *   some log entry's 'message' field.  Substrings will match.
     * @param $logType The type of log message to look for; all other
     *   types are ignored. If FALSE (the default), then all log types
     *   will be searched.
     */
    function assertLogHasNotMessage($log, $message, $logType = FALSE) {
        foreach ($log as $entry) {
            if (!$logType || ($entry['type'] == $logType)) {
                if (strpos($entry['message'], $message) !== FALSE) {
                    $this->fail("Found message in log: " . $message);
                }
            }
        }
        return TRUE;
    }

    public function testDtrDefaultOptions() {
        $this->drush('drop-the-ripper', array(), $this->siteOptions + array('backend' => NULL));
        $parsed = $this->parse_backend_output($this->getOutput());
        $this->assertTrue($this->assertLogHasMessage($parsed['log'], 'Match: uid=1 name=admin password=admin', 'success'));
        $this->assertTrue($this->assertLogHasMessage($parsed['log'], 'Match: uid=2 name=arthur password=12345', 'success'));
        $this->assertTrue($this->assertLogHasMessage($parsed['log'], "Check: uid=3 name=ford for password 'ford'", 'debug'));
        $this->assertTrue($this->assertLogHasMessage($parsed['log'], 'Match: uid=3 name=ford password=abc123', 'success'));
        $this->assertTrue($this->assertLogHasMessage($parsed['log'], 'Match: uid=4 name=zahphod password=computer', 'success'));
        $this->assertTrue($this->assertLogHasNotMessage($parsed['log'], 'Match: uid=5 name=marvin', 'success'));
        $this->assertTrue($this->assertLogHasNotMessage($parsed['log'], 'Match: uid=6 name=trillian', 'success'));
        $this->assertTrue($this->assertLogHasNotMessage($parsed['log'], 'Match: uid=7 name=slartibartfast', 'success'));
    }

    public function testDtrTopOption() {
        $options = array('top' => 50);
        $this->drush('drop-the-ripper', array(), $this->siteOptions + array('backend' => NULL) + $options);
        $parsed = $this->parse_backend_output($this->getOutput());
        $this->assertTrue($this->assertLogHasMessage($parsed['log'], 'Match: uid=1 name=admin password=admin', 'success'));
        $this->assertTrue($this->assertLogHasMessage($parsed['log'], 'Match: uid=2 name=arthur password=12345', 'success'));
        $this->assertTrue($this->assertLogHasMessage($parsed['log'], "Check: uid=3 name=ford for password 'ford'", 'debug'));
        $this->assertTrue($this->assertLogHasMessage($parsed['log'], 'Match: uid=3 name=ford password=abc123', 'success'));
        $this->assertTrue($this->assertLogHasMessage($parsed['log'], 'Match: uid=4 name=zahphod password=computer', 'success'));
        $this->assertTrue($this->assertLogHasMessage($parsed['log'], 'Match: uid=5 name=marvin password=changeme', 'success'));
        $this->assertTrue($this->assertLogHasNotMessage($parsed['log'], 'Match: uid=6 name=trillian', 'success'));
        $this->assertTrue($this->assertLogHasNotMessage($parsed['log'], 'Match: uid=7 name=slartibartfast', 'success'));
    }

    public function testDtrRestrictedOption() {
        $options = array('restricted' => NULL, 'top' => 100); // @todo: should really only be testing one thing
        $this->drush('drop-the-ripper', array(), $this->siteOptions + array('backend' => NULL) + $options);
        $parsed = $this->parse_backend_output($this->getOutput());
        $this->assertTrue($this->assertLogHasMessage($parsed['log'], 'Match: uid=1 name=admin password=admin', 'success'));
        $this->assertTrue($this->assertLogHasMessage($parsed['log'], 'Match: uid=5 name=marvin password=changeme', 'success'));
        $this->assertTrue($this->assertLogHasMessage($parsed['log'], 'Match: uid=7 name=slartibartfast password=gandalf', 'success'));
        $this->assertTrue($this->assertLogHasNotMessage($parsed['log'], 'Match: uid=4 name=zaphod', 'success'));
        $this->assertTrue($this->assertLogHasNotMessage($parsed['log'], 'Match: uid=6 name=trillian', 'success'));
    }

    public function testDtrHideOption() {
        $options = array('hide' => NULL);
        $this->drush('drop-the-ripper', array(), $this->siteOptions + array('backend' => NULL) + $options);
        $parsed = $this->parse_backend_output($this->getOutput());
        $this->assertTrue($this->assertLogHasMessage($parsed['log'], 'Match: uid=1 name=admin password=XXXXXXXXXX', 'success'));
        $this->assertTrue($this->assertLogHasMessage($parsed['log'], 'Match: uid=2 name=arthur password=XXXXXXXXXX', 'success'));
        $this->assertTrue($this->assertLogHasMessage($parsed['log'], "Check: uid=3 name=ford for password 'ford'", 'debug'));
        $this->assertTrue($this->assertLogHasMessage($parsed['log'], 'Match: uid=3 name=ford password=XXXXXXXXXX', 'success'));
        $this->assertTrue($this->assertLogHasMessage($parsed['log'], 'Match: uid=4 name=zahphod password=XXXXXXXXXX', 'success'));
        $this->assertTrue($this->assertLogHasNotMessage($parsed['log'], 'Match: uid=2 name=arthur password=12345', 'success'));
        $this->assertTrue($this->assertLogHasNotMessage($parsed['log'], 'Match: uid=5 name=marvin password=changeme', 'success'));
    }

    public function testDtrNoGuessingOption() {
        $options = array('no-guessing' => NULL);
        $this->drush('drop-the-ripper', array(), $this->siteOptions + array('backend' => NULL) + $options);
        $parsed = $this->parse_backend_output($this->getOutput());
        $this->assertTrue($this->assertLogHasMessage($parsed['log'], 'Match: uid=2 name=arthur password=12345', 'success'));
        $this->assertTrue($this->assertLogHasMessage($parsed['log'], 'Match: uid=3 name=ford password=abc123', 'success'));
        $this->assertTrue($this->assertLogHasMessage($parsed['log'], 'Match: uid=4 name=zahphod password=computer', 'success'));
        $this->assertTrue($this->assertLogHasNotMessage($parsed['log'], 'Match: uid=1 name=admin password=admin', 'success'));
        $this->assertTrue($this->assertLogHasNotMessage($parsed['log'], "Check: uid=3 name=ford for password 'ford'", 'debug'));
        $this->assertTrue($this->assertLogHasNotMessage($parsed['log'], 'Match: uid=5 name=marvin', 'success'));
        $this->assertTrue($this->assertLogHasNotMessage($parsed['log'], 'Match: uid=6 name=trillian', 'success'));
        $this->assertTrue($this->assertLogHasNotMessage($parsed['log'], 'Match: uid=7 name=slartibartfast', 'success'));
    }
}
