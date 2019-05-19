# SimpleTests
-------------

HOST="http://drupal.loc/"
php core/scripts/run-tests.sh --browser --url $HOST --verbose --class \
"Drupal\simple_analytics\Tests\SimpleAnalyticsTestBase"

php core/scripts/run-tests.sh --browser --url $HOST --verbose --class \
"Drupal\simple_analytics\Tests\SimpleAnalyticsServerSideTest"

# Test without web browser.
---------------------------
php core/scripts/run-tests.sh --verbose --class \
"Drupal\simple_analytics\Tests\SimpleAnalyticsTestBase"

php core/scripts/run-tests.sh --verbose --url $HOST --class \
"Drupal\simple_analytics\Tests\SimpleAnalyticsTestBase"

php core/scripts/run-tests.sh --verbose --url $HOST --class \
"Drupal\simple_analytics\Tests\SimpleAnalyticsUninstallTest"

php core/scripts/run-tests.sh --verbose --url $HOST --class \
"Drupal\simple_analytics\Tests\SimpleAnalyticsAdminPagesTest"

php core/scripts/run-tests.sh --verbose --url $HOST --class \
"Drupal\simple_analytics\Tests\SimpleAnalyticsBasicPagesTest"

php core/scripts/run-tests.sh --verbose --url $HOST --class \
"Drupal\simple_analytics\Tests\SimpleAnalyticsConfigurationTest"

php core/scripts/run-tests.sh --verbose --url $HOST --class \
"Drupal\simple_analytics\Tests\SimpleAnalyticsBasicPagesTest"

php core/scripts/run-tests.sh --verbose --url $HOST --class \
"Drupal\simple_analytics\Tests\SimpleAnalyticsServerSideTest"


# Cleanup
#--------
php core/scripts/run-tests.sh --verbose --clean
