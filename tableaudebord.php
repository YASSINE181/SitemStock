<?php
session_start();

$host = "localhost";
$db   = "sitemstock";
$user = "root";
$pass = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT nom, email FROM utilisateur WHERE id=?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Statistiques principales
$stats = [
    'produits' => $pdo->query("SELECT COUNT(*) as total FROM produit")->fetch()['total'],
    'commandes_today' => $pdo->query("SELECT COUNT(*) as total FROM commande WHERE DATE(date_commande) = CURDATE()")->fetch()['total'],
    'ventes_mois' => $pdo->query("SELECT COALESCE(SUM(montant_total), 0) as total FROM commande WHERE MONTH(date_commande) = MONTH(CURDATE()) AND YEAR(date_commande) = YEAR(CURDATE())")->fetch()['total'],
    'stock_bas' => $pdo->query("SELECT COUNT(*) as total FROM produit WHERE quantite_stock <= 5")->fetch()['total'],
    'clients' => $pdo->query("SELECT COUNT(*) as total FROM client")->fetch()['total'],
    'valeur_stock' => $pdo->query("SELECT COALESCE(SUM(quantite_stock * prix_achat), 0) as total FROM produit")->fetch()['total']
];

// Graphique 30 jours pour une vue plus significative
$commandes_30jours = $pdo->query("
    SELECT 
        DATE(date_commande) as date,
        COUNT(*) as commandes,
        COALESCE(SUM(montant_total), 0) as montant
    FROM commande 
    WHERE date_commande >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(date_commande)
    ORDER BY date
")->fetchAll(PDO::FETCH_ASSOC);

// Préparer données graphique
$labels = [];
$data_commandes = [];
$data_montants = [];

foreach ($commandes_30jours as $item) {
    $labels[] = date('d/m', strtotime($item['date']));
    $data_commandes[] = $item['commandes'];
    $data_montants[] = $item['montant'];
}

// Dernières commandes
$dernieres_commandes = $pdo->query("
    SELECT 
        c.id,
        c.numero_commande,
        c.date_commande,
        c.montant_total,
        CONCAT(cl.nom, ' ', cl.prenom) as client_nom,
        c.statut
    FROM commande c
    JOIN client cl ON c.client_id = cl.id
    ORDER BY c.date_commande DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// Produits à réapprovisionner
$stock_bas = $pdo->query("
    SELECT 
        nom,
        reference,
        quantite_stock,
        seuil_alerte
    FROM produit 
    WHERE quantite_stock <= 10
    ORDER BY quantite_stock ASC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Gestion Stock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .dashboard-container {
            padding: 20px;
            max-width: 1600px;
            margin: 0 auto;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .stat-icon.primary { background-color: rgba(52, 152, 219, 0.1); color: var(--secondary-color); }
        .stat-icon.success { background-color: rgba(39, 174, 96, 0.1); color: var(--success-color); }
        .stat-icon.warning { background-color: rgba(243, 156, 18, 0.1); color: var(--warning-color); }
        .stat-icon.danger { background-color: rgba(231, 76, 60, 0.1); color: var(--danger-color); }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .table-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            height: 100%;
        }

        .table th {
            font-weight: 600;
            color: var(--primary-color);
            border-bottom: 2px solid #eee;
            padding: 12px 15px;
        }

        .table td {
            padding: 12px 15px;
            vertical-align: middle;
        }

        .badge-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .date-display {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--secondary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include "sidebar.php"; ?>

    <div class="dashboard-container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1>Tableau de bord</h1>
                <div class="date-display"><?= date('l j F Y') ?></div>
            </div>
            <div class="user-info">
                <div class="text-end">
                    <div class="fw-medium"><?= htmlspecialchars($user['nom']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                </div>
                <div class="user-avatar">
                    <?= strtoupper(substr($user['nom'], 0, 1)) ?>
                </div>
            </div>
        </div>

        <!-- Statistiques principales -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="bi bi-box-seam" style="font-size: 1.5rem;"></i>
                    </div>
                    <div class="stat-value"><?= number_format($stats['produits']) ?></div>
                    <div class="stat-label">Produits</div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="bi bi-cart-check" style="font-size: 1.5rem;"></i>
                    </div>
                    <div class="stat-value"><?= number_format($stats['commandes_today']) ?></div>
                    <div class="stat-label">Commandes aujourd'hui</div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="bi bi-graph-up-arrow" style="font-size: 1.5rem;"></i>
                    </div>
                    <div class="stat-value"><?= number_format($stats['ventes_mois'], 0, ',', ' ') ?> €</div>
                    <div class="stat-label">Ventes ce mois</div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon danger">
                        <i class="bi bi-exclamation-triangle" style="font-size: 1.5rem;"></i>
                    </div>
                    <div class="stat-value"><?= number_format($stats['stock_bas']) ?></div>
                    <div class="stat-label">Alertes stock</div>
                </div>
            </div>
        </div>

        <!-- Graphique et statistiques secondaires -->
        <div class="row g-4 mb-4">
            <!-- Graphique -->
            <div class="col-lg-8">
                <div class="chart-container">
                    <div class="section-title">Activité des 30 derniers jours</div>
                    <canvas id="salesChart" height="250"></canvas>
                </div>
            </div>
            
            <!-- Statistiques secondaires -->
            <div class="col-lg-4">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon" style="background-color: rgba(52, 152, 219, 0.1); color: var(--secondary-color);">
                                    <i class="bi bi-people" style="font-size: 1.5rem;"></i>
                                </div>
                                <div class="ms-3">
                                    <div class="stat-value"><?= number_format($stats['clients']) ?></div>
                                    <div class="stat-label">Clients</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon" style="background-color: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                                    <i class="bi bi-currency-euro" style="font-size: 1.5rem;"></i>
                                </div>
                                <div class="ms-3">
                                    <div class="stat-value"><?= number_format($stats['valeur_stock'], 0, ',', ' ') ?> €</div>
                                    <div class="stat-label">Valeur du stock</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableaux -->
        <div class="row g-4">
            <!-- Dernières commandes -->
            <div class="col-lg-8">
                <div class="table-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="section-title mb-0">Dernières commandes</div>
                        <a href="commandes.php" class="btn btn-sm btn-outline-primary">
                            Voir toutes <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>N° Commande</th>
                                    <th>Client</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th class="text-end">Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($dernieres_commandes as $commande): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($commande['numero_commande']) ?></td>
                                    <td><?= htmlspecialchars($commande['client_nom']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($commande['date_commande'])) ?></td>
                                    <td>
                                        <?php
                                        $badge_class = 'badge-warning';
                                        if($commande['statut'] == 'livrée') $badge_class = 'badge-success';
                                        if($commande['statut'] == 'annulée') $badge_class = 'badge-danger';
                                        ?>
                                        <span class="badge-status <?= $badge_class ?>">
                                            <?= htmlspecialchars($commande['statut']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold"><?= number_format($commande['montant_total'], 2, ',', ' ') ?> €</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Stock bas -->
            <div class="col-lg-4">
                <div class="table-card">
                    <div class="section-title">Produits à réapprovisionner</div>
                    
                    <?php if (!empty($stock_bas)): ?>
                        <div class="list-group">
                            <?php foreach($stock_bas as $produit): 
                                $percent = ($produit['quantite_stock'] / ($produit['seuil_alerte'] ?: 10)) * 100;
                            ?>
                            <div class="list-group-item border-0 px-0 py-3 d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold"><?= htmlspecialchars($produit['nom']) ?></h6>
                                    <small class="text-muted"><?= htmlspecialchars($produit['reference']) ?></small>
                                    <div class="progress mt-2" style="height: 4px;">
                                        <div class="progress-bar <?= $percent <= 20 ? 'bg-danger' : 'bg-warning' ?>" 
                                             role="progressbar" 
                                             style="width: <?= min($percent, 100) ?>%">
                                        </div>
                                    </div>
                                </div>
                                <span class="badge <?= $produit['quantite_stock'] <= 3 ? 'bg-danger' : 'bg-warning' ?> ms-3">
                                    <?= $produit['quantite_stock'] ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                            <p class="mt-3 text-muted">Aucune alerte de stock</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Graphique
    const ctx = document.getElementById('salesChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                label: 'Nombre de commandes',
                data: <?= json_encode($data_commandes) ?>,
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                type: 'line',
                yAxisID: 'y'
            }, {
                label: 'Montant des ventes (€)',
                data: <?= json_encode($data_montants) ?>,
                backgroundColor: 'rgba(46, 204, 113, 0.2)',
                borderColor: 'rgba(46, 204, 113, 1)',
                borderWidth: 1,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label.includes('Montant')) {
                                return label + ': ' + context.parsed.y.toFixed(2).replace('.', ',') + ' €';
                            }
                            return label + ': ' + context.parsed.y;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Nombre de commandes'
                    },
                    ticks: {
                        precision: 0
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Montant (€)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(0) + ' €';
                        }
                    }
                }
            }
        }
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>