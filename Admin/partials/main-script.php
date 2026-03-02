<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Sidebar toggle for mobile
  const menuToggle = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  const sidebarClose = document.getElementById('sidebarClose');
  menuToggle.addEventListener('click', () => { sidebar.classList.add('active'); });
  sidebarClose.addEventListener('click', () => { sidebar.classList.remove('active'); });

  // Logo preview
  const logoInput = document.getElementById('logo');
  const logoPreview = document.getElementById('logoPreview');

  logoInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        logoPreview.setAttribute('src', e.target.result);
      }
      reader.readAsDataURL(file);
    }
  });
</script>

