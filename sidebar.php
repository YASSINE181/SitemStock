<?php
/* ================= DÉMARRER LA SESSION ================= */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ================= DÉCONNEXION ================= */
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

/* ================= PROTECTION DES PAGES ================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/* ================= INFOS UTILISATEUR CONNECTÉ ================= */
/* 👉 ON GARDE TON CODE, ON AJOUTE JUSTE LA RÉCUPÉRATION DU NOM */
try {
    $db = new PDO("mysql:host=localhost;dbname=sitemstock;charset=utf8", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT nom FROM utilisateurs WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $userNom = $user ? $user['nom'] : 'Utilisateur';

} catch (PDOException $e) {
    $userNom = 'Utilisateur';
}
?>
<!-- ================= SIDEBAR ================= -->
<div class="sidebar">
    <div class="sidebar-logo">
        <h3><i class="fas fa-warehouse me-2"></i> SitemStock</h3>
        <small class="text-white">Gestion de stock</small>
    </div>

    <div class="sidebar-menu">
        <a href="acceuil.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'acceuil.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i><span>Accueil</span>
        </a>

        <a href="dashboard.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i><span>Tableau de bord</span>
        </a>

        <a href="gestionstock.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'gestionstock.php' ? 'active' : ''; ?>">
            <i class="fas fa-boxes"></i><span>Produits</span>
        </a>

        <a href="gestionclient.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'gestionclient.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i><span>Clients</span>
        </a>

        <a href="fournisseurs.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'fournisseurs.php' ? 'active' : ''; ?>">
            <i class="fas fa-truck"></i><span>Fournisseurs</span>
        </a>

        <a href="utilisateur.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'utilisateur.php' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i><span>Utilisateur</span>
        </a>

        <a href="gestioncommande.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'gestioncommande.php' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-cart"></i><span>Commandes</span>
        </a>

        <a href="mouvement.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'mouvement.php' ? 'active' : ''; ?>">
            <i class="fas fa-arrows-rotate"></i><span>Mouvement</span>
        </a>

        <a href="?action=logout" class="sidebar-item">
            <i class="fas fa-sign-out-alt"></i><span>Déconnexion</span>
        </a>
    </div>

    <div class="sidebar-footer" style="position:absolute; bottom:20px; width:100%; text-align:center;">
        <small class="text-muted">Version 2.0.1</small>
    </div>
</div>
<!-- Barre de Navigation Supérieure -->
        <div class="top-navbar">
            <div class="user-profile">
                <div class="notification-bell">
                    <i class="fas fa-bell fa-lg"></i>
                    <span class="notification-badge">3</span>
                </div>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            <?php echo !empty($user['nom']) ? strtoupper(substr($user['nom'], 0, 1)) : 'U'; ?>
                        </div>
                        <div class="user-info ms-2">
                            <h5><?php echo !empty($user['nom']) ? htmlspecialchars($user['nom']) : 'Utilisateur'; ?></h5>
                            <p>Administrateur</p>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="?action=logout"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a></li>
                    </ul>
                </div>
            </div>
        </div>