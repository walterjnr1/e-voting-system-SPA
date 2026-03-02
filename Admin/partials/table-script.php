<script>
  
  // Simple search filter
  const searchInput = document.getElementById('searchInput');
  const table = document.getElementById('transactionTable').getElementsByTagName('tbody')[0];

  searchInput.addEventListener('keyup', function() {
    const filter = searchInput.value.toLowerCase();
    const rows = table.getElementsByTagName('tr');
    for (let i=0; i<rows.length; i++) {
      const cells = rows[i].getElementsByTagName('td');
      let matched = false;
      for (let j=0; j<cells.length; j++) {
        if (cells[j].textContent.toLowerCase().includes(filter)) {
          matched = true;
          break;
        }
      }
      rows[i].style.display = matched ? '' : 'none';
    }
  });
</script>