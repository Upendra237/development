</div>
    </main>
    
    <script>
        // User dropdown toggle
        document.addEventListener('DOMContentLoaded', function() {
            const userDropdownToggle = document.getElementById('user-dropdown-toggle');
            const userDropdown = document.getElementById('user-dropdown');
            
            if (userDropdownToggle && userDropdown) {
                userDropdownToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('active');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!userDropdownToggle.contains(e.target) && !userDropdown.contains(e.target)) {
                        userDropdown.classList.remove('active');
                    }
                });
            }
            
            // Auto-hide flash messages after 5 seconds
            const flashMessage = document.querySelector('.message');
            if (flashMessage) {
                setTimeout(function() {
                    flashMessage.style.opacity = '0';
                    setTimeout(function() {
                        flashMessage.style.display = 'none';
                    }, 500);
                }, 5000);
            }
        });
    </script>
</body>
</html>