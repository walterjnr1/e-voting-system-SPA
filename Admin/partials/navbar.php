<div class="d-flex align-items-center">
    <button id="sidebarCollapse" class="btn btn-outline-secondary me-3 d-lg-none">
        <i class="fas fa-bars"></i>
    </button>
    <h6 class="mb-0 d-none d-sm-block text-muted"></h6>
</div>

<div class="dropdown">
    <button class="btn btn-link text-dark text-decoration-none dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
        <div class="me-2 rounded-circle overflow-hidden shadow-sm" style="width: 35px; height: 35px; background: #e9ecef; display: flex; align-items: center; justify-content: center;">
            <?php if(!empty($row_user['user_image']) && file_exists("../".$row_user['user_image'])): ?>
                <img src="../<?php echo $row_user['user_image']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <i class="fas fa-user-circle text-muted" style="font-size: 1.5rem;"></i>
            <?php endif; ?>
        </div>
        <span class="d-none d-md-inline"><?php echo $row_user['full_name']; ?></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
        <li><a class="dropdown-item" href="profile"><i class="fas fa-user-edit me-2"></i> Profile</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="change-password"><i class="fas fa-lock me-2"></i> Change Password</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="logout"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
    </ul>
</div>