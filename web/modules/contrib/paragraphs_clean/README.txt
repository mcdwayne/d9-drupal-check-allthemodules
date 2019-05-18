Tired of your database filling up with orphaned Paragraph entities?
This module is for you.

A single module file with 3 hook implementations, this module implements a naive, rudimentary approach to cleaning up paragraphs.

If you save a node (or any other entity) on which you've removed a reference to a paragraph entity, the paragraph entity will be deleted.

If you don't want to delete orphaned paragraphs, or if you're re-using paragraphs among different entities, then don't install this module.
