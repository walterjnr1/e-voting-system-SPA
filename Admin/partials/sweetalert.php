     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

 <?php if (isset($_SESSION['toast'])): ?>
    <script>
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: '<?= $_SESSION['toast']['type'] ?>',
            title: '<?= $_SESSION['toast']['message'] ?>',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    </script>
    <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>

