<?php
/**
 * Created by PhpStorm.
 * User: Hardik
 * Date: 23-08-2018
 * Time: 03:16 PM
 */

header('Content-Type: application/json');

$file = str_replace(".", "", $_GET['file']);
$file = "download/" . $file . ".txt";

if (file_exists($file)) {

    $text = file_get_contents($file);
    echo $text;

    $obj = json_decode($text);

    if ($obj->percent == 100) {
        unlink($file);
    }
}
else {
    echo json_encode(array("percent" => null, "message" => null));
}
