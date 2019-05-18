Block content machine name
--------

Block content machine name adds machine_name field for block_content block
content type. Which is used to define the specific block template for that 
specific block.

Block content machine name module requires 
[machine_name_widget](https://www.drupal.org/project/machine_name_widget) 
module.

After successful installation the module will provide additional template 
suggestion for FE.
- block--block_content--[MACHINE_NAME].html.twig

i.e. If you have block_content created for copyright block and you have created
machine_name for that block as copyright. Then You will have template 
suggestions as listed below.
- block--block_content--copyright.html.twig
- block-content--MACHINE_NAME
- block-type-block-content

you can also add template suggestion in block content while create / edit of 
the block. With this feature you can use the same template again and again for 
different block contents.


Setup
--------

Enable the module and you will have the machine name field. you have to update
the machine name manually (for now) for all the block_content.

Author/Maintainer
-----------------

- [Mitesh Patel](https://www.drupal.org/u/miteshmap)
