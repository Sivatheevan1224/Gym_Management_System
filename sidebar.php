<link rel="stylesheet" href="../home/home.css">

<style>
/* Sidebar Styles */
.sidebar {
    width: 180px;
    background-color: #343a40;
    color: white;
    position: fixed;
    height: 100vh;
    overflow-y: auto;
    transition: all 0.3s ease;
    z-index: 100;
}

.sidebar-menu {
    list-style: none;
    padding: 20px 0;
    margin: 0;
}

.sidebar-item {
    margin-bottom: 5px;
}

.sidebar-link {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.sidebar-link:hover, .sidebar-link.active {
    background-color: #495057;
    color: white;
}

.sidebar-link img {
    height: 18px;
    width: 18px;
    margin-right: 10px;
}

/* Responsive Styles */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        display: none;
    }
    
    .sidebar.active {
        display: block;
    }
}
</style>

<nav class="sidebar" id="sidebar">
    <ul class="sidebar-menu">
        <li class="sidebar-item">
            <a href="../home/home.php" class="sidebar-link">
                <img src="../images/dashboard.png" alt="Dashboard">
                Dashboard
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="../gym/manage_gym.php" class="sidebar-link">
                <img src="../images/dumbbell.png" alt="Gym">
                Manage Gyms
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="../trainer/manage_trainer.php" class="sidebar-link">
                <img src="../images/trainer.png" alt="Trainer">
                Manage Trainers
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="../member/manage_member.php" class="sidebar-link">
                <img src="../images/member.png" alt="Members">
                Manage Members
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="../payment/manage_payment.php" class="sidebar-link">
                <img src="../images/payment.png" alt="Payment">
                Manage Payments
            </a>
        </li>
    </ul>
</nav>

<script>
// Toggle sidebar on mobile
document.addEventListener('DOMContentLoaded', function() {
    // This would be added by your main page script
    // const menuToggle = document.createElement('button');
    // menuToggle.className = 'menu-toggle';
    // menuToggle.innerHTML = '☰';
    // menuToggle.onclick = function() {
    //     document.getElementById('sidebar').classList.toggle('active');
    // };
    // document.querySelector('.navbar').appendChild(menuToggle);
});
</script>