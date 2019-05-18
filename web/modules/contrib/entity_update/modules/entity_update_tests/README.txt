Test for the module Entity Update
---------------------------------

Content Entity  : OK
Config Entity   : OK

SimpleTests
-----------

# Test with result on web browser.
HOST="http://drupal.loc/";
php core/scripts/run-tests.sh --browser --url $HOST --verbose --class \
"Drupal\entity_update_tests\Tests\EntityUpdateInstallUninstallTest"

php core/scripts/run-tests.sh --browser --url $HOST --verbose --class \
"Drupal\entity_update_tests\Tests\EntityUpdateUIAccessTest"

php core/scripts/run-tests.sh --browser --url $HOST --verbose --class \
"Drupal\entity_update_tests\Tests\EntityUpdateFunctionsTest"

php core/scripts/run-tests.sh --browser --url $HOST --verbose --class \
"Drupal\entity_update_tests\Tests\EntityUpdateProgUpTest"

# Test without web browser.
php core/scripts/run-tests.sh --verbose --class \
"Drupal\entity_update_tests\Tests\EntityUpdateInstallUninstallTest"

php core/scripts/run-tests.sh --verbose --class \
"Drupal\entity_update_tests\Tests\EntityUpdateUIAccessTest"

php core/scripts/run-tests.sh --verbose --class \
"Drupal\entity_update_tests\Tests\EntityUpdateFunctionsTest"

php core/scripts/run-tests.sh --verbose --class \
"Drupal\entity_update_tests\Tests\EntityUpdateProgUpTest"

TODO : UI EXEC tests
