<?php
// Plugin loader
function loadPlugin($slug) {
    $pluginDir = __DIR__ . "/$slug/plugin.php";

    if (file_exists($pluginDir)) {
        include_once $pluginDir;
    } else {
        echo "Plugin $slug nie został znaleziony.";
    }
}
