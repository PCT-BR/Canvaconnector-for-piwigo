<?php
include_once(dirname(__FILE__) . '/../include/bootstrap.php');
canva_connector_require_token();

$album_id = isset($_GET['albumId']) ? (int) $_GET['albumId'] : 0;
$current_page = max(1, isset($_GET['page']) ? (int) $_GET['page'] : 1);
$per_page = min(80, max(1, isset($_GET['perPage']) ? (int) $_GET['perPage'] : 24));

if ($album_id <= 0) {
  canva_connector_json(array('error' => 'albumId is required'), 400);
}

$offset = ($current_page - 1) * $per_page;

$count_query = '
SELECT COUNT(*) AS total
FROM ' . IMAGE_CATEGORY_TABLE . '
WHERE category_id = ' . $album_id . '
';
$count_row = pwg_db_fetch_assoc(pwg_query($count_query));
$total = (int) ($count_row['total'] ?? 0);

$query = '
SELECT
  i.id,
  i.file,
  i.name,
  i.comment,
  i.path
FROM ' . IMAGES_TABLE . ' i
INNER JOIN ' . IMAGE_CATEGORY_TABLE . ' ic ON ic.image_id = i.id
WHERE ic.category_id = ' . $album_id . '
ORDER BY i.date_available DESC, i.id DESC
LIMIT ' . $per_page . ' OFFSET ' . $offset . '
';

$result = pwg_query($query);
$photos = array();
$base_media_url = canva_connector_base_url() . '/plugins/canva_connector/api/media.php';

while ($row = pwg_db_fetch_assoc($result)) {
  $file = (string) ($row['file'] ?? '');
  $mime_type = preg_match('/\.png$/i', $file) ? 'image/png' : 'image/jpeg';
  $media_url = $base_media_url . '?photoId=' . (int) $row['id'];
  $width = 0;
  $height = 0;
  $path = PHPWG_ROOT_PATH . ltrim((string) ($row['path'] ?? ''), './');
  if (is_file($path)) {
    $size = @getimagesize($path);
    if (is_array($size)) {
      $width = (int) ($size[0] ?? 0);
      $height = (int) ($size[1] ?? 0);
      if (!empty($size['mime'])) {
        $mime_type = $size['mime'] === 'image/png' ? 'image/png' : 'image/jpeg';
      }
    }
  }

  $photos[] = array(
    'id' => (int) $row['id'],
    'title' => canva_connector_clean_text($row['name'] ?: $file),
    'filename' => $file,
    'width' => $width,
    'height' => $height,
    'mimeType' => $mime_type,
    'thumbUrl' => $media_url . '&variant=thumb',
    'previewUrl' => $media_url . '&variant=preview',
    'assetUrl' => $media_url . '&variant=full',
  );
}

canva_connector_json(array(
  'photos' => $photos,
  'total' => $total,
  'page' => $current_page,
  'perPage' => $per_page,
));
