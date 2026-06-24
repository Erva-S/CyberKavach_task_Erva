<?php /** @var array $event */ ?>
<section class="section">
    <div class="section-head">
        <div>
            <h2><?= htmlspecialchars($event['title'] ?? 'Event', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h2>
            <p class="muted">Event details</p>
        </div>
        <div>
            <a class="button button-secondary" href="/events">Back to events</a>
        </div>
    </div>

    <div class="card">
        <div class="muted">Status: <?= htmlspecialchars($event['status'] ?? 'draft', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
        <div style="margin-top:12px;"><?= $event['description_html'] ?? '' ?></div>
    </div>
</section>
