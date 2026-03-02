<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const mobileToggle = document.getElementById('mobile-toggle');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    const navbar = document.getElementById('navbar');
    const searchInput = document.getElementById('searchInput');
    const dataTable = document.getElementById('dataTable').getElementsByTagName('tbody')[0];

    // Sidebar mobile toggle
    mobileToggle.addEventListener('click', () => {
      if (window.innerWidth <= 992) {
        sidebar.classList.toggle('mobile-active');
      }
    });

    // Sidebar collapse toggle (desktop + mobile)
    sidebarToggle.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      content.classList.toggle('collapsed');
      navbar.classList.toggle('collapsed');
    });

    // Search filter
    searchInput.addEventListener('input', function() {
      let filter = searchInput.value.toLowerCase();
      let rows = dataTable.getElementsByTagName('tr');
      for (let i = 0; i < rows.length; i++) {
        let rowText = rows[i].textContent.toLowerCase();
        rows[i].style.display = rowText.includes(filter) ? '' : 'none';
      }
    });

    // Notification window toggle
    const notificationIcon = document.getElementById('notificationIcon');
    const notificationWindow = document.getElementById('notificationWindow');
    const closeNotification = document.getElementById('closeNotification');

    notificationIcon.addEventListener('click', () => {
      notificationWindow.style.display = notificationWindow.style.display === 'block' ? 'none' : 'block';
    });

    closeNotification.addEventListener('click', () => {
      notificationWindow.style.display = 'none';
    });
  </script>