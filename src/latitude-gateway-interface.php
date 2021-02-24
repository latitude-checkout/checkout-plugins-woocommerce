<?php

Interface Latitude_Gateway_Interface { 
    public function payment_request_callback($parameters) : bool; 
}