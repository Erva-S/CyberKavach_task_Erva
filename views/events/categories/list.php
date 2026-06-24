<?php /** @var array $categories */ ?>
<section class="section">
    <div class="section-head">
        <div>
            <h2>Event categories</h2>
            <p class="muted">Create and manage event categories used when creating events.</p>
        </div>
        <div>
            <a class="button button-primary" href="#create-category" id="open-create">Create category</a>
        </div>
    </div>

    <div class="card-grid">
        <div id="create-category" style="margin-bottom:16px;">
            <form id="category-create-form" class="field-grid" action="/api/categories" method="post">
                <?= \CyberKavach\Core\View::csrfInput() ?>
                <div class="field">
                    <label for="cat-name">Name</label>
                    <input id="cat-name" name="name" type="text" required>
                </div>
                <div class="field">
                    <label for="cat-slug">Slug (optional)</label>
                    <input id="cat-slug" name="slug" type="text">
                </div>
                <div class="form-actions">
                    <button class="button button-primary" type="submit">Create</button>
                </div>
            </form>
        </div>
        <div id="categories-app" data-list-url="/api/categories" data-create-url="/api/categories" data-update-url="/api/categories/{id}/update" data-delete-url="/api/categories/{id}/delete">
            <!-- JS will render category cards here -->
        </div>
    </div>
    <script src="/assets/js/admin-categories.js"></script>
    <div id="category-edit-modal" class="modal" style="display:none;">
        <div class="modal-content">
            <h3>Edit category</h3>
            <form id="category-edit-form" class="field-grid">
                <?= \CyberKavach\Core\View::csrfInput() ?>
                <input type="hidden" name="id" id="edit-id" />
                <div class="field">
                    <label for="edit-name">Name</label>
                    <input id="edit-name" name="name" type="text" required>
                </div>
                <div class="field">
                    <label for="edit-slug">Slug</label>
                    <input id="edit-slug" name="slug" type="text">
                </div>
                <div class="field">
                    <label for="edit-color">Color</label>
                    <input id="edit-color" name="color" type="text">
                </div>
                <div class="form-actions">
                    <button class="button button-primary" type="submit">Save</button>
                    <button class="button button-secondary" type="button" id="edit-cancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</section>
