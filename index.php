<?php
/**
 * Created by PhpStorm.
 * User: Hardik
 * Date: 03-08-2018
 * Time: 06:34 PM
 */
    require_once "config.php";

    $page_title = "FB Album downloader";
    $redirectURL = 'https://my-album-downloader.herokuapp.com/home.php';
    $permission = ['user_photos'];
    $loginURL = $helper->getLoginUrl($redirectURL, $permission);

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title><?php echo $page_title; ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="stylesheet" href="index.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

    </head>

    <body background="img/home.jpg">

    <div class="title">
        <h1>Facebook Album Downloader</h1>
        <p>Download your Facebook albums or upload to Google drive at one click away!!</p>
        <div class="login">
            <button class="fb btn" onclick="window.location = '<?php echo $loginURL ?>';">
                <i class="fa fa-facebook fa-fw"></i>
                Log In with Facebook
            </button>
        </div>
    </div>




    </body>
</html>


