<?php
// page_template.php
ob_start();
?>
<h1><?php echo htmlspecialchars($page['title']); ?></h1>
<div>
    <?php echo nl2br(htmlspecialchars($page['content'])); ?>
</div>
<?php
$content = ob_get_clean();
$pageTitle = $page['title'] ?? 'Strona';
include 'main_template.php';
?>
