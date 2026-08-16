<?php
/** @var string $appName */
/** @var string $appUrl */
/** @var string $name */
/** @var string $resetUrl */
/** @var string $supportEmail */
?>
<!DOCTYPE html>
<html><body style="font-family:Segoe UI,Arial,sans-serif;line-height:1.5;color:#222">
  <p>Reset your <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> password</p>
  <p>Hi <?= htmlspecialchars($name ?: 'there', ENT_QUOTES, 'UTF-8') ?>,</p>
  <p>We received a request to reset your password.</p>
  <p><a href="<?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') ?>">Reset Password</a></p>
  <p>If you did not request this, you can safely ignore this email.</p>
  <p class="muted" style="color:#666;font-size:12px">
    Need help? <a href="mailto:<?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?></a><br>
    <?= htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') ?>
  </p>
</body></html>
