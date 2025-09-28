        </main>
    </div>

    <!-- Mobile Footer Navigation -->
    <footer class="mobile-footer">
        <ul class="mobile-nav">
            <li><a href="../index.php" target="_blank"><div class="icon-wrapper"><i class="bi bi-house-door fs-4"></i></div><span>Home</span></a></li>
            <li><a href="rooms.php" class="<?php if($page == 'rooms') echo 'active'; ?>"><div class="icon-wrapper"><i class="bi bi-list-task fs-4"></i></div><span>Rooms</span></a></li>
            <li><a href="bookings.php" class="<?php if($page == 'bookings') echo 'active'; ?>"><div class="icon-wrapper"><i class="bi bi-journal-text fs-4"></i></div><span>Bookings</span></a></li>
            <li><a href="earnings.php" class="<?php if($page == 'earnings') echo 'active'; ?>"><div class="icon-wrapper"><i class="bi bi-wallet2 fs-4"></i></div><span>Earnings</span></a></li>
            <li><a href="profile.php" class="<?php if($page == 'profile') echo 'active'; ?>"><div class="icon-wrapper"><i class="bi bi-person-circle fs-4"></i></div><span>Profile</span></a></li>
        </ul>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
