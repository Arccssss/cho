<?php
// views/layouts/footer.php
?>
    </div> <script>
        // Move your live date/time script here since it's used in the header globally
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true };
            const formattedDateTime = now.toLocaleDateString('en-US', options);
            const dateTimeDisplay = document.getElementById('dateTimeDisplay');
            if (dateTimeDisplay) dateTimeDisplay.textContent = formattedDateTime;
        }
        updateDateTime();
        setInterval(updateDateTime, 1000);
    </script>
</body>
</html>