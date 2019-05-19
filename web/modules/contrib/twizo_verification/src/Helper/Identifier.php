<?php
/**
 * Created by PhpStorm.
 * User: WesselVrolijks
 * Date: 23/01/2018
 * Time: 14:11
 */

namespace Drupal\twizo\Controller;


class Identifier
{

    /**
     * Creates a unique identifier for a Drupal user.
     * @return string
     */
    public function createIdentifier($uid){
        return md5(uniqid($uid, true));
    }
}