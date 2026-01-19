<?php
session_start();
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
// Connexion DB
$db = new PDO("mysql:host=localhost;dbname=sitemstock", 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// Récupérer l'utilisateur
$req = $db->prepare("SELECT nom, email FROM utilisateur WHERE id = ?");
$req->execute([$_SESSION['user_id']]);
$user = $req->fetch(PDO::FETCH_ASSOC);

// Gérer les actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'ajouter':
            try {
                $db->beginTransaction();
                
                // Générer un numéro de commande unique
                $numero = 'CMD' . date('Ymd') . rand(1000, 9999);
                
                // Créer la commande
                $stmt = $db->prepare("
                    INSERT INTO commande (numero_commande, client_id, date_commande, date_livraison, montant_total)
                    VALUES (?, ?, ?, ?, 0)
                ");
                $stmt->execute([
                    $numero,
                    $_POST['client_id'],
                    $_POST['date_commande'],
                    $_POST['date_livraison']
                ]);

                $commande_id = $db->lastInsertId();
                $total = 0;

                // Ajouter les produits à la commande
                if (isset($_POST['produit_id'])) {
                    foreach ($_POST['produit_id'] as $i => $pid) {
                        if (empty($pid)) continue;

                        $qte = (int)$_POST['quantite'][$i];
                        $fid = isset($_POST['fournisseur_id'][$i]) ? $_POST['fournisseur_id'][$i] : null;

                        // Récupérer le prix du produit
                        $stmt = $db->prepare("SELECT prix_vente FROM produit WHERE id = ?");
                        $stmt->execute([$pid]);
                        $pu = (float)$stmt->fetchColumn();

                        $montant = $pu * $qte;
                        $total += $montant;

                        // Ajouter le produit à la commande
                        $stmt = $db->prepare("
                            INSERT INTO commande_produit 
                            (commande_id, produit_id, fournisseur_id, quantite, prix_unitaire, montant_total)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$commande_id, $pid, $fid, $qte, $pu, $montant]);
                    }
                }

                // Mettre à jour le montant total de la commande
                $stmt = $db->prepare("UPDATE commande SET montant_total = ? WHERE id = ?");
                $stmt->execute([$total, $commande_id]);

                $db->commit();
            } catch(Exception $e) {
                $db->rollBack();
            }
            break;

        case 'modifier':
            try {
                $db->beginTransaction();
                $id = $_POST['id'];

                // Mettre à jour la commande
                $stmt = $db->prepare("
                    UPDATE commande
                    SET client_id = ?, date_commande = ?, date_livraison = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['client_id'],
                    $_POST['date_commande'],
                    $_POST['date_livraison'],
                    $id
                ]);

                // Supprimer les anciens produits de la commande
                $stmt = $db->prepare("DELETE FROM commande_produit WHERE commande_id = ?");
                $stmt->execute([$id]);

                // Ajouter les nouveaux produits
                $total = 0;
                if (isset($_POST['produit_id'])) {
                    foreach ($_POST['produit_id'] as $i => $pid) {
                        if (empty($pid)) continue;

                        $qte = (int)$_POST['quantite'][$i];
                        $fid = isset($_POST['fournisseur_id'][$i]) ? $_POST['fournisseur_id'][$i] : null;

                        // Récupérer le prix du produit
                        $stmt = $db->prepare("SELECT prix_vente FROM produit WHERE id = ?");
                        $stmt->execute([$pid]);
                        $pu = (float)$stmt->fetchColumn();

                        $montant = $pu * $qte;
                        $total += $montant;

                        // Ajouter le produit à la commande
                        $stmt = $db->prepare("
                            INSERT INTO commande_produit 
                            (commande_id, produit_id, fournisseur_id, quantite, prix_unitaire, montant_total)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$id, $pid, $fid, $qte, $pu, $montant]);
                    }
                }

                // Mettre à jour le montant total
                $stmt = $db->prepare("UPDATE commande SET montant_total = ? WHERE id = ?");
                $stmt->execute([$total, $id]);

                $db->commit();
            } catch(Exception $e) {
                $db->rollBack();
            }
            break;

        case 'supprimer':
            try {
                $id = $_POST['id'];
                // Supprimer d'abord les produits de la commande
                $stmt = $db->prepare("DELETE FROM commande_produit WHERE commande_id = ?");
                $stmt->execute([$id]);
                // Puis supprimer la commande
                $stmt = $db->prepare("DELETE FROM commande WHERE id = ?");
                $stmt->execute([$id]);
            } catch(Exception $e) {
            }
            break;
    }
    header("Location: commande.php");
    exit;
}

