<?php /** @var array $events */ ?>
<section class="section">
    <div class="section-head">
        <div>
            <h2>Events</h2>
            <p class="muted">Upcoming and recent events.</p>
        </div>
        <div>
            <a class="button button-primary" href="/events/create">Create event</a>
        </div>
    </div>

    <div class="card-grid">
        <?php foreach ($events as $e): ?>
            <div class="card">
                <h3><?= htmlspecialchars($e['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h3>
                <p class="muted">Status: <?= htmlspecialchars($e['status'] ?? 'draft', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> —
                <?= isset($e['event_start']) && $e['event_start'] ? date('M j, Y H:i', strtotime($e['event_start'])) : 'TBA' ?></p>
                <p style="margin-top:12px;"><a href="/events/<?= htmlspecialchars($e['ulid'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" class="button button-secondary">View</a></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>
