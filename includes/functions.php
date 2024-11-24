<?php
// includes/functions.php

function generateSlug($string) {
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    $slug = preg_replace('/[^a-zA-Z0-9 -]/', '', $slug);
    $slug = strtolower(trim($slug));
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    return $slug;
}
?>
