            <div class="d-flex align-items-center">
                <button id="sidebarCollapse" class="btn btn-outline-secondary me-3 d-lg-none">
                    <i class="fas fa-bars"></i>
                </button>
                <h6 class="mb-0 d-none d-sm-block text-muted">Admin <strong>Dashboard</strong></h6>
            </div>
            
            <div class="dropdown">
                <button class="btn btn-link text-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle"></i> <span class="d-none d-md-inline"><?php echo $row_user['full_name']; ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                    <li><a class="dropdown-item" href="profile"><i class="fas fa-user-edit me-2"></i> Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                     <li><a class="dropdown-item" href="change-password"><i class="fas fa-lock"></i> Change Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
