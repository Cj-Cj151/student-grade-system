    </main>
</div>

<script src="/public/js/app.js"></script>
<script>
// Confirm before logging out, so a misclick doesn't end the session.
document.getElementById('logoutLink')?.addEventListener('click', function (e) {
    e.preventDefault();
    const href = this.getAttribute('href');
    confirmAction('Are you sure you want to log out?', 'Log Out').then((ok) => {
        if (ok) window.location.href = href;
    });
});
</script>
<?php if (!empty($extraScript)): ?>
<script><?= $extraScript ?></script>
<?php endif; ?>
</body>
</html>
