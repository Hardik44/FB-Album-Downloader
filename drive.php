<?php
/**
 * Created by PhpStorm.
 * User: Hardik
 * Date: 19-08-2018
 * Time: 06:56 AM
 */
    require_once 'lib/google-api-php-client-2.2.2/vendor/autoload.php';

    if(!session_id()){
        session_start();
    }

    class GoogleDrive{

        function __construct($sessionID)
        {
            $this->appName    = 'FB Album Downloader';
            $this->credPath   = 'tmp/'.$sessionID.'.json';
            //$this->secretPath =  __DIR__ . '\credentials.json';
            $this->scopes     = array(Google_Service_Drive::DRIVE);
        }

        public function getClient() {
            $client = new Google_Client();
            $client->setApplicationName($this->appName);
            $client->setScopes($this->scopes);
            //$client->setAuthConfig($this->secretPath);
            $client->setClientId('233613059117-klqg47t3p39lp13u2efp79f1b3qu5q06.apps.googleusercontent.com');
            $client->setClientSecret('rHRfDhHqnODTwZYQMY4PvV1O');
            $client->setAccessType('offline');
            $client->setRedirectUri('https://my-album-downloader.herokuapp.com/redirect.php');

            if (file_exists($this->credPath)) {
                $accessToken = json_decode(file_get_contents($this->credPath),true);
            }
            else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                header('Location: www.google.com');
                header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
            }

            $client->setAccessToken($accessToken);

            // Refresh the token if it's expired.
            if ($client->isAccessTokenExpired()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                file_put_contents($this->credPath, json_encode($client->getAccessToken()));
            }

            return $client;
        }


        public function uploadFileToFolder($name, $datafile, $folderId){
            $client = $this->getClient();
            $service = new Google_Service_Drive($client);
            $fileMetadata = new Google_Service_Drive_DriveFile(array(
                'name' => $name,
                'parents' => array($folderId)
            ));
            $content = file_get_contents($datafile);
            $file = $service->files->create($fileMetadata, array(
                    'data'       => $content,
                    'mimeType'   => mime_content_type($datafile),
                    'fields'     => 'id')
            );
            return $file;
        }

        public function createFolder($folderName){
            $client = $this->getClient();
            $service = new Google_Service_Drive($client);
            $fileMetadata = new Google_Service_Drive_DriveFile(array(
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder'
            ));

            $pageToken = NULL;
            do {
                try {
                    $files = $service->files->listFiles(array(
                        'mimeType' => 'application/vnd.google-apps.folder',
                        'pageToken' => $pageToken,
                    ));

                    $files = $files->getFiles();
                    foreach ($files as $key=>$file) {
                        if($file->name == $folderName)
                            return $file->id;
                    }
                    $pageToken = $files->pageToken;

                } catch (Exception $e) {
                    print "An error occurred: " . $e->getMessage();
                    $pageToken = NULL;
                }
            } while ($pageToken);

            $file = $service->files->create($fileMetadata, array(
                'fields' => 'id'));

            return $file->id;
        }

        public function createSubFolder($folderName, $parentfolderId){
            $client = $this->getClient();
            $service = new Google_Service_Drive($client);
            $fileMetadata = new Google_Service_Drive_DriveFile(array(
                'name' => $folderName,
                'parents' => array($parentfolderId),
                'mimeType' => 'application/vnd.google-apps.folder'
            ));

            $file = $service->files->create($fileMetadata, array(
                'fields' => 'id'));

            return $file->id;
        }


    }