<?php
session_start();
require "config.php";

// Sécurité
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Récupérer utilisateur
$stmt = $pdo->prepare("SELECT nom FROM utilisateur WHERE id=?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// VÉRIFICATION AJOUTÉE : Si l'utilisateur n'existe pas
if (!$user) {
    // Détruire la session et rediriger
    session_destroy();
    header("Location: login.php?error=session_expired");
    exit;
}

// Compter le nombre de produits
$stmt = $pdo->query("SELECT COUNT(*) as total_produits FROM produit");
$totalProduits = $stmt->fetch(PDO::FETCH_ASSOC)['total_produits'];

// Compter le nombre de commandes aujourd'hui
$stmt = $pdo->query("SELECT COUNT(*) as total_commandes_today FROM commande WHERE date_commande = CURDATE()");
$totalCommandesToday = $stmt->fetch(PDO::FETCH_ASSOC)['total_commandes_today'];

// Compter le nombre de fournisseurs actifs (etat = 1)
$stmt = $pdo->query("SELECT COUNT(*) as total_fournisseurs FROM fournisseur WHERE etat = '1'");
$totalFournisseurs = $stmt->fetch(PDO::FETCH_ASSOC)['total_fournisseurs'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Accueil - SiteMStock</title>
<link rel="stylesheet" href="sidebar.css">

<!-- Bootstrap + Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    .card .badge {
        font-size: 0.9rem;
    }
</style>
</head>
<body>

<div class="main-content p-4">
    <?php include "sidebar.php"; ?>

    <!-- ===== WELCOME CARD ===== -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4><i class="fas fa-gauge me-2"></i> Tableau de bord</h4>
                    <p class="mb-0">Bienvenue <strong><?= htmlspecialchars($user['nom'] ?? 'Utilisateur') ?></strong></p>
                    <small><?= date('d/m/Y H:i') ?></small>
                </div>
                <i class="fas fa-chart-line fa-3x opacity-50"></i>
            </div>
        </div>
    </div>

    <!-- ===== STAT CARDS ===== -->
    <div class="row mb-4">
        <!-- Produits -->
        <div class="col-md-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <i class="fas fa-box fa-2x text-primary mb-2"></i>
                    <h6>Produits</h6>
                    <span class="badge bg-primary text-white"><?= $totalProduits ?> en stock</span>
                </div>
            </div>
        </div>

        <!-- Commandes du jour -->
        <div class="col-md-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <i class="fas fa-cart-shopping fa-2x text-warning mb-2"></i>
                    <h6>Commandes</h6>
                    <span class="badge bg-warning text-dark"><?= $totalCommandesToday ?> aujourd'hui</span>
                </div>
            </div>
        </div>

        <!-- Fournisseurs -->
        <div class="col-md-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <i class="fas fa-truck fa-2x text-success mb-2"></i>
                    <h6>Fournisseurs</h6>
                    <span class="badge bg-success text-white"><?= $totalFournisseurs ?> actifs</span>
                </div>
            </div>
        </div>
    </div>

</div> <!-- main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>