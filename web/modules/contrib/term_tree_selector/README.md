# Term Tree Selector

Provides configurable root/leaf selector blocks for term trees with 2 or 3 levels.

Terms will only show in the selector if they are published and have content.

## Installation

Once enabled, you can place one or more "Term Tree Selector" blocks
with the following configuration:

**Vocabulary:** The hierarchical vocabulary to use

**Root Label:** The label for the root terms, e.g. "Make", used to
display "Select Make" in the root select box.

**Leaf Label:** The label for the leaf terms, e.g. "Model", used to
display "Select Model" in the leaf select box.

**Leaf Level:** The number levels to traverse to populate the leaf select
box (Second, Third or All). "All" will show the second and third levels.

**Description (optional):** Text that will be displayed above the form

**Submit Label:** The text shown on the submit button

**Auto Submit:** If checked the form will automatically be submitted 
once a leaf has been selected

## REST Endpoint
The following REST Export endpoint is provided by the
"Term Tree Selector" view.

### Root terms
```
/term-tree-selector/{vocabulary}/level/{level}
```
Returns the root level terms for provided vocabulary and level.

+ Parameters
    + vocabulary (string) - Vocabulary machine name
    + level - Leaf level to show (`2`,`3`,`all`)

#### Retrieve Root Terms [GET]

+ Response 200 (application/json)

    + Body

            [
              {
                "name": "Ford"
                "tid": 123,
                "url": "/ford"
              }
            ]

### Leaf terms
```
/term-tree-selector/{vocabulary}/level/{level}/{tid}
```
Returns the leaf level terms for provided vocabulary, parent root ID and level.

+ Parameters
    + vocabulary (string) - Vocabulary machine name
    + level - Leaf level to show (`2`,`3`,`all`)
    + tid (number) - Term ID of the root term

#### Retrieve Leaf Terms [GET]

+ Response 200 (application/json)

    + Body

            [
              {
                "name": "Ford Focus"
                "tid": 456,
                "url": "/ford/focus"
              }
            ]

### JavaScript Events

#### onTermTreeSelectorChange

Fired when the root or leaf element changes.

JSON data is passed as a property in the detail object.

##### Example usage:

```
  document.addEventListener('onTermTreeSelectorChange', function (e) {
     var data = e.detail.data;
     var options = e.target.options;
  }, false);
```
