Testing Drupal Coverage
======================================

The Drupal Coverage project uses DrupalCI testing on drupal.org.

That means: It runs the testbot on every patch that is marked as 'Needs Review'.

Your patch might not get reviewed, and certainly won't get committed unless it
passes the testbot.

The testbot runs a script that's in your Drupal installation called
`core/scripts/run-tests.sh`. You can run `run-tests.sh` manually and approximate
the testbot's behavior.

You can find information on how to run `run-tests.sh` locally here:
https://www.drupal.org/node/645286

You should at least run `run-tests.sh` locally against all the changes in your
patch before uploading it.

Keep in mind that unless you know you're changing behavior that is being tested
for, the tests are not at fault. :-)
