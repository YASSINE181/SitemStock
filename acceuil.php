<?php
session_start();

// Vérifier utilisateur
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Connexion DB
$db = new PDO("mysql:host=localhost;dbname=sitemstock;charset=utf8", 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Récupérer utilisateur
$req = $db->prepare("SELECT nom, email FROM utilisateur WHERE id = ?");
$req->execute([$_SESSION['user_id']]);
$user = $req->fetch();
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Dernières commandes avec produits
$recentCommandes = $db->query("
    SELECT c.id, c.numero_commande, cl.nom AS client_nom, cl.prenom AS client_prenom, 
           c.date_commande, c.montant_total
    FROM commande c
    JOIN client cl ON cl.id = c.client_id
    ORDER BY c.id DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les produits pour ces commandes
$commandeProduits = [];
foreach($recentCommandes as $c){
    $stmt = $db->prepare("
        SELECT p.nom AS produit_nom, cp.quantite, cp.prix_unitaire
        FROM commande_produit cp
        JOIN produit p ON p.id = cp.produit_id
        WHERE cp.commande_id = ?
    ");
    $stmt->execute([$c['id']]);
    $commandeProduits[$c['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Accueil - SitemStock</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="acceuil.css">
</head>
<body>

<div class="main-content">
    <?php include 'sidebar.php'; ?>
    
    <!-- Section de bienvenue -->
    <div class="welcome-section">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2>Bienvenue, <?= htmlspecialchars($user['nom']) ?> !</h2>
                <p class="mb-0">Vous êtes connecté à votre tableau de bord de gestion de stock.</p>
                <p class="mb-0">Date du jour : <?= date('d/m/Y') ?> - <?= date('H:i') ?></p>
            </div>
            <div class="col-md-4 text-end">
                <i class="fas fa-chart-line fa-4x opacity-50"></i>
            </div>
        </div>
    </div>
    
    <!-- Dernières commandes avec produits -->
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-clock"></i> Dernières commandes</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th><th>Numéro</th><th>Client</th><th>Date</th><th>Produits</th><th>Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($recentCommandes as $c): ?>
                    <tr>
                        <td><?= $c['id'] ?></td>
                        <td><?= $c['numero_commande'] ?></td>
                        <td><?= $c['client_nom'].' '.$c['client_prenom'] ?></td>
                        <td><?= date('d/m/Y', strtotime($c['date_commande'])) ?></td>
                        <td>
                            <ul class="mb-0">
                            <?php foreach($commandeProduits[$c['id']] as $p): ?>
                                <li><?= htmlspecialchars($p['produit_nom']) ?> x <?= $p['quantite'] ?> (<?= number_format($p['prix_unitaire'],2) ?> €)</li>
                            <?php endforeach; ?>
                            </ul>
                        </td>
                        <td><?= number_format($c['montant_total'],2) ?> €</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Profil -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileModalLabel">
                    <i class="fas fa-user-circle me-2"></i>Mon Profil
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="profile-avatar-large mb-3"><?= strtoupper(substr($user['nom'],0,1)) ?></div>
                        <h4><?= htmlspecialchars($user['nom']) ?></h4>
                        <p class="text-muted">Administrateur</p>
                    </div>
                    <div class="col-md-8">
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <label class="form-label text-muted">Nom complet</label>
                                <div class="form-control-static"><?= htmlspecialchars($user['nom']) ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Email</label>
                                <div class="form-control-static"><?= htmlspecialchars($user['email']) ?></div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <label class="form-label text-muted">ID Utilisateur</label>
                                <div class="form-control-static">#<?= $_SESSION['user_id'] ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Rôle</label>
                                <div class="form-control-static"><span class="badge bg-primary">Administrateur</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>