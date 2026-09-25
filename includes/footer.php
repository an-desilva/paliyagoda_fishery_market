<?php
// includes/footer.php
?>
        </main>
    </div>

    <!-- Live Clock Script & Global Handlers -->
    <script>
        function updateClock() {
            const timeElem = document.getElementById('live-time-display');
            if (timeElem) {
                const now = new Date();
                timeElem.textContent = now.toLocaleTimeString('en-US', { hour12: false });
            }
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>
