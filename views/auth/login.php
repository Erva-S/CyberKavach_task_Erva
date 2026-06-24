<?php /** @var array|null $user */ ?>
<section class="auth-card">
    <div class="eyebrow">Secure sign in</div>
    <h1>Welcome back</h1>
    <p>Use your institutional email and password to continue into CyberKavach.</p>

    <form class="field-grid" method="post" action="/login">
        <?= \CyberKavach\Core\View::csrfInput() ?>
        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" required>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <div class="form-actions">
            <button class="button button-primary" type="submit">Sign in</button>
            <a class="button button-secondary" href="/register">Create account</a>
        </div>
    </form>

    <p class="form-note">Registration requires OTP verification through the API flow.</p>
</section>
