Taxonomy Based Visibility

NOTE: Applicable only on node view page .
INTRODUCTION
------------
    1.Taxonomy based visibility is the module which allows the user to set the 
    block visibility based on taxonomy terms.
    2.This Modules creates a new section "Taxonomy Based Visibility" in 
    Visibility section on every individual block configuration page.
    3.This will help the user to control block visibility in every node/content 
    view  page based on the taxonomy/category/term value selected in every node.
    Create a taxonomy entity reference field in any content types 
    and choose the taxonomy value.
    Ex: Navigate to Manage -> Structure -> Content Types -> Article 
    -> Manage Fields.
    Create single/multiple taxonomy reference field and enable it.

    Create a new content.
    Ex: Navigate to Manage -> Content-> Add Content -> Article 
    In the created taxonomy field,choose values in the available drop down.

    To Enable Block visibility 
    Navigate to Structure -> Block layout-> <block> -> Configure-> Visibility 
    -> Taxonomy Based Visibility.

    Ex: In taxonomy based visibility section you will get list of vocabularies,
    choose the taxonomy to which the block need to be visible.

    The block will be visible if the value checked in the block is equal to 
    the value chosen in the node entity reference.

REQUIREMENTS
------------
    Taxonomy

RECOMMENDED MODULES
-------------------

INSTALLATION
------------
    Install and enable the Taxonomy Based Visibility module in extend section. 

CONFIGURATION
-------------
