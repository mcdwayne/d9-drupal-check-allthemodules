#!/bin/bash
#
# Lint php files.
#

echo 'Linting PHP files'
echo 'If you are getting a false negative, use:'
echo ''
echo '// @codingStandardsIgnoreStart'
echo '...'
echo '// @codingStandardsIgnoreEnd'
echo ''

docker run -v "$(pwd)"/src:/code dcycle/php-lint \
  --standard=DrupalPractice /code
docker run -v "$(pwd)"/src:/code dcycle/php-lint \
  --standard=Drupal /code
