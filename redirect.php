<?php
/**
 * Created by PhpStorm.
 * User: Hardik
 * Date: 25-08-2018
 * Time: 12:07 AM
 */
    require_once 'lib/google-api-php-client-2.2.2/vendor/autoload.php';

    if(!session_id()){
        session_start();
    }

    if(isset($_GET['code'])){
        $client = new Google_Client();

        //$client->setScopes($this->scopes);
        //$client->setAuthConfig($this->secretPath);
        $client->setClientId('233613059117-klqg47t3p39lp13u2efp79f1b3qu5q06.apps.googleusercontent.com');
        $client->setClientSecret('rHRfDhHqnODTwZYQMY4PvV1O');
        $client->setAccessType('offline');
        $client->setRedirectUri('https://my-album-downloader.herokuapp.com/redirect.php');

        $_SESSION['googleToken'] = $client->fetchAccessTokenWithAuthCode($_GET['code']);

        //var_dump($_SESSION['googleToken']['access_token']);

        $fp = fopen('tmp/'.session_id().'.json', 'w');
        fwrite($fp, json_encode($_SESSION['googleToken']));
        fclose($fp);

        header('Location: https://my-album-downloader.herokuapp.com/home.php');
    }