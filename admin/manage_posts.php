<?php
$stmt = $pdo->query("SELECT * FROM facebook_events ORDER BY start_time ASC LIMIT 5");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="plugin-facebook">
    <h2>Nadchodzące Wydarzenia</h2>
    <ul>
        <?php foreach ($events as $event): ?>
            <li>
                <strong><?php echo htmlspecialchars($event['name']); ?></strong><br>
                <?php echo $event['start_time']; ?> - <?php echo $event['end_time']; ?><br>
                <?php echo htmlspecialchars($event['location']); ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
