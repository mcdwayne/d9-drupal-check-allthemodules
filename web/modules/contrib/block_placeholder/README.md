Block Placeholder
===========

The block placeholder module allows content editors to associate block content to a particular block placeholder. Yes, this is very similar to how block regions work, but allows for a much easier configuration deployment process. As block content placement information is stored in configuration, and depends on the block content which may or may not exist yet. 

As a developer you'll be able to set a block placeholder in a block region. The placeholder can be configured to allow certain block types to reference it. No more having to sync block content along with the block placement configurations. So you can say goodbye to those "This block is broken or missing" messages after deploying your configurations. 

Installation
------------

* Normal module installation procedure. See
  https://www.drupal.org/documentation/install/modules-themes/modules-8


Initial Setup
------------

After the installation, you have to create a Block Placeholder. You'll be able to define what block types are allowed to be referenced. As well as how many references can be associated with the particular placeholder.

Block placeholders can be created at: `/admin/structure/block/block-placeholder`

After you've created a block placeholder you'll need to setup where that block content should be rendered. Navigate to `/admin/structure/block` and click `Place block`, then select the `Block Placeholder` block type. 

Select the block placeholder you've created. Go ahead and set your block name accordingly, and click save.

Now, when you create any block content you'll be able to associate it from the block edit form, to a particular block placeholder. The content will be rendered in the appropriate placeholder that's contained in the desired region.

After block content has been referenced to a block placeholder, you'll be able to order the content `/admin/structure/block/block-placeholder/{INPUT_PLACEHOLDER_ID}/order`. You're also able to set the weight in the block content edit form to control the ordering of the content.
