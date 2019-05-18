#!/usr/bin/make -f

# This assumes the module is installed under modules/contrib/cloudfront_purger.
DRUPAL_ROOT=../../..

php-lint:
	@echo ">>> Linting PHP..."
	bin/phpcs --report=full --standard=vendor/drupal/coder/coder_sniffer/Drupal/ruleset.xml --extensions=php,module src tests

php-fix:
	@echo ">>> Fixing PHP..."
	bin/phpcbf --standard=vendor/drupal/coder/coder_sniffer/Drupal/ruleset.xml --extensions=php,module src tests

test:
	@echo ">>> Running tests..."
	cd ${DRUPAL_ROOT} && php core/scripts/run-tests.sh --sqlite /tmp/test.sqlite --module cloudfront_purger
