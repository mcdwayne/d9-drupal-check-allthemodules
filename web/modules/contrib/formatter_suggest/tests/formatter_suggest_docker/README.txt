Contains an environment for local testing.
It contains a Docker network and a composer-specified Drupal installation. The module is linked from the parent directory (i.e., local changes and patches
may be tested in real time).

To use:

- install Docker
- docker-compose up
- ./dcomposer install
- cd scripts && ./simpletest.sh
