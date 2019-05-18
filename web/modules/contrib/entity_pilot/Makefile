#!/usr/bin/make -f
PHPCBF=../../vendor/bin/phpcbf
PHPCS=../../vendor/bin/phpcs
PHPUNIT=../../vendor/bin/phpunit -c ../../core

lint-php:
	$(PHPCS) -as

fix-php:
	$(PHPCBF)

test:
	$(PHPUNIT) .
