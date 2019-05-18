This module provides a Drupal Console command to output information on all revisions of an entity and to specifically
list embedded entities.

## Usage

1. Enable module
2. `$ drupal entity_explorer node 123`

Example output:
```
Found 77 revisions for id 12345
 Revision 45678 (French): Programme de relève 
    Field Title: Programme de relève 
    Field ID: 12345
    Field Versions-ID: 45678
    Field Language: fr
    Field Content type: page
    ...
    Field Inhalt:
      - Paragraph of type Section (ID: xxx, Revision: xxx)
            Field Inhalt:
              - Paragraph of type Text (ID: xxx, Revision: xxx)
              - Paragraph of type Group (ID: xxx, Revision: xxx)
                Field Inhalt:
                  - Paragraph of type Text (ID: xxx, Revision: xxx)
      - Paragraph of type Section (ID: xxx, Revision: xxx)
            Field Inhalt:
              - Paragraph of type Image (ID: xxx, Revision: xxx)
 ...
```

## Limitations

* See EntityExplorer::processRevision for superfluous revision data with
  Paragraphs (all translated languages are listed per revision instead of
  only the one in which it was triggered).
* Only tested with nodes and paragraphs.
