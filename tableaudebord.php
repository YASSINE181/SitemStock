<?php
session_start();
require "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT nom FROM utilisateur WHERE id=?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php?error=session_expired");
    exit;
}

$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Compter le nombre de fournisseurs actifs (etat = 1)
$stmt = $pdo->query("SELECT COUNT(*) as total_fournisseurs FROM fournisseur WHERE etat = '1'");
$totalFournisseurs = $stmt->fetch(PDO::FETCH_ASSOC)['total_fournisseurs'];
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear = $year - 1;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear = $year + 1;
}

$stats = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM produit) as total_produits,
        (SELECT COUNT(*) FROM produit WHERE quantite_stock < 5 AND quantite_stock > 0) as stock_faible,
        (SELECT COUNT(*) FROM commande WHERE MONTH(date_commande) = $month AND YEAR(date_commande) = $year) as commandes_mois,
        (SELECT SUM(montant_total) FROM commande WHERE MONTH(date_commande) = $month AND YEAR(date_commande) = $year) as ca_mois
")->fetch(PDO::FETCH_ASSOC);

$stockFaible = $pdo->query("
    SELECT id, nom, quantite_stock 
    FROM produit 
    WHERE quantite_stock < 5 AND quantite_stock > 0
    ORDER BY quantite_stock ASC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$daysWithData = $pdo->prepare("
    SELECT 
        DAY(c.date_commande) as jour,
        COUNT(DISTINCT c.id) as nb_commandes,
        GROUP_CONCAT(DISTINCT CONCAT(cl.nom, ' ', cl.prenom) SEPARATOR '; ') as clients,
        GROUP_CONCAT(DISTINCT p.nom SEPARATOR '; ') as produits_commandes,
        SUM(cp.quantite) as total_quantite
    FROM commande c
    LEFT JOIN commande_produit cp ON c.id = cp.commande_id
    LEFT JOIN produit p ON cp.produit_id = p.id
    LEFT JOIN client cl ON c.client_id = cl.id
    WHERE MONTH(c.date_commande) = ? AND YEAR(c.date_commande) = ?
    GROUP BY DAY(c.date_commande)
    ORDER BY jour
");
$daysWithData->execute([$month, $year]);
$commandesParJour = $daysWithData->fetchAll(PDO::FETCH_ASSOC);

$commandesDetail = [];
foreach ($commandesParJour as $cmd) {
    $jour = $cmd['jour'];
    $commandesDetail[$jour] = $pdo->prepare("
        SELECT 
            c.id,
            c.numero_commande,
            CONCAT(cl.nom, ' ', cl.prenom) as client,
            c.montant_total,
            GROUP_CONCAT(CONCAT(p.nom, ' (', cp.quantite, ' unités)') SEPARATOR ', ') as produits_detail
        FROM commande c
        LEFT JOIN client cl ON c.client_id = cl.id
        LEFT JOIN commande_produit cp ON c.id = cp.commande_id
        LEFT JOIN produit p ON cp.produit_id = p.id
        WHERE DAY(c.date_commande) = ? 
        AND MONTH(c.date_commande) = ? 
        AND YEAR(c.date_commande) = ?
        GROUP BY c.id
        ORDER BY c.id
    ");
    $commandesDetail[$jour]->execute([$jour, $month, $year]);
    $commandesDetail[$jour] = $commandesDetail[$jour]->fetchAll(PDO::FETCH_ASSOC);
}

$commandesIndex = [];
foreach ($commandesParJour as $cmd) {
    $commandesIndex[$cmd['jour']] = $cmd;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Stock Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="sidebar.css">
    <style>
        .stat-card { border-radius: 10px; border: none; ;  height: 100%; }
        .stat-icon { font-size: 2.5rem;}
        .calendar-day { border: 1px solid #dee2e6; border-radius: 8px; padding: 10px; margin-bottom: 10px; background: white; min-height: 150px; }
        .today { background-color: #e3f2fd; border-color: #0d6efd; }
        .has-commands { background-color: #f8f9fa; border-left: 4px solid #28a745; }
        .no-commands { background-color: #f8f9fa; color: #6c757d; }
        .day-header { font-weight: bold; font-size: 1.1rem; color: #495057; border-bottom: 1px solid #dee2e6; padding-bottom: 5px; margin-bottom: 8px; }
        .badge-command { font-size: 0.8rem; padding: 4px 8px; }
        .stock-alert { border-left: 4px solid #dc3545 !important; background-color: #fff5f5 !important; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; }
        .client-item { background: white; border-radius: 6px; padding: 8px; margin-bottom: 5px; border-left: 3px solid #0d6efd; }
        .stock-faible-item { background: linear-gradient(135deg, #fff9e6, #fff); border: 1px solid #ffc107; border-radius: 8px; padding: 12px; margin-bottom: 8px; }
        @media (max-width: 1200px) { .calendar-grid { grid-template-columns: repeat(5, 1fr); } }
        @media (max-width: 992px) { .calendar-grid { grid-template-columns: repeat(4, 1fr); } }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 15px; } .calendar-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 576px) { .calendar-grid { grid-template-columns: repeat(2, 1fr); } }
    </style>
</head>
<body>
    <main class="main-content">
         <?php include "sidebar.php"; ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1"><i class="fas fa-chart-line text-primary me-2"></i>Tableau de Bord</h2>
                <p class="text-muted mb-0">Statistiques et vue d'ensemble du stock</p>
            </div>
            <div class="d-flex align-items-center">
                <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-outline-secondary me-2"><i class="fas fa-chevron-left"></i></a>
                <span class="btn btn-primary px-4"><?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?></span>
                <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-outline-secondary ms-2"><i class="fas fa-chevron-right"></i></a>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3 mb-3"><div class="card stat-card"><div class="card-body text-center"><i class="fas fa-boxes stat-icon text-primary mb-2"></i><h3 class="card-title"><?= $stats['total_produits'] ?></h3><p class="card-text text-muted">Produits en stock</p></div></div></div>
            <div class="col-md-3 mb-3"><div class="card stat-card"><div class="card-body text-center"><i class="fas fa-shopping-cart stat-icon text-success mb-2"></i><h3 class="card-title"><?= $stats['commandes_mois'] ?? 0 ?></h3><p class="card-text text-muted">Commandes ce mois</p></div></div></div>
            <div class="col-md-3 mb-3"><div class="card stat-card"><div class="card-body text-center"><i class="fas fa-money-bill-wave stat-icon text-warning mb-2"></i><h3 class="card-title"><?= number_format($stats['ca_mois'] ?? 0, 2, ',', ' ') ?> DT</h3><p class="card-text text-muted">Chiffre d'affaires</p></div></div></div>
            <div class="col-md-3 mb-3"><div class="card stat-card"><div class="card-body text-center"><i class="fas fa-exclamation-triangle stat-icon text-danger mb-2"></i><h3 class="card-title"><?= $stats['stock_faible'] ?></h3><p class="card-text text-muted">Stocks faibles (<5)</p></div></div></div>
        </div>

        <?php if (!empty($stockFaible)): ?>
        <div class="card mb-4 stock-alert">
            <div class="card-header bg-warning text-dark"><h5 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i>Alertes Stock Faible (<5 unités>)</h5></div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($stockFaible as $produit): 
                    $pourcentage = ($produit['quantite_stock'] / 5) * 100;
                    if ($pourcentage > 100) $pourcentage = 100; ?>
                    <div class="col-md-4 mb-3">
                        <div class="stock-faible-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div><h6 class="mb-1"><?= htmlspecialchars($produit['nom']) ?></h6><p class="mb-0 text-muted small">ID: <?= $produit['id'] ?></p></div>
                                <span class="badge bg-danger rounded-pill px-3 py-2"><?= $produit['quantite_stock'] ?> unités</span>
                            </div>
                            <div class="progress mt-2" style="height: 8px;"><div class="progress-bar bg-danger" role="progressbar" style="width: <?= $pourcentage ?>%"></div></div>
                            <small class="text-danger mt-1 d-block"><i class="fas fa-info-circle me-1"></i>Réapprovisionnement urgent</small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-light"><h5 class="mb-0"><i class="fas fa-calendar-alt text-primary me-2"></i>Commandes du mois</h5></div>
            <div class="card-body">
                <div class="calendar-grid">
                    <?php
                    $daysInMonth = date('t', mktime(0, 0, 0, $month, 1, $year));
                    $today = date('j');
                    for ($day = 1; $day <= $daysInMonth; $day++):
                        $isToday = ($day == $today && $month == date('n') && $year == date('Y'));
                        $hasCommands = isset($commandesIndex[$day]);
                        $commandData = $hasCommands ? $commandesIndex[$day] : null;
                        $clientsList = $hasCommands ? explode('; ', $commandData['clients']) : [];
                    ?>
                    <div class="calendar-day <?= $isToday ? 'today' : ($hasCommands ? 'has-commands' : 'no-commands') ?>">
                        <div class="day-header d-flex justify-content-between align-items-center">
                            <span><?= $day ?></span>
                            <?php if ($isToday): ?><span class="badge bg-primary badge-command">Aujourd'hui</span>
                            <?php elseif ($hasCommands): ?><span class="badge bg-success badge-command"><?= $commandData['nb_commandes'] ?> cmd</span><?php endif; ?>
                        </div>
                        <?php if ($hasCommands): ?>
                            <div class="command-info">
                                <div class="mb-2"><small class="text-muted d-block mb-1"><i class="fas fa-users"></i> Clients:</small>
                                    <?php foreach (array_slice($clientsList, 0, 2) as $client): ?>
                                        <span class="badge bg-info text-dark mb-1 d-block"><?= htmlspecialchars($client) ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($clientsList) > 2): ?><small class="text-muted">+<?= count($clientsList) - 2 ?> autres...</small><?php endif; ?>
                                </div>
                                <div class="small text-muted mb-2"><i class="fas fa-box"></i> <?= $commandData['total_quantite'] ?> unités</div>
                                <button class="btn btn-sm btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#dayModal<?= $day ?>"><i class="fas fa-eye me-1"></i> Détails</button>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-3"><i class="fas fa-minus fa-2x mb-2"></i><br><small>Aucune commande</small></div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($hasCommands && isset($commandesDetail[$day])): ?>
                    <div class="modal fade" id="dayModal<?= $day ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title"><i class="fas fa-calendar-day me-2"></i>Détails du <?= sprintf("%02d/%02d/%04d", $day, $month, $year) ?></h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-info mb-4">
                                        <div class="row">
                                            <div class="col-md-4"><h6><i class="fas fa-shopping-cart me-2"></i>Commandes</h6><p class="mb-0 display-6"><?= $commandData['nb_commandes'] ?></p></div>
                                            <div class="col-md-4"><h6><i class="fas fa-users me-2"></i>Clients</h6><p class="mb-0 display-6"><?= count($clientsList) ?></p></div>
                                            <div class="col-md-4"><h6><i class="fas fa-box me-2"></i>Unités</h6><p class="mb-0 display-6"><?= $commandData['total_quantite'] ?></p></div>
                                        </div>
                                    </div>
                                    <h5 class="mb-3"><i class="fas fa-receipt me-2"></i>Détails des commandes</h5>
                                    <?php foreach ($commandesDetail[$day] as $commande): 
                                    $produitsCommande = explode(', ', $commande['produits_detail']); ?>
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div><strong>Commande #<?= $commande['numero_commande'] ?></strong><span class="badge bg-primary ms-2"><?= number_format($commande['montant_total'], 2, ',', ' ') ?> DT</span></div>
                                                <span class="badge bg-info"><i class="fas fa-user me-1"></i><?= htmlspecialchars($commande['client']) ?></span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <h6 class="mb-2"><i class="fas fa-boxes me-2"></i>Produits commandés:</h6>
                                            <?php foreach ($produitsCommande as $produit): if (!empty($produit)): ?>
                                            <span class="badge bg-secondary me-1 mb-1"><?= htmlspecialchars($produit) ?></span>
                                            <?php endif; endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    <h5 class="mt-4 mb-3"><i class="fas fa-user-friends me-2"></i>Clients du jour</h5>
                                    <div class="row"><?php foreach ($clientsList as $client): ?>
                                        <div class="col-md-6 mb-2"><div class="client-item"><i class="fas fa-user-circle me-2 text-primary"></i><?= htmlspecialchars($client) ?></div></div>
                                    <?php endforeach; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>