// Récupérer commandes
$commandes = $db->query("
    SELECT c.*, cl.nom as client_nom, cl.prenom as client_prenom
    FROM commande c
    JOIN client cl ON cl.id = c.client_id
    ORDER BY c.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les produits de chaque commande
$commandeProduits = [];
foreach ($commandes as $commande) {
    $stmt = $db->prepare("
        SELECT cp.*, p.nom as produit_nom, p.prix_vente
        FROM commande_produit cp
        JOIN produit p ON cp.produit_id = p.id
        WHERE cp.commande_id = ?
    ");
    $stmt->execute([$commande['id']]);
    $commandeProduits[$commande['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Récupérer données pour les formulaires
$clients = $db->query("SELECT * FROM client ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
$produits = $db->query("SELECT * FROM produit ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
$fournisseurs = $db->query("SELECT * FROM fournisseur WHERE etat = 1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion des Commandes - SitemStock</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="sidebar.css">
</head>
<body>
<div class="main-content">
    <?php include 'sidebar.php'; ?>
    <!-- WELCOME -->
    <div class="welcome-section">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2><i class="fas fa-shopping-cart me-2"></i> Gestion des Commandes</h2>
                <p class="mb-0">Gérez l'ensemble des commandes clients.</p>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajouterModal">
                    <i class="fas fa-plus me-1"></i> Ajouter une commande
                </button>
            </div>
        </div>
    </div>
    
    <!-- Message Flash -->
    <?php if (!empty($_SESSION['message'])): ?>
    <div class="alert alert-info alert-dismissible fade show mt-3" role="alert">
        <?= $_SESSION['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    
    <!-- TABLE COMMANDES -->
    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Liste des commandes (<?= count($commandes); ?>)</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Numéro</th>
                        <th>Client</th>
                        <th>Date Commande</th>
                        <th>Date Livraison</th>
                        <th>Total</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commandes as $commande): ?>
                    <tr>
                        <td><?= $commande['id'] ?></td>
                        <td><?= htmlspecialchars($commande['numero_commande']) ?></td>
                        <td><?= htmlspecialchars($commande['client_nom'] . ' ' . $commande['client_prenom']) ?></td>
                        <td><?= date('d/m/Y', strtotime($commande['date_commande'])) ?></td>
                        <td><?= date('d/m/Y', strtotime($commande['date_livraison'])) ?></td>
                        <td><?= number_format($commande['montant_total'], 2) ?> DT</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modifierModal" onclick='chargerCommande(<?= $commande['id'] ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#supprimerModal" onclick="setDeleteId(<?= $commande['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                            <a href="facture.php?commande_id=<?= $commande['id'] ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($commandes)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-2 text-muted">
                            Aucune commande enregistrée
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL AJOUTER COMMANDE -->
<div class="modal fade" id="ajouterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i> Ajouter une commande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="ajouter">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Client *</label>
                            <select class="form-select" name="client_id" required>
                                <option value="">Sélectionnez un client</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['nom'] . ' ' . $client['prenom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date commande *</label>
                            <input type="date" class="form-control" name="date_commande" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date livraison *</label>
                            <input type="date" class="form-control" name="date_livraison" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                        </div>
                    </div>

                    <h6 class="mt-4 mb-3">Produits de la commande</h6>
                    <div id="produits-container">
                        <div class="row g-2 produit-item mb-2">
                            <div class="col-md-5">
                                <select class="form-select" name="produit_id[]" required>
                                    <option value="">Sélectionnez un produit</option>
                                    <?php foreach ($produits as $produit): ?>
                                        <option value="<?= $produit['id'] ?>"><?= htmlspecialchars($produit['nom']) ?> (<?= number_format($produit['prix_vente'], 2) ?>€)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="fournisseur_id[]">
                                    <option value="">Fournisseur</option>
                                    <?php foreach ($fournisseurs as $fournisseur): ?>
                                        <option value="<?= $fournisseur['id'] ?>"><?= htmlspecialchars($fournisseur['nom']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" class="form-control" name="quantite[]" value="1" min="1" required>
                            </div>
                            <div class="col-md-2 text-end">
                                <button type="button" class="btn btn-danger remove-produit">X</button>
                            </div>
                        </div>
                    </div>

                    <button type="button" id="add-produit" class="btn btn-secondary btn-sm mt-2">
                        <i class="fas fa-plus"></i> Ajouter un produit
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL MODIFIER COMMANDE -->
<div class="modal fade" id="modifierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Modifier la commande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="modifier">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Numéro commande</label>
                            <input type="text" class="form-control" id="edit_numero" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Client *</label>
                            <select class="form-select" id="edit_client" name="client_id" required>
                                <option value="">Sélectionnez un client</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['nom'] . ' ' . $client['prenom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date commande *</label>
                            <input type="date" class="form-control" id="edit_date_commande" name="date_commande" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date livraison *</label>
                            <input type="date" class="form-control" id="edit_date_livraison" name="date_livraison" required>
                        </div>
                    </div>
                    <div id="edit-produits-container">
                        <!-- Les produits seront chargés ici par JavaScript -->
                    </div>
                    <button type="button" id="add-produit-edit" class="btn btn-secondary btn-sm mt-2">
                        <i class="fas fa-plus"></i> Ajouter un produit
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL SUPPRIMER COMMANDE -->
<div class="modal fade" id="supprimerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash me-1"></i> Supprimer la commande</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p>Voulez-vous vraiment supprimer cette commande ?</p>
                <input type="hidden" name="action" value="supprimer">
                <input type="hidden" name="id" id="sup_id">
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-danger">Supprimer</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Fonction pour ajouter un champ produit dans le modal d'ajout
document.getElementById('add-produit').onclick = function() {
    const container = document.getElementById('produits-container');
    const template = container.querySelector('.produit-item');
    const clone = template.cloneNode(true);
    
    // Réinitialiser les valeurs
    clone.querySelectorAll('input, select').forEach(input => {
        if (input.tagName === 'SELECT') {
            input.selectedIndex = 0;
        } else {
            input.value = '';
            if (input.name === 'quantite[]') {
                input.value = '1';
            }
        }
    });
    
    container.appendChild(clone);
};

// Fonction pour ajouter un champ produit dans le modal de modification
document.getElementById('add-produit-edit').onclick = function() {
    const container = document.getElementById('edit-produits-container');
    const html = `
    <div class="row g-2 produit-item mb-2">
        <div class="col-md-5">
            <select class="form-select" name="produit_id[]" required>
                <option value="">Sélectionnez un produit</option>
                <?php foreach ($produits as $produit): ?>
                    <option value="<?= $produit['id'] ?>"><?= htmlspecialchars($produit['nom']) ?> (<?= number_format($produit['prix_vente'], 2) ?>€)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" name="fournisseur_id[]">
                <option value="">Fournisseur</option>
                <?php foreach ($fournisseurs as $fournisseur): ?>
                    <option value="<?= $fournisseur['id'] ?>"><?= htmlspecialchars($fournisseur['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" class="form-control" name="quantite[]" value="1" min="1" required>
        </div>
        <div class="col-md-2 text-end">
            <button type="button" class="btn btn-danger remove-produit">X</button>
        </div>
    </div>`;
    
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = html;
    container.appendChild(tempDiv.firstElementChild);
};

// Gérer la suppression des lignes produit
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-produit')) {
        const item = e.target.closest('.produit-item');
        const allItems = document.querySelectorAll('.produit-item');
        if (allItems.length > 1) {
            item.remove();
        }
    }
});

// Fonction pour charger une commande dans le modal de modification
function chargerCommande(commandeId) {
    // Stocker les données PHP en JavaScript pour y accéder
    const commandesData = <?= json_encode($commandes) ?>;
    const commandeProduitsData = <?= json_encode($commandeProduits) ?>;
    const produitsData = <?= json_encode($produits) ?>;
    const fournisseursData = <?= json_encode($fournisseurs) ?>;
    
    // Trouver la commande correspondante
    const commande = commandesData.find(c => c.id == commandeId);
    
    if (!commande) {
        console.error('Commande non trouvée');
        return;
    }
    
    // Remplir les informations de base
    document.getElementById('edit_id').value = commande.id;
    document.getElementById('edit_numero').value = commande.numero_commande;
    document.getElementById('edit_client').value = commande.client_id;
    document.getElementById('edit_date_commande').value = commande.date_commande;
    document.getElementById('edit_date_livraison').value = commande.date_livraison;
    
    // Charger les produits de la commande
    const container = document.getElementById('edit-produits-container');
    container.innerHTML = '';
    
    const produits = commandeProduitsData[commande.id] || [];
    
    if (produits.length > 0) {
        produits.forEach(prod => {
            const html = `
            <div class="row g-2 produit-item mb-2">
                <div class="col-md-5">
                    <select class="form-select" name="produit_id[]" required>
                        <option value="">Sélectionnez un produit</option>
                        ${produitsData.map(p => `
                        <option value="${p.id}" ${prod.produit_id == p.id ? 'selected' : ''}>
                            ${p.nom} (${parseFloat(p.prix_vente).toFixed(2)}DT)
                        </option>
                        `).join('')}
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="fournisseur_id[]">
                        <option value="">Fournisseur</option>
                        ${fournisseursData.map(f => `
                        <option value="${f.id}" ${prod.fournisseur_id == f.id ? 'selected' : ''}>
                            ${f.nom}
                        </option>
                        `).join('')}
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control" name="quantite[]" value="${prod.quantite}" min="1" required>
                </div>
                <div class="col-md-2 text-end">
                    <button type="button" class="btn btn-danger remove-produit">X</button>
                </div>
            </div>`;
            
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            container.appendChild(tempDiv.firstElementChild);
        });
    } else {
        // Si pas de produits, ajouter une ligne vide
        document.getElementById('add-produit-edit').click();
    }
}

// Fonction pour définir l'ID à supprimer
function setDeleteId(id) {
    document.getElementById('sup_id').value = id;
}
</script>
</body>
</html>