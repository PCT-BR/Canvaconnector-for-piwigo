<?php
include_once(dirname(__FILE__) . '/../include/bootstrap.php');
canva_connector_require_token();

$query = '
SELECT
  id,
  name,
  comment,
  id_uppercat,
  uppercats,
  status
FROM ' . CATEGORIES_TABLE . '
ORDER BY global_rank ASC, name ASC
';

$result = pwg_query($query);
$albums = array();

while ($row = pwg_db_fetch_assoc($result)) {
  $album_id = (int) $row['id'];
  $parent_id = null;
  if (!empty($row['id_uppercat'])) {
    $parent_id = (int) $row['id_uppercat'];
  } elseif (!empty($row['uppercats'])) {
    $parts = array_values(array_filter(array_map('intval', explode(',', $row['uppercats']))));
    if (count($parts) > 1) {
      $parent_id = $parts[count($parts) - 2];
    }
  }

  $direct_count_query = '
SELECT COUNT(DISTINCT image_id) AS total
FROM ' . IMAGE_CATEGORY_TABLE . '
WHERE category_id = ' . $album_id . '
';
  $direct_row = pwg_db_fetch_assoc(pwg_query($direct_count_query));
  $direct_images = (int) ($direct_row['total'] ?? 0);

  $total_count_query = '
SELECT COUNT(DISTINCT ic.image_id) AS total
FROM ' . IMAGE_CATEGORY_TABLE . ' ic
INNER JOIN ' . CATEGORIES_TABLE . ' c ON c.id = ic.category_id
WHERE c.id = ' . $album_id . '
   OR FIND_IN_SET(' . $album_id . ', c.uppercats)
';
  $total_row = pwg_db_fetch_assoc(pwg_query($total_count_query));
  $total_images = (int) ($total_row['total'] ?? $direct_images);

  $albums[] = array(
    'id' => $album_id,
    'name' => canva_connector_clean_text($row['name']),
    'comment' => canva_connector_clean_text($row['comment'] ?? ''),
    'parentId' => $parent_id,
    'directImages' => $direct_images,
    'totalImages' => $total_images,
    'isPrivate' => ($row['status'] ?? 'public') === 'private',
  );
}

canva_connector_json($albums);
