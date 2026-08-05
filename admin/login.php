<?php
require_once __DIR__ . '/header.php';
?>
<div class="min-h-screen flex items-center justify-center p-4" style="background: radial-gradient(ellipse at top, #1a2340 0%, #0a0f1c 70%);">
    <div class="card" style="width: 100%; max-width: 440px; padding: 2.5rem;">
        <div class="text-center mb-8">
            <div style="font-size: 3.5rem; margin-bottom: 1rem;">🏛️</div>
            <h1 style="font-size: 1.75rem; font-weight: 800; background: linear-gradient(135deg, #D4AF37 0%, #E8C96B 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
                OPCIEAS ADMIN
            </h1>
            <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem;">Sign in to access the administration portal</p>
        </div>

        <div id="loginAlert" class="alert alert-error" style="display: none;"></div>

        <form id="loginForm" onsubmit="event.preventDefault(); handleLogin();">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-input" placeholder="Enter your username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required autocomplete="current-password">
            </div>
            <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem;">
                <input type="checkbox" id="remember" name="remember" style="accent-color: #D4AF37; width: 16px; height: 16px;">
                <label for="remember" style="font-size: 0.85rem; color: rgba(255,255,255,0.7); cursor: pointer;">Remember me for 30 days</label>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; padding: 0.875rem; font-size: 1rem; margin-top: 0.5rem;" id="loginBtn">
                <span id="loginBtnText">Sign In</span>
                <span id="loginBtnSpinner" style="display: none;">⏳</span>
            </button>
        </form>

        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid rgba(212,175,55,0.15); text-align: center;">
            <p style="font-size: 0.75rem; color: rgba(255,255,255,0.4);">
                &copy; <?php echo date('Y'); ?> OPCIEAS. Admin Portal v1.0
            </p>
        </div>
    </div>
</div>

<script>
async function handleLogin() {
    const alertEl = document.getElementById('loginAlert');
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    const remember = document.getElementById('remember').checked;

    if (!username || !password) {
        alertEl.textContent = 'Please enter both username and password.';
        alertEl.style.display = 'block';
        return;
    }
    alertEl.style.display = 'none';

    const btn = document.getElementById('loginBtn');
    const btnText = document.getElementById('loginBtnText');
    const btnSpinner = document.getElementById('loginBtnSpinner');
    btn.disabled = true;
    btnText.textContent = 'Signing in...';
    btnSpinner.style.display = 'inline';

    const result = await apiPost('/auth/login.php', { username, password, remember });

    btn.disabled = false;
    btnText.textContent = 'Sign In';
    btnSpinner.style.display = 'none';

    if (result.success) {
        alertEl.className = 'alert alert-success';
        alertEl.textContent = 'Login successful! Redirecting...';
        alertEl.style.display = 'block';
        setTimeout(() => window.location.href = 'index.php', 800);
    } else {
        alertEl.className = 'alert alert-error';
        alertEl.textContent = result.message || 'Login failed. Please try again.';
        alertEl.style.display = 'block';
    }
}

document.getElementById('password').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') handleLogin();
});
</script>
<?php require_once __DIR__ . '/footer.php'; ?>
