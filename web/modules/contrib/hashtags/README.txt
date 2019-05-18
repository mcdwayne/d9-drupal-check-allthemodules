Hashtags module allows to use the hashtags for the content entities.

-- GETTING STARTED --

1. Install Hashtags in the usual way.
(https://drupal.org/node/895232)
2. Go to Administration > Help > Hashtags (admin/help/hashtags)
and check the pictures how to config and work with the module.

-- Simple configuration --

1. Administration > Configuration > Hashtags
(admin/config/content/hashtags/manager_form)
and click on "Activate Hashtags" button for the
corresponding content type (for example Content > Article).
2. Go to Article Create Form (node/add/article)
and create an article
Title: Test Article
Body: My #special content
and submit a form.

-- FOR DEVELOPERS --

$node = \Drupal\node\Entity\Node::load(18);
$body_value = $node->body->value;
$body_value .= "ddd ggg lll #ccc sss";
$node->body->value = $body_value;
$node->save();

This code will update body field of the node with nid = 18 and
will create/attach the 'ccc' hashtag to the 'field_hashtags' field.

-- UNINSTALLATION --

Hashtags module attaches Hashtags filter for Basic HTML and
Full HTML text formats that should be unattached manually
before uninstallation.

Go to Administration > Configuration >
      Content authoring > Text formats and editors > Basic HTML
      (admin/config/content/formats/manage/basic_html)
then uncheck Hashtags filter box and submit a form.

Go to Administration > Configuration >
      Content authoring > Text formats and editors > Full HTML
      (admin/config/content/formats/manage/full_html)
then uncheck Hashtags filter box and submit a form.

