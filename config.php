<?php
/**
 * Created by PhpStorm.
 * User: Hardik
 * Date: 12-08-2018
 * Time: 03:08 AM
 */
    session_start();
    require_once "lib/Facebook/Facebook.php";
    require_once 'lib/Facebook/autoload.php';

    use Facebook\Exceptions\FacebookResponseException;
    use Facebook\Exceptions\FacebookSDKException;

    try {
        $FB = new \Facebook\Facebook([
            'app_id' => '471426176693619',
            'app_secret' => '5f87fe63152b8a4fd21e366948d2d054',
            'default_graph_version' => 'v3.1'
        ]);
    }catch (Exception $e){
        throw $e;
    }

    $helper = $FB -> getRedirectLoginHelper();

    try {
        if(isset($_SESSION['facebook_access_token'])){
            $accessToken = $_SESSION['facebook_access_token'];
        }else{
            $accessToken = $helper->getAccessToken();
        }
    } catch(FacebookResponseException $e) {
        echo 'Graph returned an error: ' . $e->getMessage();
        exit;
    } catch(FacebookSDKException $e) {
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }


