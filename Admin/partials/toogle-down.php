
<style>
    /* Smooth rotation for the chevron */
    .nav-link[data-bs-toggle="collapse"] .fa-chevron-down {
        transition: transform 0.3s ease;
    }
    /* Rotate when the element DOES NOT have the 'collapsed' class */
    .nav-link[data-bs-toggle="collapse"]:not(.collapsed) .fa-chevron-down {
        transform: rotate(180deg);
    }
    
    /* Sub-menu hover styling */
    #electionSubmenu .nav-link:hover {
        color: #0dcaf0 !important;
        background: rgba(255, 255, 255, 0.05);
        padding-left: 1.5rem;
        transition: all 0.2s ease;
    }

    /* Ensure the collapse is hidden by default without inline styles */
    .collapse:not(.show) {
        display: none;
    }
</style>

<div class="nav-item">
    <a href="#electionSubmenu" 
       data-bs-toggle="collapse" 
       class="nav-link text-white d-flex justify-content-between align-items-center collapsed" 
       role="button" 
       aria-expanded="false">
        <span><i class="fas fa-vote-yea me-2"></i> Elections</span>
        <i class="fas fa-chevron-down small opacity-50"></i>
    </a>
    
    <div class="collapse" id="electionSubmenu">
        <ul class="nav flex-column ms-4 small border-start border-secondary border-opacity-25 mt-1">
            <li class="nav-item">
                <a href="create_election.php" class="nav-link text-white-50">Create New</a>
            </li>
            <li class="nav-item">
                <a href="view_elections.php" class="nav-link text-white-50">View All</a>
            </li>
        </ul>
    </div>
</div>