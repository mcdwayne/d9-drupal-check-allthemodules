<?php
/**
 * Created by PhpStorm.
 * User: josh
 * Date: 28/03/2016
 * Time: 5:23 PM
 */

namespace Drupal\tealeaf\Controller;

class TeaLeafController {
    public function leaf() {
        $build['tealeaf']['#attached']['library'][] = 'tealeaf/tealeaf-styling';
        $build['tealeaf']['#title'] = 'TeaLeaf';
        $build['tealeaf']['#markup'] = t('<div id="tealeaf-container">
    <div id="tealeaf-controls">
        <div><p> Find and load a node:</p>
            <p>
                <label for="tealeaf-nid" class="visually-hidden">Find a node:</label>
                <input type="text" id="tealeaf-nid" value="1">
                <input type="button" value="Load" id="tealeaf-finder">
            </p>
        </div>
        <div>
            <p>Load node 1:</p>
            <p><input type="button" value="Load Node 1" id="tealeaf-loader"></p>
        </div>
    </div>
    <div id="tealeaf-results">
        <!-- results will appear here -->
    </div>
</div>');

        return $build['tealeaf'];

    }
}

?>

