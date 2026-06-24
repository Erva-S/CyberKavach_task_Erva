<header class="topbar">
    <div class="topbar-inner page-shell">
        <a class="brand" href="/" aria-label="CyberKavach home">
            <span class="brand-mark">CK</span>
            <span>CyberKavach</span>
        </a>
        <nav class="nav" aria-label="Primary">
            <a href="#platform">Platform</a>
            <a href="#workflow">Workflow</a>
            <a href="#modules">Modules</a>
            <a class="button button-secondary" href="/login">Sign in</a>
        </nav>
    </div>
</header>

<section class="hero">
    <div>
        <div class="eyebrow">University club operations, rebuilt as auditable software</div>
        <h1><?= htmlspecialchars($headline ?? 'CyberKavach', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars($lede ?? 'A production-grade command center for events, approvals, certificates, attendance, and club governance.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <div class="hero-actions">
            <a class="button button-primary" href="/register">Create account</a>
            <a class="button button-secondary" href="#workflow">See workflow</a>
        </div>
    </div>

    <aside class="hero-panel" aria-label="Platform metrics">
        <div class="metric-grid">
            <div class="metric">
                <span>Members</span>
                <strong>128</strong>
            </div>
            <div class="metric">
                <span>Events</span>
                <strong>24</strong>
            </div>
            <div class="metric">
                <span>Certs</span>
                <strong>1.2k</strong>
            </div>
            <div class="metric">
                <span>Approvals</span>
                <strong>47</strong>
            </div>
        </div>
        <div class="tag-list" aria-label="Key capabilities">
            <span class="tag">RBAC</span>
            <span class="tag">Audit logs</span>
            <span class="tag">OTP auth</span>
            <span class="tag">Certificates</span>
            <span class="tag">Attendance</span>
        </div>
    </aside>
</section>

<section class="section" id="platform">
    <div class="section-head">
        <div>
            <h2>Built like operational software</h2>
            <p>Each workflow is explicit, permissioned, and tracked. Nothing relies on informal handoffs or hidden state.</p>
        </div>
    </div>
    <div class="card-grid">
        <article class="card">
            <h3>Workflow first</h3>
            <p>Event creation, approval, attendance, and certificate generation all follow accountable state transitions.</p>
        </article>
        <article class="card">
            <h3>Permissions as data</h3>
            <p>Roles and permissions live in the database so access can evolve without code changes.</p>
        </article>
        <article class="card">
            <h3>Auditable by default</h3>
            <p>Every write path is ready to leave a trail with user, time, IP, and before/after data.</p>
        </article>
    </div>
</section>

<section class="section" id="workflow">
    <div class="section-head">
        <div>
            <h2>Approval pipeline</h2>
            <p>A clean sequence from request to review to completion, built to fit the club’s real hierarchy.</p>
        </div>
    </div>
    <div class="workflow">
        <div class="workflow-step">
            <strong>01</strong>
            <div>
                <h3>Request created</h3>
                <p>Members and coordinators submit events, posts, certificates, or other operational requests.</p>
            </div>
        </div>
        <div class="workflow-step">
            <strong>02</strong>
            <div>
                <h3>Role-based review</h3>
                <p>Approvals move through the proper coordinator levels with SLA tracking and escalation support.</p>
            </div>
        </div>
        <div class="workflow-step">
            <strong>03</strong>
            <div>
                <h3>Action and audit</h3>
                <p>Approved actions trigger the downstream workflow and are logged for institutional memory.</p>
            </div>
        </div>
    </div>
</section>

<section class="section" id="modules">
    <div class="section-head">
        <div>
            <h2>Core modules in the backlog</h2>
            <p>The structure already anticipates events, teams, registrations, notifications, content, certificates, and rewards.</p>
        </div>
    </div>
    <div class="card-grid">
        <article class="card">
            <h3>Events and teams</h3>
            <p>Create, approve, and run club events with registration, waitlists, and team management.</p>
        </article>
        <article class="card">
            <h3>Certificates and attendance</h3>
            <p>Generate verifiable certificates and connect them to attendance and event data.</p>
        </article>
        <article class="card">
            <h3>Rewards and content</h3>
            <p>Track points, badges, posts, and campaign approvals through the same platform backbone.</p>
        </article>
    </div>
</section>

<footer class="footer">
    <div class="footer-inner page-shell">
        <span>CyberKavach Smart Club Management System</span>
        <span>Production-grade scaffold in progress</span>
    </div>
</footer>