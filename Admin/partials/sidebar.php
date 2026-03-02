
<div class="text-center py-3 position-relative border-bottom">
  <!-- App Logo -->
  <img src="../<?php echo htmlspecialchars($app_logo); ?>" 
       alt="Logo" 
       style="max-width: 120px; height: auto;">

  <!-- Close Icon for Mobile -->
  <i class="fas fa-times d-md-none" 
     id="sidebarClose" 
     style="cursor:pointer; position: absolute; right: 15px; top: 15px;"></i>
</div>

<ul class="nav flex-column pt-2">
  <li class="nav-item">
    <a href="dashboard" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard' ? 'active' : ''; ?>">
      <i class="fas fa-tachometer-alt me-2"></i> Dashboard
    </a>
  </li>

  <!-- Profile / Account -->
  <li class="nav-item">
    <a href="#profileSubmenu" data-bs-toggle="collapse" class="nav-link d-flex justify-content-between align-items-center">
      <span><i class="fas fa-user me-2"></i> Account</span>
      <i class="fas fa-chevron-down small"></i>
    </a>
    <ul class="collapse nav flex-column ms-3" id="profileSubmenu">
      <li class="nav-item"><a href="profile" class="nav-link">Profile</a></li>
      <li class="nav-item"><a href="change-password" class="nav-link">Change Password</a></li>
      <li class="nav-item"><a href="login-records" class="nav-link">Login records</a></li>
      <li class="nav-item"><a href="logout" class="nav-link">Logout</a></li>

    </ul>
    
  </li>

 
  <!-- Plan Management -->
  <li class="nav-item">
    <a href="#planSubmenu" data-bs-toggle="collapse" class="nav-link d-flex justify-content-between align-items-center">
      <span><i class="fas fa-layer-group me-2"></i> Plan Management</span>
      <i class="fas fa-chevron-down small"></i>
    </a>
    <ul class="collapse nav flex-column ms-3" id="planSubmenu">
      <li class="nav-item">
        <a href="add-plan" class="nav-link">
          <i class="fas fa-plus me-1"></i> Add Plan
        </a>
      </li>
      <li class="nav-item">
        <a href="plan-records" class="nav-link">
          <i class="fas fa-list-alt me-1"></i> Plan Records
        </a>
      </li>
      <li class="nav-item">
        <a href="add-features" class="nav-link">
          <i class="fas fa-star me-1"></i> Add Features
        </a>
      </li>
      <li class="nav-item">
        <a href="plan-feature-mapping" class="nav-link">
          <i class="fas fa-project-diagram me-1"></i> Plan Feature Mapping
        </a>
      </li>
    </ul>
  </li>

 <!-- Notification Management -->
<li class="nav-item">
  <a href="#notificationSubmenu"
     data-bs-toggle="collapse"
     class="nav-link d-flex justify-content-between align-items-center">
    
    <span>
      <i class="fas fa-bell me-2"></i> Notification Management
    </span>

    <i class="fas fa-chevron-down small"></i>
  </a>

  <ul class="collapse nav flex-column ms-3" id="notificationSubmenu">
    <li class="nav-item">
      <a href="post-marquee-notification" class="nav-link">
        <i class="fas fa-bullhorn me-1"></i>
        Post Marquee Notification
      </a>
    </li>

    <li class="nav-item">
      <a href="marquee-notification-records" class="nav-link">
        <i class="fas fa-clipboard-list me-1"></i>
        Marquee Notification Records
      </a>
    </li>
  </ul>
</li>


  <!-- Subscription Management -->
  <li class="nav-item">
    <a href="subscription-records" class="nav-link">
      <i class="fas fa-receipt me-2"></i> Subscription Records
    </a>
  </li>

<!-- collaborative record -->
  <li class="nav-item">
    <a href="#collaborativeSubmenu" data-bs-toggle="collapse" class="nav-link d-flex justify-content-between align-items-center">
      <span><i class="fas fa-book"></i> Collaborative Question record</span>
      <i class="fas fa-chevron-down small"></i>
    </a>
    <ul class="collapse nav flex-column ms-3" id="collaborativeSubmenu">
      <li class="nav-item"><a href="collaborative_question_record" class="nav-link">Questions</a></li>
      <li class="nav-item"><a href="collaborative_interview_record" class="nav-link">Interviews</a></li>

    </ul>
    
  </li>

  <!-- otp -->
  <li class="nav-item">
    <a href="otp-records" class="nav-link">
      <i class="fas fa-code"></i> OTP Records
    </a>
  </li>
<li class="nav-item">
    <a href="question-records" class="nav-link">
      <i class="fas fa-question"></i> Question Records
    </a>
  </li>
  

  <!-- Subject Records -->
  <li class="nav-item">
    <a href="subject-records" class="nav-link">
      <i class="fas fa-history me-2"></i> Subject Records
    </a>
  </li>

  <!-- Request Demo Records -->
  <li class="nav-item">
    <a href="demo-records" class="nav-link">
      <i class="fas fa-history me-2"></i> Request Demo Records    </a>
  </li>

  <!-- support Management -->
<li class="nav-item">
  <a href="#supportSubmenu"
     data-bs-toggle="collapse"
     class="nav-link d-flex justify-content-between align-items-center">
    
    <span>
      <i class="fas fa-bell me-2"></i> Support Management
    </span>

    <i class="fas fa-chevron-down small"></i>
  </a>

  <ul class="collapse nav flex-column ms-3" id="supportSubmenu">
    <li class="nav-item">
      <a href="support_record" class="nav-link">
        <i class="fas fa-bullhorn me-1"></i>
        Support Requests
      </a>
    </li>

    <li class="nav-item">
      <a href="feedback-record" class="nav-link">
        <i class="fas fa-clipboard-list me-1"></i>
        Feedback Records
      </a>
    </li>
  </ul>
</li>

  <!-- Activity Logs -->
  <li class="nav-item">
    <a href="activity_log" class="nav-link">
      <i class="fas fa-clipboard-list me-2"></i> Activity Logs
    </a>
  </li>

  <!-- Website Settings -->
  <li class="nav-item">
    <a href="website-setting" class="nav-link">
      <i class="fas fa-cog me-2"></i> Website Settings
    </a>
  </li>
  <!-- logout -->
  <li class="nav-item">
    <a href="logout" class="nav-link">
      <i class="fas fa-sign-out-alt me-2"></i> Logout
    </a>
  </li>
</ul>
