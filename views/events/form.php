<?php /** simple create form */ ?>
<section class="auth-card">
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
    <?php endif; ?>
    <h1>Create event</h1>
    <form class="field-grid" method="post" action="/events/create">
        <?= \CyberKavach\Core\View::csrfInput() ?>
        <div class="field">
            <label for="title">Title</label>
            <input id="title" name="title" type="text" required value="<?= htmlspecialchars($old['title'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label for="slug">Slug (optional)</label>
            <input id="slug" name="slug" type="text" value="<?= htmlspecialchars($old['slug'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label for="category">Category</label>
            <select id="category" name="category_id">
                <option value="">-- choose --</option>
                <?php foreach (($categories ?? []) as $c): ?>
                    <option value="<?= htmlspecialchars($c['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" <?= (isset($old['category_id']) && (string)$old['category_id'] === (string)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="event_start">Start (local)</label>
            <input id="event_start" name="event_start" type="datetime-local" value="<?= htmlspecialchars($old['event_start'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label for="event_end">End (local)</label>
            <input id="event_end" name="event_end" type="datetime-local" value="<?= htmlspecialchars($old['event_end'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label for="capacity">Capacity</label>
            <input id="capacity" name="capacity" type="number" min="0" value="<?= htmlspecialchars($old['capacity'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label for="description">Description (HTML allowed)</label>
            <textarea id="description" name="description"><?= htmlspecialchars($old['description'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
        </div>
        <div class="form-actions">
            <button class="button button-primary" type="submit">Create</button>
            <a class="button button-secondary" href="/events">Cancel</a>
        </div>
    </form>
</section>
