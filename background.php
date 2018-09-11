<?php
/**
 * Created by PhpStorm.
 * User: Hardik
 * Date: 10-09-2018
 * Time: 03:46 PM
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

$arg = explode("*",$argv[1]);
$albumSet = array();

$i=0;
for ($i=0; $i<count($arg); $i++){
    if($arg[$i]=="true" || $arg[$i]=="false")
        break;
    $name = str_replace("_"," ",$arg[$i]);
    $albumSet[$name] = true;
}
if($arg[$i]=="true")
    $all = true;
else
    $all = false;

if($arg[$i+1]=="true")
    $drive = true;
else
    $drive = false;

$userName = $arg[$i+2];
$total = $arg[$i+3];
$_SESSION['facebook_access_token'] = $arg[$i+4];
$sessionID = $arg[$i+5];

$dirname = __DIR__ . '/download/facebook_' . $userName. '_albums';

$i=0;
$percent = 0;

if($drive==true){
    $drive = new GoogleDrive($sessionID);
    $rootId = $drive->createFolder('facebook_'.$userName.'_albums');

    if($all == true) {
        $dir = new RecursiveDirectoryIterator($dirname, RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($dir as $directories) {
            if ($directories->getFilename() == '.' || $directories->getFilename() == '..')
                continue;

            $subroot = $drive->createSubFolder($directories->getFilename(), $rootId);
            $fileInfo = new RecursiveDirectoryIterator($directories, RecursiveIteratorIterator::LEAVES_ONLY);

            foreach ($fileInfo as $pathname => $file) {
                if (!$file->isFile()) continue;
                $drive->uploadFileToFolder($file->getFilename(), $pathname, $subroot);

                $i++;
                $percent = intval($i/$total * 100);
                $array_progress['percent'] = $percent;
                $array_progress['message'] = $i . " photo(s) uploaded to Drive.";

                file_put_contents("download/".$sessionID.".txt", json_encode($array_progress));
            }
        }
    }else{
        $dir = new RecursiveDirectoryIterator($dirname, RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($dir as $directories) {
            if ($directories->getFilename() == '.' || $directories->getFilename() == '..')
                continue;

            if(isset($albumSet[$directories->getFilename()])) {

                $subroot = $drive->createSubFolder($directories->getFilename(), $rootId);
                $fileInfo = new RecursiveDirectoryIterator($directories, RecursiveIteratorIterator::LEAVES_ONLY);

                foreach ($fileInfo as $pathname => $file) {
                    if (!$file->isFile()) continue;
                    $drive->uploadFileToFolder($file->getFilename(), $pathname, $subroot);

                    $i++;
                    $percent = intval($i/$total * 100);
                    $array_progress['percent'] = $percent;
                    $array_progress['message'] = $i . " photo(s) uploaded to Drive.";

                    file_put_contents("download/".$sessionID.".txt", json_encode($array_progress));
                }
            }
        }
    }

}



if (is_dir($dirname)) {
    $dir = new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS);
    foreach (new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::CHILD_FIRST ) as $filename => $file) {
        if (is_file($filename))
            unlink($filename);
        else
            rmdir($filename);
    }
    rmdir($dirname); // Now remove directory which created
}



$_SESSION['name'] = $userName;
header('Location: home.php');
exit();

