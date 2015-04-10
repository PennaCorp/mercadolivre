<?php
	if (isset($_GET['cliente']) && preg_match("/^[a-zA-Z0-9]{32}$/", $_GET['cliente'])){
		define("CLI_HASH", strtoupper($_GET['cliente']));
	}else{die();}
	define("CLI_LINK", "http://www.pennatec.com.br/teste/loginml.php?cliente=".CLI_HASH);
	if (isset($_GET['error'])){
		switch(strtolower($_GET['error'])){
			case "access-denied":
				die("O acesso foi negado. Clique no <a href='".CLI_LINK."'>link</a> para autorizá-lo novamente.");
			break;
		}
	}
	require 'mercadolivre_api/meli.php';
	$meli = new Meli('306099844324210', 'WIY47D0uMBYtfFl2rbkowdgD61BS75O6');
	include_once("bancoDadosInfo.php");
	$query = "select count(*) from mercadolivre where hash_cliente='".CLI_HASH."'";
	$con = mysqli_connect(CLIENT_DATABASE_SERVER, CLIENT_DATABASE_USERNAME, CLIENT_DATABASE_PASSWORD, CLIENT_DATABASE)
					or die(mysqli_conn_error());
	$resultValidaHash = mysqli_query($con, $query) or die(mysqli_error($con));
	if (mysqli_num_rows($resultValidaHash) == 0){
		die("Token de acesso inválido");
	}
	$query = "select ml.user_id, ml.access_token, ml.refresh_token, ml.expiration_time,
										db.db_ip, db.db_user, db.db_password, db.db_name
						from mercadolivre ml, bancos db
						where db.em_id=ml.em_id
						and db.db_id=ml.db_id
						and ml.hash_cliente='".CLI_HASH."'";
	$result = mysqli_query($con, $query) or die(mysqli_error($con));
	if (mysqli_num_rows($result) == 0){
		die("Ocorreu um erro ao identificar o banco de dados da empresa.");
	}
	$rowMl = mysqli_fetch_array($result);
	if (($rowMl['user_id'] == null) || (isset($_GET['reloga'])) || (isset($_GET['code']))){
		if (isset($_GET['code'])){
			$user = $meli->authorize($_GET['code'], CLI_LINK);
			$parametros = $user["body"];
			$corpoReq = array(
									'grant_type' => urlencode("client_credentials"),
									'client_id' => urlencode($parametros["client_id"]),
									'client_secret' => urlencode($parametros["client_secret"])
							);
			$curl = curl_init();
			curl_setopt_array($curl, array(
																		CURLOPT_RETURNTRANSFER => 1,
																		CURLOPT_URL => "https://api.mercadolibre.com/oauth/token",
																		CURLOPT_POST => 1,
																		CURLOPT_POSTFIELDS => $corpoReq
																)
			);
			$resp = curl_exec($curl) or die(curl_error($curl));
			curl_close($curl);
			$resultadoMl = json_decode($resp);
			if (isset($resultadoMl->error)){
				die("Ocorreu um erro na autenticação com o mercado livre. Erro: ".$resultadoMl->message);
			}
			$resultadoMl->expires_in += time();
			mysqli_query($con, "START TRANSACTION");
			$query = "update mercadolivre set
									user_id='".$resultadoMl->user_id."',
									access_token='".$resultadoMl->access_token."',
									refresh_token='".$resultadoMl->refresh_token."',
									expiration_time='".$resultadoMl->expires_in."'
								where hash_cliente='".CLI_HASH."'";
			mysqli_query($con, $query) or die(mysqli_query($con, "ROLLBACK"));
			$queryHash = "select hash_cliente from mercadolivre
									where
									user_id='".$resultadoMl->user_id."'
									and hash_cliente <> ('".CLI_HASH."')";
			$resultHash = mysqli_query($con, $queryHash) or die(mysqli_error($con));
			if (mysqli_num_rows($resultHash) > 0){
				$hashes = "";
				while ($rowHash = mysqli_fetch_array($resultHash)){
					$hashes .= ",'".$rowHash['hash_cliente']."'";
				}
				$hashes = substr($hashes, 1);
				$query = "update mercadolivre set
										user_id=null,
										access_token=null,
										refresh_token=null,
										expiration_time=null
									where user_id='".$resultadoMl->user_id."'
									and hash_cliente in (".$hashes.")";
				mysqli_query($con, $query) or die(mysqli_query($con, "ROLLBACK"));
			}
			mysqli_query($con, "COMMIT");
			echo "OK!";
		}else{
			die(header("Location: " . $meli->getAuthUrl(CLI_LINK)));
		}
	}else{
		if ($rowMl["user_id"] == "" || $rowMl['access_token'] == "" || $rowMl['refresh_token'] == ""){
			die("O acesso foi negado. Clique no <a href='".CLI_LINK."&reloga=true"."'>link</a> para autorizá-lo novamente.");
		}
		if ($rowMl["expiration_time"] < time()){
			$corpoReq = array(
										'grant_type' => urlencode("refresh_token"),
										'client_id' => urlencode("306099844324210"),
										'client_secret' => urlencode("WIY47D0uMBYtfFl2rbkowdgD61BS75O6"),
										'refresh_token' => urlencode($rowMl["refresh_token"])
									);
			$curl = curl_init();
			curl_setopt_array($curl, array(
																		CURLOPT_RETURNTRANSFER => 1,
																		CURLOPT_URL => "https://api.mercadolibre.com/oauth/token",
																		CURLOPT_POST => 1,
																		CURLOPT_POSTFIELDS => $corpoReq
																)
			);
			$resp = curl_exec($curl) or die(curl_error($curl));
			curl_close($curl);
			$resultadoMl = json_decode($resp);
			if (isset($resultadoMl->error)){
				die("Ocorreu um erro na autenticação com o mercado livre. Erro: ".$resultadoMl->message);
			}
			$resultadoMl->expires_in += time();
			$query = "update mercadolivre set
									user_id='".$resultadoMl->user_id."',
									access_token='".$resultadoMl->access_token."',
									refresh_token='".$resultadoMl->refresh_token."',
									expiration_time='".$resultadoMl->expires_in."'
								where hash_cliente='".CLI_HASH."'";
			mysqli_query($con, $query) or die(mysqli_error($con));
			echo "Token revalidado";
		}else{
			echo "Token OK";
		}
	}
	mysqli_close($con);
?>
