<ul class="pagination justify-content-end">
  <!-- Previous -->
  <li class="page-item <?php if($page <= 1) echo 'disabled'; ?>">
    <a class="page-link" href="?page=<?php echo $page-1; ?>">Previous</a>
  </li>

  <?php for ($i = 1; $i <= $total_pages; $i++): ?>
    <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
      <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
    </li>
  <?php endfor; ?>

  <!-- Next -->
  <li class="page-item <?php if($page >= $total_pages) echo 'disabled'; ?>">
    <a class="page-link" href="?page=<?php echo $page+1; ?>">Next</a>
  </li>
</ul>
