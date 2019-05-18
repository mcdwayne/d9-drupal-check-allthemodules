# Testing

Automated tests are done using the [Drupal Module Tester](https://github.com/ibrows/drupal_module_tester) and [Codeship](https://codeship.com/)

The Drupal Module Tester is a public [docker image](https://hub.docker.com/r/pwcsexperiencecenter/drupal-module-tester/) that can install a MINIMAL install of drupal and a module of your choosing.
You can then use this to run the module's tests.

## Testing Locally

To only run the tests locally without Jet you should edit or copy the `docker-compose.yml` file and change the entrypoint to
`entrypoint: /opt/app-root/scripts/test.sh` and run `docker-compose up` or `docker-compose -f my-file.yml up`

## Jet

If you use codeship (or even if you don't) you can use their CLI tool to run the tests as the server will when you make a PR.
Instructions for installing Jet are [available here](https://documentation.codeship.com/pro/builds-and-configuration/cli/)
Once it is installed you can simple run `jet steps --tag MY_BRANCH_NAME` in the module root and your tests will be run on a clean drupal install in a container.
The default codeship-steps file will run tests on any branch matching the pattern `^(master|develop|feature)`