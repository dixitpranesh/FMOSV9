<?php
/** @var string $appName */
/** @var string $appUrl */
/** @var string $name */
/** @var string $supportEmail */
?>
<!DOCTYPE html>
<html><body style="font-family:Segoe UI,Arial,sans-serif;line-height:1.5;color:#222">
  <p>Welcome to <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?>.</p>
  <p>Hi <?= htmlspecialchars($name ?: 'there', ENT_QUOTES, 'UTF-8') ?>, your email is verified and your account is active.</p>
  <p><a href="<?= htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') ?>">Sign in</a></p>
  <p class="muted" style="color:#666;font-size:12px">
    Questions? <a href="mailto:<?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?></a>
  </p>
</body></html>
