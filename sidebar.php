<!-- Sidebar Verticale -->
<div class="sidebar">
    <div class="sidebar-logo">
        <h3><i class="fas fa-warehouse me-2"></i> SitemStock</h3>
        <small class="text-muted">Gestion de stock</small>
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
        <a href="gestionstock.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'gestionstock.php' ? 'active' : ''; ?>">
            <i class="fas fa-boxes"></i>
            <span>Gestion du stock</span>
        </a>
        <a href="gestionclient.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'gestionclient.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Clients</span>
        </a>
        <a href="fournisseurs.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'fournisseurs.php' ? 'active' : ''; ?>">
            <i class="fas fa-truck"></i>
            <span>Fournisseurs</span>
        </a>
        <a href="statistiques.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'statistiques.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i>
            <span>Statistiques</span>
        </a>
        <a href="facturation.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'facturation.php' ? 'active' : ''; ?>">
            <i class="fas fa-file-invoice-dollar"></i>
            <span>Facturation</span>
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