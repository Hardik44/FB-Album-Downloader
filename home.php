<?php
/**
 * Created by PhpStorm.
 * User: Hardik
 * Date: 12-08-2018
 * Time: 04:20 AM
 */

    require_once 'config.php';
    require_once 'lib/Facebook/Facebook.php';

    use Facebook\Exceptions\FacebookResponseException;
    use Facebook\Exceptions\FacebookSDKException;

    ini_set ( 'max_execution_time', 0);

    if(isset($accessToken)){
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
            $response = $FB->get('me?fields=albums{name,photos{images},picture{url}}');

        } catch(FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            session_destroy();
            header("https://my-album-downloader.herokuapp.com/home.php");
            exit;
        } catch(FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }
        $graphDecoded = $response->getDecodedBody();
        $graphNode = $response->getGraphNode();
        $json = $graphNode->asJson();
        $data = json_decode($json);

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

        //echo '<pre>';
        //var_dump($data);
        //echo '</pre>';

        $data = $data->albums;

        $albums = '';
        $albums .= '<a class="album-btn-right" href="link.php" id="dLink">Download Link</a>';
        $albums .= '<form action="download.php" method="get" id="theForm">';

        $albums .= '<div class="d-flex justify-content-center topbtn">';
        $albums .= '<input class="btn btn-primary" type="submit" name="download_all" value="Download All">';
        $albums .= '<input class="btn btn-primary" type="submit" name="download_all_drive" value="Move All to Drive">';
        $albums .= '<input class="btn btn-primary" type="submit" name="selected" value="Download selected">';
        $albums .= '<input class="btn btn-primary" type="submit" name="selected_drive" value="Move selected to Drive">';
        $albums .= '</div>';

        $albums .= '<div class="row">';
        foreach ($data as $key=>$value){
            $albumName= $value->name;
            $id = $value->id;
            $albums .= '<div class="responsive">';
            $albums .= '<div class="gallery">';
            $albums .= '<div class="cover">';

            $albums .= "<a href='home.php?func=".$albumName."'>";
            if(isset($value->photos[0]->images[0]->source))
                $albums .= '<img src="' . $value->photos[0]->images[0]->source . '" >';
            else{
                $albums .= '<img src="img/no-image.jpg" >';
            }
            $albums .= '<div class="overlay">';
            $albums .= '<div class="text">'.$albumName.'</div>';
            $albums .= '</div>';
            $albums .= '</div>';
            $albums .= '</a>';

            $albums .= '<div class="checkbox">';
            $albums .= '<input type="checkbox" name="'.$albumName.'">';
            $albums .= '</div>';
            $albums .= '<button type="submit" class="btn album-btn-left" name="'.$albumName.'"><i class="fa fa-download" aria-hidden="true"></i></button>';
            $albums .= '<button type="submit" class="btn album-btn-right" name="'.$albumName.'_drive"><img src="img/Drive_Icon_Monochromatic512.png" style="width: 18px; height: 18px"></button>';

            $albums .= '<div class="desc">'.$albumName.'</div>';
            $albums .= '</div>';
            $albums .= '</div>';
        }
        $albums .= '</div>';
        $albums .= '</form>';
        $albums .= '<div class="clearfix"></div>';

    }else{
        header('https://my-album-downloader.herokuapp.com/index.php');
    }

    $slide_show = "";

    if(isset($_GET['func'])){
        $albumName = $_GET['func'];

        foreach ($data as $key => $value){
            $albumname = $value -> name;

            if($albumname == $albumName){
                $photos = $value -> photos;
                $slide = count($photos);
                $slide_show .= '<div id="myModal" class="modal">';
                $slide_show .= '<span class="close cursor" onclick="closeModal();" id = "closeSlideShow">&times;</span>';
                $slide_show .= '<div class="modal-content">';

                $i = 1;
                foreach ($photos as $key => $value){
                    $imageURL = $value -> images[0] -> source;
                    $slide_show .= '<div class="mySlides fade">';
                    $slide_show .= '<div class="numbertext">'.$i.'/'.$slide.'</div>';
                    $slide_show .= '<img src="'.$imageURL.'" style="width:100%; max-height:600px">';
                    $slide_show .= '</div>';
                    $i++;
                }

                $slide_show .= '<a class="prev" onclick="plusSlides(-1);">&#10094;</a>';
                $slide_show .= '<a class="next" onclick="plusSlides(1);">&#10095;</a>';
                $slide_show .= '</div>';
                $slide_show .= '</div>';
                $slide_show .= '<script>openModal();currentSlide(1)</script>';

            }
        }
    }



?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Albums home</title>
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        <link rel="stylesheet" href="index.css" type="text/css">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
        <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

        <script src="js/slidShow.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

    </head>

    <body>
        <div class="title">
            <h1 style="color: #2e2e7f">Welcome to your facebook albums!!</h1>
        </div>

        <div class="progress" id="progress"></div>
        <div id="message" style="color: #2e2e7f; text-align: center"></div>

        <div>
            <?php echo $albums ?>
            <?php echo $slide_show?>
        </div>
            <script>
                function refreshProgress() {
                    $.ajax({
                        url: "checker.php?file=<?php echo session_id() ?>",
                        success:function(data){
                            $("#progress").html('<div class="progress-bar progress-bar-striped active" style="width:' + data.percent+ '%"  role="progressbar"\n' +
                                '  aria-valuenow="' + data.percent+ '" aria-valuemin="0" aria-valuemax="100"></div>');
                            $("#message").html(data.message);

                            if (data.percent == 100) {
                                window.clearInterval(timer);
                                timer = window.setInterval(completed, 1000);
                            }
                        }
                    });
                }

                function showBox() {
                    if (confirm("Click OK to start download.....")) {
                        window.location = "link.php";
                        $('#progress').hide();
                        $("#message").html("Your Zip file downloaded successfully...");
                    }else{
                        $("#message").html("Your have canceled downloading...");
                    }
                    window.clearInterval(timer);
                }

                function completed() {
                    $("#message").html("Zipping Completed");
                    window.clearInterval(timer);
                    //timer = window.setInterval(showBox,1000);
                }

                $(document).ready(function () {
                    $('#progress').hide();

                    $( "#theForm" ).submit(function( event ) {
                        $('#progress').show();
                        timer = window.setInterval(refreshProgress, 1000);
                    });

                    $('#closeSlideShow').click(function () {
                        window.location = "home.php";
                    })
                });

            </script>
    </body>
</html>
