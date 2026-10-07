
</main>


<footer class="site-footer">

    CRM System

    &copy;

    <?php echo date('Y'); ?>

</footer>


</div>


<script>

document.addEventListener('click', function (event) {

    const sidebar = document.querySelector('.sidebar');

    const button = document.querySelector('.mobile-menu-button');

    if (!sidebar || !button) {
        return;
    }

    if (
        document.body.classList.contains('sidebar-open') &&
        !sidebar.contains(event.target) &&
        !button.contains(event.target)
    ) {
        document.body.classList.remove('sidebar-open');
    }

});

</script>


</body>

</html>

