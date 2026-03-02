<div class="mb-4 text-center px-3">
    <a href="dashboard" class="d-block text-decoration-none">
        <img src="<?php echo htmlspecialchars($app_logo); ?>" alt="Admin Logo" class="img-fluid rounded-circle bg-white p-1 shadow-sm" style="max-height: 60px;">
        <div class="mt-2">
            <span class="text-info fw-bold tracking-wider small uppercase">VOTE-ADMIN</span>
        </div>
    </a>
</div>

<ul class="nav nav-pills flex-column mb-auto text-white" id="sidebarMenu">
    <li class="nav-item mb-1">
        <a href="dashboard" class="nav-link text-white active">
            <i class="fas fa-home me-2"></i> Dashboard
        </a>
    </li>

    <li class="nav-item mb-1">
        <a href="#electionSubmenu" 
           data-bs-toggle="collapse" 
           role="button" 
           aria-expanded="false" 
           aria-controls="electionSubmenu" 
           class="nav-link text-white d-flex justify-content-between align-items-center">
            <span><i class="fas fa-vote-yea me-2"></i> Elections</span>
            <i class="fas fa-chevron-down small opacity-50"></i>
        </a>
        <div class="collapse" id="electionSubmenu" data-bs-parent="#sidebarMenu">
            <ul class="nav flex-column ms-4 small border-start border-secondary border-opacity-25 mt-1">
                <li class="nav-item">
                    <a href="elections/create" class="nav-link text-white-50 hover:text-white py-1">
                        <i class="fas fa-plus-circle me-2 text-xs"></i> Create New
                    </a>
                </li>
                <li class="nav-item">
                    <a href="elections/view" class="nav-link text-white-50 hover:text-white py-1">
                        <i class="fas fa-list me-2 text-xs"></i> View All
                    </a>
                </li>
                <li class="nav-item">
                    <a href="elections/archived" class="nav-link text-white-50 hover:text-white py-1">
                        <i class="fas fa-archive me-2 text-xs"></i> Archived
                    </a>
                </li>
            </ul>
        </div>
    </li>

    <li class="nav-item mb-1">
        <a href="voters" class="nav-link text-white">
            <i class="fas fa-users-cog me-2"></i> Manage Voters
        </a>
    </li>

    <li class="nav-item mb-1">
        <a href="candidates" class="nav-link text-white">
            <i class="fas fa-user-tie me-2"></i> Candidates
        </a>
    </li>

    <li class="nav-item mb-1">
        <a href="results" class="nav-link text-white">
            <i class="fas fa-chart-bar me-2"></i> Live Results
        </a>
    </li>
</ul>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Sidebar Logic
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const btn = document.getElementById('sidebarCollapse');

    btn.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    });

    overlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    });
    </script>
