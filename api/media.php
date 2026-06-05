<?php
include_once(dirname(__FILE__) . '/../include/bootstrap.php');
canva_connector_require_token();

$photo_id = isset($_GET['photoId']) ? (int) $_GET['photoId'] : 0;
if ($photo_id <= 0) {
  canva_connector_json(array('error' => 'photoId is required'), 400);
}

$query = '
SELECT id, file, path
FROM ' . IMAGES_TABLE . '
WHERE id = ' . $photo_id . '
LIMIT 1
';
$row = pwg_db_fetch_assoc(pwg_query($query));
if (!$row) {
  canva_connector_json(array('error' => 'Photo not found'), 404);
}

$path = PHPWG_ROOT_PATH . ltrim((string) $row['path'], './');
if (!is_file($path)) {
  canva_connector_json(array('error' => 'Photo file not found'), 404);
}

$mime_type = preg_match('/\.png$/i', (string) $row['file']) ? 'image/png' : 'image/jpeg';
canva_connector_clean_output_buffer();
header('Content-Type: ' . $mime_type);
header('Cache-Control: private, max-age=300');
readfile($path);
exit;
