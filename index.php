<?php

require 'vendor/autoload.php';

use Google\Client;

$client = new Client();
$client->setAuthConfig(__DIR__.'/client_secret.json'); // Assurez-vous que votre chemin soit correct
$client->addScope('https://www.googleapis.com/auth/business.manage');
$client->setRedirectUri('http://localhost');

// Vérifier si un jeton d'accès est déjà présent dans le cookie ok
if (isset($_COOKIE["access_token"])) {
    $accessToken = $_COOKIE["access_token"];
    try {
        get_avis_google($accessToken);
    } catch (Exception $e) {
        echo 'Erreur : ' . $e->getMessage();
    }

} else {
    if (isset($_GET['code'])) {
        $accessToken = $client->fetchAccessTokenWithAuthCode($_GET['code']);

        if (isset($accessToken['access_token'])) {
            $time = $accessToken["expires_in"];
            setcookie("access_token", $accessToken["access_token"], time() + $time, "/");
            header('Location: /'); 
            exit;

        } else {
            echo 'Erreur lors de la récupération du jeton d\'accès.';
        }

    } else {
        $authUrl = $client->createAuthUrl();
        echo "<a href='$authUrl'>Connecter à Google My Business</a>";
    }
}

function get_avis_google($key){
	// $review = recuperer_url('https://maps.googleapis.com/maps/api/place/details/json?reference=ChIJyUyQiqO4rhIRLJI2E7l2zGo&key='.$key);
	// $review_decode = json_decode($review["page"]);
	// return $review_decode->result->reviews;
	$url = "https://mybusiness.googleapis.com/v4/accounts/108278300493587713841/locations/17310033391058014922/reviews?access_token=".$key;
	$review = file_get_contents($url);
	$review_decode = json_decode($review,true);
	$result = $review_decode["reviews"];
	
	$status = false;
	while($status != "ok"){
		if(isset($review_decode["nextPageToken"])){
			$review_decode = next_page_avis_google($url, $review_decode["nextPageToken"]);
			$result = array_merge($result, $review_decode["reviews"]);
		}else{
			$status = "ok";
		}
	}
	return find_data_utils($result);
}
function next_page_avis_google($url, $page_token){
	$review = file_get_contents($url."&pageToken=".$page_token);
	return json_decode($review,true);
}

function find_data_utils($data){
    //id -> reviewId (unique)
    //etoile -> starRating
    //profil -> reviewer["profilePhotoUrl"]
    //name -> reviewer["displayName"]
    //commentaire -> comment
    $_data = [];
    foreach($data as $value){
        array_push($_data,['id' => $value["reviewId"],
                        'profil' => $value["reviewer"]["profilePhotoUrl"],
                        'name' => $value["reviewer"]["displayName"],
                        'etoile' => $value["starRating"],
                        'commentaire' => $value["comment"]]);
    }
    echo "<pre>";
    var_dump($_data);
    echo "</pre>";
}