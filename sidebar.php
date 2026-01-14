<!-- Sidebar Verticale -->
<div class="sidebar">
    <div class="sidebar-logo">
        <h3><i class="fas fa-warehouse me-2"></i> SitemStock</h3>
        <small class="text-white">Gestion de stock</small>
    </div>
    
    <div class="sidebar-menu">
        <!-- Lien Accueil ajouté ici -->
        <a href="acceuil.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'acceuil.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Accueil</span>
        </a>
        
        <a href="dashboard.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Tableau de bord</span>
        </a>
        <a href="produit.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'produit.php' ? 'active' : ''; ?>">
            <i class="fas fa-boxes"></i>
            <span>produit</span>
        </a>
        <a href="gestionclient.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'gestionclient.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Clients</span>
        </a>
        <a href="fournisseur.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'fournisseur.php' ? 'active' : ''; ?>">
            <i class="fas fa-truck"></i>
            <span>Fournisseurs</span>
        </a>
        <a href="utilisateur.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'utilisateur.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-user"></i>
            <span>Utilisateur</span>
        </a>
        <a href="gestioncommande.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'gestioncommande.php' ? 'active' : ''; ?>">
           <i class="fas fa-shopping-cart"></i>
    <span>Gestion des commandes</span>
        </a>
        <a href="mouvement.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'mouvement.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-arrows-rotate"></i>
            <span>Mouvement</span>
        </a>
        <a href="login.php" class="sidebar-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Déconnexion</span>
        </a>
    </div>
    
    <div class="sidebar-footer" style="position: absolute; bottom: 20px; width: 100%; padding: 20px; text-align: center;">
        <small class="text-muted">Version 2.0.1</small>
    </div>

</div>
<!-- ===== TOP NAVBAR ===== -->
    <div class="top-navbar">
        <button class="menu-toggle">
            <i class="fas fa-bars"></i>
        </button>

        <div class="user-profile">
            <div class="notification-bell">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">3</span>
            </div>

            <div class="dropdown">
                <a href="#" class="dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                    <div class="user-avatar">U</div>
                    <div class="user-info ms-2">
                        <h5>Utilisateur</h5>
                        <p>Administrateur</p>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="?action=logout">
                            <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>