<?php
/** @var string $patosCreditVariant 'page' | 'modal' */
$patosCreditVariant = $patosCreditVariant ?? 'page';
?>
<div class="dc-patos-credit dc-patos-credit--<?= htmlspecialchars($patosCreditVariant, ENT_QUOTES, 'UTF-8') ?>" role="contentinfo">
    <span class="dc-patos-credit-text">made with &lt;3 by</span>
    <img src="assets/images/patos_logo.png" alt="PATOS" class="dc-patos-credit-logo" width="72" height="47" loading="lazy">
</div>
