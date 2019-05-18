# Fragments
Fragments are re-usable bits of content. Fragments are similar to
Paragraphs, but where Paragraphs are bound to their host entity (and
revisioned along with it), fragments are meant to be re-used and
potentially update the same bit of content across many pages. Fragments
are fieldable and revisionable.

## Required modules
### Entity Reference
Although not *technically* a requirement (i.e. Drupal won't force you to
install the module when it's not already installed), Fragments's use is
extremely limited without it. Entity Reference is the basic method with
which you associate Fragments to e.g. nodes in order to display them on
a page for end users. With that said, please report any other uses you
might find for Fragments that do not require Entity Reference (if that
sounds like a challenge, it is).

## Recommended modules
Several modules go together particularly well with Fragments, depending on your use-case.

### [Inline Entity Form](https://www.drupal.org/project/inline_entity_form)
Inline Entity Form offers a entity reference widget that will display a
form to create a new fragment inline in e.g. the node form where you
reference fragments. In certain use cases you may want to enforce the
need to create a fragment separately, but in many cases it is extremely
convenient to be able to create a new fragment on the fly, right where
you need it.

### [Automatic Entity Label](https://www.drupal.org/project/auto_entitylabel)
Every fragment needs a label to identify it in the interface, but in
many cases you do not want to tie this to a title displayed to users
(i.e. two fragments may need the same title on your site's frontend, but
editors still need a way to distinguish them). So, you end up adding a
<em>Display Title</em> field. This module will help to set things up so
editorial users only need to fill in the display title in most cases,
but still have the option to override the administrative label when
needed.
