Closest Zip Code
=====

[![CircleCI](https://circleci.com/gh/dcycle/closest_zip_code.svg?style=svg)](https://circleci.com/gh/dcycle/closest_zip_code)

A Drupal 8 module which allows you get a closest zip code to another zip code. An API is provided, but no user interface.

Usage
-----

### Step 1: Install as you would any Drupal module:

    drush dl closest_zip_code

### Step 2: Try it!

Let's say you are at zipcode 78376, and you have three locations, 78629, 01343 and 99919, and you want know which is closest, run:

    drush ev 'print_r(\Drupal\closest_zip_code\ClosestZipCode\App::instance()->closestZipCode("00720", ["00723", "00725", "00727"]))'

**It is important to put your zipcodes in quotes so they are treated as strings**.

You should see something like:

    Array
    (
        [errors] => Array
            (
            )

        [zip] => 00720
        [lat] => 18.217946
        [lon] => -66.428076
        [zips] => Array
            (
                [00727] => Array
                    (
                        [km] => 37.4453002632
                        [miles] => 23.267430784452
                        [lat] => 18.215308
                        [lon] => -66.073565
                    )

                [00725] => Array
                    (
                        [km] => 40.738204346371
                        [miles] => 25.313546513168
                        [lat] => 18.218819
                        [lon] => -66.042375
                    )

                [00723] => Array
                    (
                        [km] => 47.721027769338
                        [miles] => 29.652471813056
                        [lat] => 18.043498
                        [lon] => -66.015479
                    )

            )

        [duration-seconds] => 0.014860153198242
    )

Issue queue and pull requests
-----

Please use the [Drupal issue queue](https://www.drupal.org/project/issues/search/closest_zip_code) for this project.

Please run tests by running `./scripts/test.sh` (you do not need to install or configure anything except Docker to run this) on your proposed changes before suggesting patches. Use [GitHub](https://github.com/dcycle/closest_zip_code) for pull requests.

Development
-----

The code is available on [GitHub](https://github.com/dcycle/closest_zip_code) and [Drupal.org](https://www.drupal.org/project/closest_zip_code).

Automated testing is on [CircleCI](https://circleci.com/gh/dcycle/closest_zip_code).

To install a local version for development or evaluation, install Docker and run `./scripts/deploy.sh`.
