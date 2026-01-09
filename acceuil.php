<?php
session_start();
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
// 2. Connexion DB (correction du DSN)
$db = new PDO("mysql:host=localhost;dbname=sitemstock", 'root', '');
// 3. Récupérer utilisateur - SANS created_at qui n'existe pas
$req = $db->prepare("SELECT nom, email FROM utilisateur WHERE id = ?");
$req->execute([$_SESSION['user_id']]);
$user = $req->fetch();
// Vérifier si l'utilisateur existe
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// 5. Déconnexion
if (!empty($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - SitemStock</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="utilisateur.css">
</head>
<body>
    <!-- Inclusion de la sidebar -->
    <?php include 'sidebar.php'; ?>
    
    <!-- Contenu Principal -->
    <div class="main-content">
        <!-- Barre de Navigation Supérieure -->
        <div class="top-navbar">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
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
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal"><i class="fas fa-user-circle me-2"></i> Mon profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="?action=logout"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Section de Bienvenue -->
        <div class="welcome-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2>Bienvenue, <?php echo !empty($user['nom']) ? htmlspecialchars($user['nom']) : 'Utilisateur'; ?> !</h2>
                    <p class="mb-0">Vous êtes connecté à votre tableau de bord de gestion de stock.</p>
                    <p class="mb-0">Date du jour : <?php echo date('d/m/Y'); ?> - <?php echo date('H:i'); ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <i class="fas fa-chart-line fa-4x opacity-75"></i>
                </div>
            </div>
        </div>
    <!-- Modal pour le profil utilisateur -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="profileModalLabel">
                        <i class="fas fa-user-circle me-2"></i>Mon Profil
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="profile-avatar-large mb-3">
                                <?php echo !empty($user['nom']) ? strtoupper(substr($user['nom'], 0, 1)) : 'U'; ?>
                            </div>
                            <h4><?php echo !empty($user['nom']) ? htmlspecialchars($user['nom']) : 'Utilisateur'; ?></h4>
                            <p class="text-muted">Administrateur</p>
                            <button class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-camera me-1"></i> Changer la photo
                            </button>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Nom complet</label>
                                    <div class="form-control-static"><?php echo !empty($user['nom']) ? htmlspecialchars($user['nom']) : 'Non défini'; ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Adresse email</label>
                                    <div class="form-control-static"><?php echo !empty($user['email']) ? htmlspecialchars($user['email']) : 'Non défini'; ?></div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">ID Utilisateur</label>
                                    <div class="form-control-static">#<?php echo !empty($_SESSION['user_id']) ? $_SESSION['user_id'] : 'N/A'; ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Rôle</label>
                                    <div class="form-control-static">
                                        <span class="badge bg-primary">Administrateur</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Date d'inscription</label>
                                    <div class="form-control-static">
                                        <?php 
                                        // Date par défaut (date actuelle)
                                        echo date('d/m/Y');
                                        ?>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Statut</label>
                                    <div class="form-control-static">
                                        <span class="badge bg-success">Actif</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Dernière connexion</label>
                                    <div class="form-control-static"><?php echo date('d/m/Y H:i'); ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Temps de session</label>
                                    <div class="form-control-static"><?php echo date('H:i:s'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Fermer
                    </button>
                    <button type="button" class="btn btn-primary" id="editProfileBtn">
                        <i class="fas fa-edit me-1"></i> Modifier le profil
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JavaScript personnalisé -->
    <script src="utilisateur.js"></script>
</body>
</html>