<?php
 
 function onChoosePlanHook() {
   echo "latitude_checkout_order called!";
   //wp_send_json_success([ "latitude_checkout_order called!"]); 
   wp_die(); 
 }
?>