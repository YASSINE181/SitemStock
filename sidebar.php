<?php
/* ================= DÉMARRER LA SESSION ================= */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ================= PROTECTION DES PAGES ================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/* ================= GESTION DE LA DÉCONNEXION ================= */
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}

/* ================= INFOS UTILISATEUR ================= */
try {
    $db = new PDO("mysql:host=localhost;dbname=sitemstock;charset=utf8", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Même requête que vous aviez
    $stmt = $db->prepare("SELECT nom FROM utilisateur WHERE id = ?");
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

        <a href="gestionstock.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'gestionstock.php' ? 'active' : ''; ?>">
            <i class="fas fa-boxes"></i><span>Produits</span>
        </a>

        <a href="gestionclient.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'gestionclient.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i><span>Clients</span>
        </a>

        <a href="fournisseurs.php" class="sidebar-item <?= basename($_SERVER['PHP_SELF']) == 'fournisseurs.php' ? 'active' : ''; ?>">
            <i class="fas fa-truck"></i><span>Fournisseurs</span>
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
                    <?php echo !empty($userNom) ? strtoupper(substr($userNom, 0, 1)) : 'U'; ?>
                </div>
                <div class="user-info ms-2">
                    <h5><?php echo !empty($userNom) ? htmlspecialchars($userNom) : 'Utilisateur'; ?></h5>
                    <p>Administrateur</p>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                    <i class="fas fa-user me-2"></i> Mon Profil
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="?action=logout"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- MODAL PROFIL UTILISATEUR -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-circle me-2"></i> Mon Profil</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="profile-avatar-large mb-3">
                        <?php echo !empty($userNom) ? strtoupper(substr($userNom, 0, 1)) : 'U'; ?>
                    </div>
                    <h4><?php echo htmlspecialchars($userNom); ?></h4>
                    <p class="text-muted">Administrateur</p>
                </div>
                
                <div class="profile-info">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-envelope me-2"></i> Email</label>
                        <input type="text" class="form-control" value="admin@sitemstock.com" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-calendar-alt me-2"></i> Date d'inscription</label>
                        <input type="text" class="form-control" value="<?php echo date('d/m/Y'); ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-shield-alt me-2"></i> Rôle</label>
                        <input type="text" class="form-control" value="Administrateur" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-clock me-2"></i> Dernière connexion</label>
                        <input type="text" class="form-control" value="<?php echo date('d/m/Y H:i'); ?>" readonly>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                    <i class="fas fa-key me-1"></i> Changer le mot de passe
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CHANGEMENT DE MOT DE PASSE -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-key me-2"></i> Changer le mot de passe</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Mot de passe actuel</label>
                    <input type="password" class="form-control" name="current_password">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Nouveau mot de passe</label>
                    <input type="password" class="form-control" name="new_password">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Confirmer le nouveau mot de passe</label>
                    <input type="password" class="form-control" name="confirm_password">
                </div>
                
                <div class="alert alert-info">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        Le mot de passe doit contenir au moins 8 caractères.
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour les avatars */
.profile-avatar-large {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: bold;
    margin: 0 auto;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.user-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: bold;
}

.notification-bell {
    position: relative;
    margin-right: 20px;
    cursor: pointer;
    color: #6c757d;
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #dc3545;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gérer l'interaction entre les modals
    var profileModal = document.getElementById('profileModal');
    var changePasswordModal = document.getElementById('changePasswordModal');
    
    if (changePasswordModal) {
        changePasswordModal.addEventListener('show.bs.modal', function () {
            var modal = bootstrap.Modal.getInstance(profileModal);
            if (modal) {
                modal.hide();
            }
        });
        
        changePasswordModal.addEventListener('hidden.bs.modal', function () {
            var modal = new bootstrap.Modal(profileModal);
            modal.show();
        });
    }
    
    // Gérer les notifications
    var notificationBell = document.querySelector('.notification-bell');
    if (notificationBell) {
        notificationBell.addEventListener('click', function() {
            alert('Vous avez 3 nouvelles notifications');
        });
    }
});
</script>