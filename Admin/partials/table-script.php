<script>
    // 1. Dynamic Search (Matches row text against search input)
    document.getElementById('dynamicSearch').addEventListener('keyup', function() {
        let value = this.value.toLowerCase();
        let rows = document.querySelectorAll('#tableBody tr');
        rows.forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(value) ? '' : 'none';
        });
    });
</script>