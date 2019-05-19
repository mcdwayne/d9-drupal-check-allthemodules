<?php

interface SShttpInterface{
    public function publishEvent($data);
    public function sendLog($data);
    public function sendSlackNotify($data);
    public function sendUpdates($data);
    public function sendHealth($data);
    public function sendRequest($data, $url = false, $id_handle = false);
}