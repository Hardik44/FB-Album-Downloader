<?php
/**
 * Created by PhpStorm.
 * User: Hardik
 * Date: 16-08-2018
 * Time: 01:46 AM
 */
require_once 'config.php';
require_once 'lib/google-api-php-client-2.2.2/vendor/autoload.php';
require_once 'drive.php';

use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

    if(!session_id()){
        session_start();
    }

    ini_set ( 'max_execution_time', 0);

    $all = false;
    $drive = false;
    $albumSet = array();
    $arg = "";

    if(isset($_GET['download_all_drive'])){
        $drive = true;
        $all = true;
    }
    else {
        if(isset($_GET['selected_drive'])){
            $drive = true;
        }
        foreach ($_GET as $key => $value) {
            $part = strpos($key,'_drive');

            if($part !== false){
                $drive = true;
            }

            if ($key === 'download_all') {
                $all = true;
                break;
            } else {
                $name = str_replace("_drive", "", $key);
                $arg .= $name.'*';
                $name = str_replace("_"," ",$name);
                $albumSet[$name] = true;

            }
        }
    }

if(isset($_SESSION['facebook_access_token'])){
    $FB->setDefaultAccessToken($_SESSION['facebook_access_token']);
}else{
    $_SESSION['facebook_access_token'] = (string) $accessToken;

    $oAuth2Client = $FB->getOAuth2Client();

    $longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($_SESSION['facebook_access_token']);
    $_SESSION['facebook_access_token'] = (string) $longLivedAccessToken;

    $FB->setDefaultAccessToken($_SESSION['facebook_access_token']);
}



if(isset($_GET['code'])){
    header('https://my-album-downloader.herokuapp.com/index.php');
}

try {
    $response = $FB->get('me?fields=name,albums{name,photos{images}}');

} catch(FacebookResponseException $e) {
    echo 'Graph returned an error: ' . $e->getMessage();
    session_destroy();
    header("https://my-album-downloader.herokuapp.com/home.php");
    exit;
} catch(FacebookSDKException $e) {
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
}

$graphNode = $response->getGraphNode();
$graphDecoded = $response->getDecodedBody();
$json = $graphNode->asJson();
$data = json_decode($json);
$userName = $data->name;

for ($i=0; $i<count($graphDecoded['albums']['data']); $i++){
    if(isset($graphDecoded['albums']['data'][$i]['photos']['paging']['next'])){
        try {
            $nextPage = $graphDecoded['albums']['data'][$i]['photos']['paging']['next'];
            $nextPage = str_replace('https://graph.facebook.com/v3.1/','',$nextPage);
            $nextPageData = $FB->get($nextPage);

        } catch(FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            session_destroy();
            header("https://my-album-downloader.herokuapp.com/home.php");
            exit;
        } catch(FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        $nextPageData = $nextPageData->getDecodedBody();
        $nextPageData = json_decode(json_encode($nextPageData),FALSE);
        $temp = array();
        $temp = $data->albums[$i]->photos;
        unset($data->albums[$i]->photos);

        while(isset($nextPageData->paging->next)){
            $temp = array_merge($temp,$nextPageData->data);
            $nextLink = $nextPageData->paging->next;
            $nextLink = str_replace('https://graph.facebook.com/v3.1/','',$nextLink);
            $nextPageData = $FB->get($nextLink);
            $nextPageData = $nextPageData->getDecodedBody();
            $nextPageData = json_decode(json_encode($nextPageData),FALSE);
        }

        $data->albums[$i]->photos = json_decode(json_encode(array_merge($temp,$nextPageData->data)));
    }
}

$data = $data->albums;
$userName = str_replace(" ","_",$userName);

if($drive){
    $driveObj = new GoogleDrive(session_id());
    $client = $driveObj->getClient();
}

if(!file_exists('download/facebook_' . $userName. '_albums')){
    mkdir('download/facebook_' . $userName. '_albums');
}


$total = 0;
if($all==true){
    foreach ($data as $key => $value){
        $total += count($value->photos);
    }
}else{
    foreach ($data as $key => $value){
        $albumName = $value -> name;
        if(isset($albumSet[$albumName])){
            $total += count($value->photos);
        }
    }
}

if($total == 0){
    $total = 1;
}

$i = 0;
$array_progress = array();
foreach ($data as $key => $value){
    $albumName = $value -> name;
    if($all == true){

        if(!file_exists('download/facebook_' . $userName . '_albums/' . $albumName)){
            mkdir('download/facebook_' . $userName . '_albums/' . $albumName);
        }

        $photos = $value -> photos;

        foreach ($photos as $key => $value){
            $imageURL = $value -> images[0] -> source;

            //for progressbar
            $i++;
            $percent = intval($i/$total * 100);

            if($drive == true && $i == $total){
                $percent = 0;
            }

            $array_progress['percent'] = $percent;
            $array_progress['message'] = $i . " photo(s) processed.";

            file_put_contents("download/".session_id().".txt", json_encode($array_progress));
            copy($imageURL, 'download/facebook_' . $userName . '_albums/' . $albumName . '/' . $key . '.jpg');
        }

    }else if(isset($albumSet[$albumName])){
        if(!file_exists('download/facebook_' . $userName . '_albums/' . $albumName)){
            mkdir('download/facebook_' . $userName . '_albums/' . $albumName);
        }

        $photos = $value -> photos;

        foreach ($photos as $key => $value){
            $imageURL = $value -> images[0] -> source;
            $i++;
            $percent = intval($i/$total * 100);
            
            if($drive == true && $i == $total){
                $percent = 0;
            }

            $array_progress['percent'] = $percent;
            $array_progress['message'] = $i . " photo(s) processed.";

            file_put_contents("download/".session_id().".txt", json_encode($array_progress));
            copy($imageURL, 'download/facebook_' . $userName . '_albums/' . $albumName . '/' . $key . '.jpg');
        }
    }
}

// Get real path for our folder
$rootPath = __DIR__ . '/download/facebook_' . $userName.'_albums';

if($drive==false) {
    // Initialize archive object
    $zip = new ZipArchive();
    $zip->open('./download/facebook_' . $userName . '_albums.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

    ob_clean();
    ob_end_flush();

    // Create recursive directory iterator
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootPath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        // Skip directories (they would be added automatically)
        if (!$file->isDir()) {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($rootPath) + 1);

            // Add current file to archive
            $zip->addFile($filePath, $relativePath);
        }
    }

    // Zip archive will be created only after closing object
    $zip->close();

}


    if($all == true){
        $all = "true";
    }else
        $all = "false";

    if($drive == true){
        $drive = "true";
    }else
        $drive = "false";

    $arg .= $all."*";
    $arg .= $drive."*";
    $arg .= $userName."*";
    $arg .= $total."*";
    $arg .= $_SESSION['facebook_access_token']."*";
    $arg .= session_id();

    $_SESSION['name'] = $userName;

    shell_exec("php background.php ".$arg." &");
    header('Location: home.php');
    exit();




