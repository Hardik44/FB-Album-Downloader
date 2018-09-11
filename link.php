<?php
/**
 * Created by PhpStorm.
 * User: Hardik
 * Date: 19-08-2018
 * Time: 03:32 AM
 */

    if(!session_id()){
        session_start();
    }

    $userName = $_SESSION['name'];
    $file='./download/facebook_' . $userName . '_albums.zip';

    if (headers_sent()) {
        echo 'HTTP header already sent';
    } else {
        if (!is_file($file)) {
            header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
            echo 'File not found';
        } else if (!is_readable($file)) {
            header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
            echo 'File not readable';
        } else {
            header($_SERVER['SERVER_PROTOCOL'].' 200 OK');
            header("Content-Type: application/zip");
            header("Content-Transfer-Encoding: Binary");
            header("Content-Length: ".filesize($file));
            header("Content-Disposition: attachment; filename=\"".basename($file)."\"");
            readfile($file);
            unlink($file);
            exit;
        }
    }