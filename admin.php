<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

include_once(dirname(__FILE__) . '/include/bootstrap.php');
canva_connector_require_admin();

$connect_url = get_root_url() . 'plugins/canva_connector/connect.php';
?>

<div class="titrePage">
  <h2>Canva Connector</h2>
</div>

<div class="canva-connector-admin">
  <p>
    Configure the connector token used by the Canva Piwigo Media app to access
    this Piwigo instance.
  </p>

  <p>
    Canva will not receive your Piwigo password or Piwigo API keys. The
    connector token can be revoked at any time.
  </p>

  <p>
    <a class="button" href="<?php echo htmlspecialchars($connect_url, ENT_QUOTES, 'UTF-8'); ?>">
      Manage Canva connector tokens
    </a>
  </p>
</div>
