<?php
return array (
  'name' => 'Cashback',
  'img' => 'img/cashback.gif',
  'version' => '1.0.1',
  'vendor' => 'romaris',
  'handlers' => 
  array (
  	'order_action.create'	=> 'execute',
  	'frontend_cart'			=> 'cashbackCart'

  ),
  'frontend' => true,
);
