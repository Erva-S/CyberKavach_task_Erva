<?php /** @var array|null $user */ ?>
<section class="dashboard-card">
    <div class="eyebrow">Dashboard</div>
    <h1>Hi<?= isset($user['name']) ? ', ' . htmlspecialchars($user['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '' ?>.</h1>
    <p>Use this space for your role-specific workspace, approvals, events, and communications.</p>

    <div class="dashboard-grid">
        <div class="stat">
            <span>Current role</span>
            <strong><?= htmlspecialchars($role ?? 'Member', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
        </div>
        <div class="stat">
            <span>Account</span>
            <strong><?= htmlspecialchars($user['email'] ?? 'Signed in', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
        </div>
        <div class="stat">
            <span>Focus</span>
            <strong>Operations</strong>
        </div>
    </div>

    <div class="section" style="padding-bottom:0;">
        <div class="section-head">
            <div>
                <h2>Next actions</h2>
                <p>As the build grows, this dashboard will surface module shortcuts and live work queues.</p>
            </div>
        </div>
        <div class="stack">
            <div class="stack-row">
                <div>
                    <strong>Approvals</strong>
                    <div class="muted">Review pending requests and SLA escalations.</div>
                </div>
                <span class="tag">Soon</span>
            </div>
            <div class="stack-row">
                <div>
                    <strong>Events</strong>
                    <div class="muted">Manage drafts, publishing, and registrations.</div>
                </div>
                <span class="tag">Soon</span>
            </div>
            <div class="stack-row">
                <div>
                    <strong>Certificates</strong>
                    <div class="muted">Map fields and generate verification-safe outputs.</div>
                </div>
                <span class="tag">Soon</span>
            </div>
        </div>
    </div>
</section>
