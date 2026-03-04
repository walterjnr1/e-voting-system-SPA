<div class="mb-4 text-center px-3">
    <a href="dashboard" class="d-block text-decoration-none">
        <img src="../<?php echo htmlspecialchars($app_logo); ?>" alt="Admin Logo" class="img-fluid rounded-circle bg-white p-1 shadow-sm" style="max-height: 60px;">
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
        <a href="#accountSubmenu" 
           data-bs-toggle="collapse" 
           role="button" 
           aria-expanded="false" 
           aria-controls="accountSubmenu" 
           class="nav-link text-white d-flex justify-content-between align-items-center">
            <span><i class="fas fa-user-shield me-2"></i> Account</span>
            <i class="fas fa-chevron-down small opacity-50"></i>
        </a>
        <div class="collapse" id="accountSubmenu" data-bs-parent="#sidebarMenu">
            <ul class="nav flex-column ms-4 small border-start border-secondary border-opacity-25 mt-1">
                <li class="nav-item">
                    <a href="add-user" class="nav-link text-white-50 hover:text-white py-1">
                        <i class="fas fa-user-plus me-2 text-xs"></i> Add User
                    </a>
                </li>
                <li class="nav-item">
                    <a href="user-record" class="nav-link text-white-50 hover:text-white py-1">
                        <i class="fas fa-address-book me-2 text-xs"></i> User Records
                    </a>
                </li>
                <li class="nav-item">
                    <a href="change-password" class="nav-link text-white-50 hover:text-white py-1">
                        <i class="fas fa-lock text-xs"></i> Change Password
                    </a>
                </li>
            </ul>
        </div>
    </li>
    <li class="nav-item mb-1">
        <a href="#electionSubmenu" 
           data-bs-toggle="collapse" 
           role="button" 
           aria-expanded="false" 
           aria-controls="electionSubmenu" 
           class="nav-link text-white d-flex justify-content-between align-items-center">
            <span><i class="fas fa-vote-yea me-2"></i> Election Management</span>
            <i class="fas fa-chevron-down small opacity-50"></i>
        </a>
        <div class="collapse" id="electionSubmenu" data-bs-parent="#sidebarMenu">
            <ul class="nav flex-column ms-4 small border-start border-secondary border-opacity-25 mt-1">
                <li class="nav-item">
                    <a href="create_election" class="nav-link text-white-50 hover:text-white py-1">
                        <i class="fas fa-plus-circle me-2 text-xs"></i> Create New
                    </a>
                </li>
                <li class="nav-item">
                    <a href="election-record" class="nav-link text-white-50 hover:text-white py-1">
                        <i class="fas fa-list me-2 text-xs"></i> Election Record
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_positions" class="nav-link text-white-50 hover:text-white py-1">
                        <i class="fas fa-plus-circle me-2 text-xs"></i> manage Position
                    </a>
                </li>
            </ul>
        </div>
    </li>


<li class="nav-item mb-1">
        <a href="#voterSubmenu" 
           data-bs-toggle="collapse" 
           role="button" 
           aria-expanded="false" 
           aria-controls="voterSubmenu" 
           class="nav-link text-white d-flex justify-content-between align-items-center">
            <span><i class="fas fa-users-cog me-2"></i> Voters Management</span>
            <i class="fas fa-chevron-down small opacity-50"></i>
        </a>
        <div class="collapse" id="voterSubmenu" data-bs-parent="#sidebarMenu">
            <ul class="nav flex-column ms-4 small border-start border-secondary border-opacity-25 mt-1">
                <li class="nav-item">
                    <a href="voter_register" class="nav-link text-white-50 hover:text-white py-1">
                        <i class="fas fa-user-tie me-2"></i> Voter Register
                    </a>
                </li>
                <li class="nav-item">
                    <a href="voter_records" class="nav-link text-white-50 hover:text-white py-1">
                        <i class="fas fa-user-tie me-2"></i> Voter Records
                    </a>
                </li>
                
            </ul>
        </div>
    </li>
    

  <li class="nav-item mb-1">
    <a href="login-records" class="nav-link text-white d-flex align-items-center">
        <i class="fas fa-shield-alt me-2"></i> 
        <span>Manage Logins</span>
    </a>
</li>
    
 <li class="nav-item mb-1">
        <a href="#candidateSubmenu" 
           data-bs-toggle="collapse" 
           role="button" 
           aria-expanded="false" 
           aria-controls="candidateSubmenu" 
           class="nav-link text-white d-flex justify-content-between align-items-center">
            <span><i class="fas fa-user-tie me-2"></i> Candidates Management</span>
            <i class="fas fa-chevron-down small opacity-50"></i>
        </a>
        <div class="collapse" id="candidateSubmenu" data-bs-parent="#sidebarMenu">
            <ul class="nav flex-column ms-4 small border-start border-secondary border-opacity-25 mt-1">
                <li class="nav-item">
                    <a href="add-candidate" class="nav-link text-white-50 hover:text-white py-1">
                        <i class="fas fa-user-tie me-2"></i> Add Candidate
                    </a>
                </li>
                <li class="nav-item">
                    <a href="candidate-records" class="nav-link text-white-50 hover:text-white py-1">
                        <i class="fas fa-user-tie me-2"></i> Candidate Records
                    </a>
                </li>
                
            </ul>
        </div>
    </li>
    
    <li class="nav-item mb-1">
        <a href="candidate-record" class="nav-link text-white">
            <i class="fas fa-chart-bar me-2"></i> Live Results
        </a>
    </li>
<li class="nav-item mb-1">
    <a href="audit_log" class="nav-link text-white">
        <i class="fas fa-history me-2"></i> Audit Log
    </a>
</li>
   <li class="nav-item mb-1">
    <a href="website_setting" class="nav-link text-white">
        <i class="fas fa-cog me-2"></i> Website Setting
    </a>
</li>
</ul>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Sidebar Logic
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const btn = document.getElementById('sidebarCollapse');

    if(btn) {
        btn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
    }

    if(overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }
</script>