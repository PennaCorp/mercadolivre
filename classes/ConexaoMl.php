<?php
  class ConexaoMl{
    var $ml_id;
    var $em_id;
    var $db_id;
    var $hash_cliente;
    var $user_id;
    var $access_token;
    var $refresh_token;
    var $expiration_time;
    function getMlId(){return $this->ml_id;}
    function getEmId(){return $this->$em_id;}
    function getDbId(){return $this->$db_id;}
    function getHashCliente(){return $this->$hash_cliente;}
    function getUserId(){return $this->$user_id;}
    function getAccessToken(){return $this->$access_token;}
    function getRefreshToken(){return $this->$refresh_token;}
    function getExpirationTime(){return $this->$expiration_time;}
    function setMlId($ml_id){$this->$ml_id = $ml_id;}
    function setEmId($em_id){$this->$em_id = $em_id;}
    function setDbId($db_id){$this->$db_id = $db_id;}
    function setHashCliente($hash_cliente){$this->$hash_cliente = $hash_cliente;}
    function setUserId($user_id){$this->$user_id = $user_id;}
    function setAccessToken($access_token){$this->$access_token = $access_token;}
    function setRefreshToken($refresh_token){$this->$refresh_token = $refresh_token;}
    function setExpirationTime($expiration_time){$this->$expiration_time = $expiration_time;}
    function __construct($hash_cliente){
      $this->setHashCliente($hash_cliente);
    }
    
  }
?>
