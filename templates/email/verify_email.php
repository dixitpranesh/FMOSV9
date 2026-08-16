<?php
/** @var string $appName */
/** @var string $appUrl */
/** @var string $name */
/** @var string $verifyUrl */
/** @var string $supportEmail */
?>
<!DOCTYPE html>
<html><body style="font-family:Segoe UI,Arial,sans-serif;line-height:1.5;color:#222">
  <p>Welcome to <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?>.</p>
  <p>Hi <?= htmlspecialchars($name ?: 'there', ENT_QUOTES, 'UTF-8') ?>,</p>
  <p>Please verify your email address to activate your account:</p>
  <p><a href="<?= htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8') ?>">Verify Email</a></p>
  <p>This verification link expires after the configured validity period.</p>
  <p>If you did not create this account, please ignore this email.</p>
  <p class="muted" style="color:#666;font-size:12px">
    Need help? <a href="mailto:<?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?></a><br>
    <?= htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') ?>
  </p>
</body></html>
