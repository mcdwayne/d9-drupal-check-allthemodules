# Marketo Subscribe

This module adds a block where users can leave their e-mail address in order to
subscribe for a list in Marketo.

## Requirements

- Drupal 8.x
- Composer <https://getcomposer.org/>
- An active Marketo account <http://www.marketo.com/>
- The Marketo MA module <https://www.drupal.org/project/marketo_ma> installed
and enabled

## Installation

1. Copy the entire `marketo_subscribe` directory the Drupal `modules`
   directory. You can optionally user drush to download with `drush 
   dl marketo_subscribe`.

2. Login as an administrator. Enable the Marketo Subscribe module in "Extend".

3. Ensure that you have configured Marketo in "Configuration" -> "Marketo MA".

4. Add the block to your site via "Structure" -> "Block layout".

5. Specify the region where you want to place your block and click on
   "Place block" -> "Marketo Subscribe block".

6. (Optional) In case you want to add the user's details to a specific list in
   Marketo, fill in the ID of the list in
   the field "List ID".
   
7. Specify the visibility & region of the block.

8. Save the block.

## Maintainers

Current maintainers:

- Levi Govaerts (legovaer) - <https://www.drupal.org/u/legovaer>
