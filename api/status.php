<?php
include_once(dirname(__FILE__) . '/../include/bootstrap.php');
$token = canva_connector_require_token();

canva_connector_json(array(
  'connected' => true,
  'piwigoBaseUrl' => canva_connector_base_url(),
  'tokenLabel' => $token['label'] ?? 'Canva',
));
