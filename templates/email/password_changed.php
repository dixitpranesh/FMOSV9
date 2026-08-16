<?php
/** @var string $appName */
/** @var string $appUrl */
/** @var string $name */
/** @var string $supportEmail */
/** @var string $securityEmail */
?>
<!DOCTYPE html>
<html><body style="font-family:Segoe UI,Arial,sans-serif;line-height:1.5;color:#222">
  <p>Your <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> password was changed.</p>
  <p>Hi <?= htmlspecialchars($name ?: 'there', ENT_QUOTES, 'UTF-8') ?>,</p>
  <p>If you did not make this change, contact support immediately at
    <a href="mailto:<?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?></a>
    or report a security concern to
    <a href="mailto:<?= htmlspecialchars($securityEmail, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($securityEmail, ENT_QUOTES, 'UTF-8') ?></a>.
  </p>
  <p class="muted" style="color:#666;font-size:12px"><?= htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') ?></p>
</body></html>
