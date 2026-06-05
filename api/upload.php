<?php
include_once(dirname(__FILE__) . '/../include/bootstrap.php');
canva_connector_require_token();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  canva_connector_json(array('error' => 'POST required'), 405);
}

$input = json_decode(file_get_contents('php://input') ?: '{}', true);
if (!is_array($input)) {
  canva_connector_json(array('error' => 'Invalid JSON body'), 400);
}

$export_url = (string) ($input['canvaExportUrl'] ?? '');
$album_id = (int) ($input['albumId'] ?? 0);
$filename = trim((string) ($input['filename'] ?? 'canva-export'));

if (!$export_url || $album_id <= 0) {
  canva_connector_json(array('error' => 'canvaExportUrl and albumId are required'), 400);
}

$url = parse_url($export_url);
$host = strtolower($url['host'] ?? '');
if (($url['scheme'] ?? '') !== 'https' || !preg_match('/(^|\.)canva\.com$/', $host)) {
  canva_connector_json(array('error' => 'Only HTTPS Canva export URLs are allowed'), 400);
}

$tmp = tempnam(sys_get_temp_dir(), 'canva-piwigo-');
if (!$tmp) {
  canva_connector_json(array('error' => 'Unable to create temporary file'), 500);
}

$context = stream_context_create(array(
  'http' => array(
    'timeout' => 60,
    'follow_location' => 0,
  ),
));

$bytes = @file_put_contents($tmp, fopen($export_url, 'rb', false, $context));
if ($bytes === false || $bytes <= 0) {
  @unlink($tmp);
  canva_connector_json(array('error' => 'Unable to download Canva export'), 400);
}

$info = @getimagesize($tmp);
if (!$info || !in_array($info['mime'] ?? '', array('image/jpeg', 'image/png'), true)) {
  @unlink($tmp);
  canva_connector_json(array('error' => 'Only JPG and PNG exports are supported'), 400);
}

$extension = ($info['mime'] === 'image/png') ? 'png' : 'jpg';
$safe_name = preg_replace('/[^\w.\- ]+/', '', $filename) ?: 'canva-export';
$safe_name = preg_replace('/\.(jpg|jpeg|png)$/i', '', $safe_name) . '.' . $extension;

include_once(PHPWG_ROOT_PATH . 'admin/include/functions_upload.inc.php');
if (!function_exists('add_uploaded_file')) {
  @unlink($tmp);
  canva_connector_json(array('error' => 'Piwigo upload function is unavailable'), 500);
}

try {
  $image_id = add_uploaded_file($tmp, $safe_name, array($album_id), 0, null);
  @unlink($tmp);
  canva_connector_json(array('success' => true, 'imageId' => $image_id));
} catch (Throwable $error) {
  @unlink($tmp);
  canva_connector_json(array('error' => $error->getMessage()), 500);
}
