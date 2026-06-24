<?php /** @var array|null $old */ ?>
<section class="auth-card">
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
    <?php endif; ?>

    <h1>Create category</h1>
    <form class="field-grid" method="post" action="/admin/categories/create">
        <?= \CyberKavach\Core\View::csrfInput() ?>
        <div class="field">
            <label for="name">Name</label>
            <input id="name" name="name" type="text" required value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label for="slug">Slug (optional)</label>
            <input id="slug" name="slug" type="text" value="<?= htmlspecialchars($old['slug'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label for="color">Color</label>
            <input id="color" name="color" type="text" value="<?= htmlspecialchars($old['color'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label for="icon">Icon (optional)</label>
            <input id="icon" name="icon" type="text" value="<?= htmlspecialchars($old['icon'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </div>
        <div class="form-actions">
            <button class="button button-primary" type="submit">Create</button>
            <a class="button button-secondary" href="/admin/categories">Cancel</a>
        </div>
    </form>
</section>
