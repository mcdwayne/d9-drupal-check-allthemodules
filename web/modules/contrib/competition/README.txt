Competition
-------
This module supports the creation of competitions. It provides management tools
necessary for managing a continuous cycle of competitions that run on your site.

Features
--------------------------------------------------------------------------------
The primary features include:

* Fieldable competitions

* Multi-round judging

* Public voting

* Downloadable reports


Installation
--------------------------------------------------------------------------------
Download this module to your Drupal modules folder and enable.


Global competition configuration
--------------------------------------------------------------------------------

"Structure > Competitions > Settings"


Competition configuration
--------------------------------------------------------------------------------

"Structure > Competitions > Add competition"

You can add fields to your competitions, similar to other D8 content entities.


Creating a competition entry
--------------------------------------------------------------------------------

Users can create a competition entry by Navigating to
"/competition/[machine_name]/enter"


Judging
--------------------------------------------------------------------------------

Configure your judging rounds

1. Navigate to "Structure > Competitions > [your_competition]".
2. Click the "Judging" table.
3. Select the "Enable judging for the active cycle" checkbox.
4. Add and configure rounds.
  * You can have unlimited rounds.
  * A "Pass/Fail" round allows judges to only select pass or fail for an entry.
  * A "Weighted Criteria" allows judges to score an entry based on different
    criteria.
  * Example criteria:
      30|Content
      50|Creativity
      20|Presentation
  * Click "Save".

Set the active judging round:

1. It is recommended that you create a "Competition Judge" role with the
   "Judge competition entries" permission.
2. Grant the "Competition Judge" role to some users.
3. You may also need to grant the "View the administration theme" to the
   "Competition Judge" role, depending on your installation.
4. Set the competition status to "Closed".
5. Navigate to "Content > Competition entries > Judging".
6. Click the "Setup" tab.
7. The "Assign judges to rounds" section will list users with the
   "Judge competition entries" role. Use this section to configure which user
   should judge each round(s).
8. Set the "Active round" to Round 1 and click "Update".
9. Click the "Assign Entries" button to assign entries to the specific judge
   user.

Judging users can now login and judge entries.


Voting
--------------------------------------------------------------------------------

The public voting feature is a special judging round. See the README in the
"competition_voting" sub-mod.


Acknowledgements
--------------------------------------------------------------------------------

This module was sponsored by SochaDev & Discovery Education and was built with
love by MattDanger, tea.time and natemow.
