# Taxonomy code examples

## Example 1
### Before
Without the use of data_api.

    $item = taxonomy_term_load(123);
    $title = check_plain($item->name);
    if (!empty($item->field_edu_subject_short)) {
        $title = field_view_value('taxonomy_term', $item, 'field_edu_subject_short', $item->field_edu_subject_short[$lang][0]);
        $title = drupal_render($title);
    }
    
### After
The above code is refactored like this:

    $tt = data_api('taxonomy_term');
    ...
    $item = taxonomy_term_load(123);
    $title = $tt->get($item, 'field_edu_subject_short.0', check_plain($item->name), function ($title) use ($item) {
        $title = field_view_value('taxonomy_term', $item, 'field_edu_subject_short', $title);
    
        return drupal_render($title);
    });

## Example 2: Iteration
### Before
    if (!empty($item->field_edu_level)) {
        foreach (array_keys($item->field_edu_level) as $lang) {
            foreach ($item->field_edu_level[$lang] as $delta => $edu_level) {
                ...
            }
        }
    }
    
    
### After
Iterations become much simpler:
    
    $tt = data_api('taxonomy_term');
    ...
    foreach ($tt->get($item, 'field_edu_level', []) as $delta => $edu_level) {
        ...
    }
