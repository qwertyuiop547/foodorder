<?php
require_once __DIR__ . '/../includes/helpers.php';

$flash = getFlash();
if($flash):
    $flashType = in_array($flash['type'], ['success', 'error', 'info'], true) ? $flash['type'] : 'info';
?>

<div class="error-box">
    <div
        class="flash-message flash-<?= htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8') ?>"
        data-flash-message
        role="<?= $flashType === 'error' ? 'alert' : 'status' ?>"
        aria-live="polite"
    >
        <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const flash = document.querySelector('[data-flash-message]');
    if (!flash) {
        return;
    }

    window.setTimeout(function () {
        flash.classList.add('flash-hide');

        window.setTimeout(function () {
            const wrapper = flash.closest('.error-box');
            if (wrapper) {
                wrapper.remove();
                return;
            }

            flash.remove();
        }, 250);
    }, 5000);
});
</script>
<?php endif; ?>



