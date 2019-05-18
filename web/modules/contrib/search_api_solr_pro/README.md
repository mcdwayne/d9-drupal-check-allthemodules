# search_api_solr_pro
Module extension to prevent the entities being loaded from solr results in views by a query setting.

#### Motivation:
The well know module search_api_solr (https://www.drupal.org/project/search_api_solr) force the entities to beign loaded from the solr results in views ignoring if the user only want to display particularly fields that are already indexed in solr.
In that case we can improve the performance of view generation avoiding the load of the entities.

#### Install module
1. Download the folder and place it in the drupal module folder
2. Activate the module in the drupal admin section extend
3. Set the solr server backend to "Solr Pro"
4. Move the index to the new server and re index them
5. Go to the view
6. Open the advance query setting dialog
7. Check the option "Skip entity load"
8. Specified the indexed fields you want to use in the view (If the field is set as excluded for render then it will not be available in the view)


Note: In the twig file for the view you will find the fields in the row object, for example:

```TWIG
<li class="alpha-section__list-item">
  {% if title %}
    <h3>{{ title }}</h3>
  {% endif %}
  <ul class="alpha-section__sublist">
    {% for row in rows %}
      {%
      set row_classes = [
      default_row_class ? 'views-row',
      ]
      %}
      <li>
        {% set result_row = row.content['#row']|obj_to_arr %}
        {% set name = result_row['entity:authors/person_name'][0].getText() %}
        {% set url = result_row['search_api_id']|replace({':und': ''}) %}
        {{ link(name, url) }}
      </li>
    {% endfor %}
  </ul>
</li>
```

#### Require

1. Drupal 8.X
2. Search API Module (https://www.drupal.org/project/search_api)
3. Search API Solr Module (https://www.drupal.org/project/search_api_solr)

#### License

```
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
```
