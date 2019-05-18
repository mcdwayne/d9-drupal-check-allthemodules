
GENERAL INFORMATION
-------------------

This module implements the Porter-Stemmer algorithm, version 2, to improve
English-language searching with the Drupal built-in Search module. Information
about the algorithm can be found at
http://snowball.tartarus.org/algorithms/english/stemmer.html

Stemming reduces a word to its basic root or stem (e.g. 'blogging' to 'blog') so
that variations on a word ('blogs', 'blogger', 'blogging', 'blog') are
considered equivalent when searching. This generally results in more relevant
results.

Note that a few parts of the Porter Stemmer algorithm work better for American
English than British English, so some British spellings will not be stemmed
correctly.

This module will use the PECL "stem" library's implementation of the Porter
Stemmer algorithm, if it is installed on your server. If the PECL "stem" library
is not available, the module uses its own PHP implementation of the
algorithm. The output is the same in either case. More information about the
PECL "stem" library: http://pecl.php.net/package/stem


INSTALLATION
------------

See the INSTALL.txt file for installation instructions.


TESTING
-------

The Porter Stemmer module includes tests for the stemming algorithm and
functionality.  If you would like to run the tests, enable the core Testing
module, and then run the tests following instructions on
https://www.drupal.org/docs/8/phpunit/running-phpunit-tests. Commands below:

cd core
../vendor/bin/phpunit --group porterstemmer


Each test for the Porter Stemmer module includes approximately
5000 individual word stemming tests (which test the module against a standard
word list).

Tests are provided both for the internal algorithm and the PECL library.

There is also a functional test for integration with Drupal search.
