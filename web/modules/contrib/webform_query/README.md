Webform Query
=============

#### Query Webform Submission Data

The Webform module stores all submission data in the one table using the EAV model. This is great for performance but it can be tricky to extract submission data matching certain criteria.

Webform Query extracts submission IDs based on field values. Currently it supports adding conditions and specifying the webform ID. For example, this would return all `event_registration` submissions for `event` 1 where `age` is 18 or over:

    $query = \Drupal::service('webform_query');

    $query->addCondition('event', 1)
          ->addCondition('age', 18, '>=')
          ->setWebform('event_registration');
    $results = $query->execute();

The return type is an array of objects with a single property `sid`, the submission ID. The webform submissions can then be loaded in the normal way.

`webform_id` is optional. This would return all webform submissions where the value of the `event` field is 1:

    $query = \Drupal::service('webform_query');

    $query->addCondition('event', 1);
    $results = $query->execute()

Sort the results:

    $query = \Drupal::service('webform_query');

    $query->addCondition('event', 1);
    $query->orderBy('age', 'DESC');
    $results = $query->execute()

"ASC" is used if the second parameter is missing.