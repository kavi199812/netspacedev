</main>

<footer class="admin-footer">
    <p>&copy; <?= date('Y') ?> NetSpace Software Development. Connected to MySQL database <code>netspace</code>.</p>
</footer>

<script>
// Auto dismiss alert after 5s
setTimeout(() => {
    const alert = document.querySelector('.alert');
    if (alert) {
        alert.style.transition = 'opacity 0.5s ease';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    }
}, 5000);
</script>
</body>
</html>
