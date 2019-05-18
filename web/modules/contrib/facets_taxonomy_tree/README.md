Facets taxonomy tree provides a facet processor that allows you to show the entire taxonomy tree with out having to have all the term ids in the index. It plays well with facets hierarchy processor.

Installation:
- Enable the module on the extend admin page.

Setup:
- Navigate to a facets processor settings and check the 'Show full taxonomy tree' checkbox.
- Select the vocabulary the field relates to and click save.

Note while you don't have to have the whole taxonomy tree indexed with your documents, you do need to make sure that the field being index relates to the vocabulary that you selected. Otherwise you'll just see 0 count values for all your facets.
