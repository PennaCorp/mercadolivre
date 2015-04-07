<?php
session_start('teste');

require 'MercadoLivre/meli.php';

$meli = new Meli('306099844324210', 'WIY47D0uMBYtfFl2rbkowdgD61BS75O6');
if (!isset($_GET['code'])){
	$link = $meli->getAuthUrl("http://www.pennatec.com.br/teste/loginMl.php");
	echo $link;
}else{
	$user = $meli->authorize($_GET['code'], 'http://www.pennatec.com.br/teste/loginMl.php');
	$parametros = $user["body"];
	//print_r($user);
	$fields = array(
							'grant_type' => urlencode("client_credentials"),
							'client_id' => urlencode($parametros["client_id"]),
							'client_secret' => urlencode($parametros["client_secret"])
					);
	$curl = curl_init();
	curl_setopt_array($curl, array(
																CURLOPT_RETURNTRANSFER => 1,
																CURLOPT_URL => "https://api.mercadolibre.com/oauth/token",
																CURLOPT_POST => 1,
																CURLOPT_POSTFIELDS => $fields
														)
	);
	echo "<pre>";
	var_dump($fields);
	echo "</pre>";
	$resp = curl_exec($curl);
	if (!$resp){
		die(curl_error($curl));
	}
	curl_close($curl);
	var_dump($resp);
}
