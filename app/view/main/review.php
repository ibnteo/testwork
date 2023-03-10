<?php foreach ($reviews as $row): ?>
<div class="card mb-3">
    <div class="card-header text-bg-info"><h4 class="mb-0"><?php echo htmlspecialchars($row['name'] ?? '' ?: 'Аноним'); ?></h4></div>
    <div class="card-body"><?php echo nl2br(htmlspecialchars($row['message'] ?? '' ?: 'Без отзыва')); ?></div>
    <div class="card-footer">Дата: <em><?php echo htmlspecialchars(date('d.m.Y H:i:s', strtotime($row['date']))); ?></em></div>
</div>
<?php endforeach; ?>
