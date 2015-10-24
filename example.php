<?php
  require_once(doAPI.php);
  $do = new digitalOcean('yourAPIkeyforDigitalOcean');
    if(!$do->sizes()){
        print_r( $do->getError());
    }else{
        print_r( $do->getResult() );
    }
?>
