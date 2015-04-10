<?php
  if (!isset($_REQUEST['produto'])){
    die("Informe o código do produto");
  }
  $produto = $_REQUEST['produto'];
  require 'mercadolivre_api/meli.php';
  $meli = new Meli('306099844324210', 'WIY47D0uMBYtfFl2rbkowdgD61BS75O6');
  include_once("bancoDadosInfo.php");
  $query = "select ml.user_id, ml.access_token, ml.refresh_token, ml.expiration_time,
                    db.db_ip, db.db_user, db.db_password, db.db_name
            from mercadolivre ml, bancos db
            where db.em_id=ml.em_id
            and db.db_id=ml.db_id
            and ml.hash_cliente='F8F19FB4A6F1F138060E1225265ABA85'";
  $con = mysqli_connect(CLIENT_DATABASE_SERVER, CLIENT_DATABASE_USERNAME, CLIENT_DATABASE_PASSWORD, CLIENT_DATABASE)
					or die(mysqli_conn_error());
  $result = mysqli_query($con, $query) or die(mysqli_error($con));
  mysqli_close($con);
  if (mysqli_num_rows($result) == 0){
    die("Ocorreu um erro ao identificar o banco de dados da empresa.");
  }
  $rowMl = mysqli_fetch_array($result);
  define("MLCLI_SERVER", $rowMl['db_ip']);
  define("MLCLI_USER", $rowMl['db_user']);
  define("MLCLI_PASSWORD", $rowMl['db_password']);
  define("MLCLI_DATABASE", $rowMl['db_name']);
  //preparando o produto
  $con = mysqli_connect(MLCLI_SERVER, MLCLI_USER, MLCLI_PASSWORD, MLCLI_DATABASE) or die(mysqli_conn_error());
  $queryProd = "select PT_CODE, PT_DESCR, PT_CUSTO from produto where pt_code='".$produto."'";
  $resultProd = mysqli_query($con, $queryProd) or die(mysqli_error($con));
  if (mysqli_num_rows($resultProd) == 0){
    die("Produto não encontrado");
  }
  $rowProd = mysqli_fetch_array($resultProd);
  $queryAplic = "select ap.AP_CODE, ap.AP_MARCA, pa.PA_CODEORIG
                  from produto_aplic pa, aplicacao ap
                  where ap.AP_NUMBER=pa.AP_NUMBER
                  and pa.pt_code='".$produto."'";
  $resultAplic = mysqli_query($con, $queryAplic) or die(mysqli_error($con));
  $aplicacoes = "";
  while ($rowAplic = mysqli_fetch_array($resultAplic)){
    $aplicacoes .= $rowAplic['AP_CODE']."-".$rowAplic['AP_MARCA']."-".$rowAplic['PA_CODEORIG'];
  }
  $curl = curl_init();
  $item = array(
              "title" => $rowProd["PT_DESCR"],
              "category_id" => "MLB3530",
              "price" => round(0, 2),
              "currency_id" => "BRL",
              "available_quantity" => 1,
              "buying_mode" => "buy_it_now",
              "listing_type_id" => "free",
              "condition" => "new",
              "description" => $aplicacoes,
              "video_id" => "",
              "warranty" => "3 meses",
            );
  $retorno = $meli->post("/items", $item, array('access_token' => $rowMl['access_token']));
  $objRetorno = $retorno["body"];
  if (isset($objRetorno->error)){
    die("Ocorreu um erro ao anunciar o produto. Erro: ".$objRetorno->message);
  }
  echo "Anuncio realizado com sucesso! Link: ".$objRetorno->permalink;
?>
