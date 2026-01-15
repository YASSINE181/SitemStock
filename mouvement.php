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
            $idProduit = $_POST['id_produit'];
            $type = $_POST['type'];
            $quantite = (int)$_POST['quantite'];
            $date = $_POST['date_mouvement'];

            try {
                $db->beginTransaction();
                
                // Insérer le mouvement
                $stmt = $db->prepare(
                    "INSERT INTO mouvements (id_produit, type, quantite, date_mouvement)
                     VALUES (?, ?, ?, ?)"
                );
                $stmt->execute([$idProduit, $type, $quantite, $date]);

                // Mettre à jour le stock
                if ($type === 'entree') {
                    $stmt = $db->prepare(
                        "UPDATE produit 
                         SET quantite_stock = quantite_stock + ? 
                         WHERE id = ?"
                    );
                } else {
                    $stmt = $db->prepare(
                        "UPDATE produit 
                         SET quantite_stock = quantite_stock - ? 
                         WHERE id = ?"
                    );
                }
                $stmt->execute([$quantite, $idProduit]);
                
                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
            }
            break;

        case 'modifier':
            $id = $_POST['id'];
            $idProduit = $_POST['id_produit'];
            $type = $_POST['type'];
            $quantite = (int)$_POST['quantite'];
            $date = $_POST['date_mouvement'];

            try {
                $db->beginTransaction();
                
                // Récupérer l'ancien mouvement
                $stmt = $db->prepare("SELECT * FROM mouvements WHERE id = ?");
                $stmt->execute([$id]);
                $ancien = $stmt->fetch(PDO::FETCH_ASSOC);

                // Annuler ancien impact
                if ($ancien['type'] === 'entree') {
                    $stmt = $db->prepare(
                        "UPDATE produit 
                         SET quantite_stock = quantite_stock - ? 
                         WHERE id = ?"
                    );
                    $stmt->execute([$ancien['quantite'], $ancien['id_produit']]);
                } else {
                    $stmt = $db->prepare(
                        "UPDATE produit 
                         SET quantite_stock = quantite_stock + ? 
                         WHERE id = ?"
                    );
                    $stmt->execute([$ancien['quantite'], $ancien['id_produit']]);
                }

                // Mettre à jour le mouvement
                $stmt = $db->prepare(
                    "UPDATE mouvements 
                     SET id_produit=?, type=?, quantite=?, date_mouvement=?
                     WHERE id=?"
                );
                $stmt->execute([$idProduit, $type, $quantite, $date, $id]);

                // Appliquer nouveau impact
                if ($type === 'entree') {
                    $stmt = $db->prepare(
                        "UPDATE produit 
                         SET quantite_stock = quantite_stock + ? 
                         WHERE id = ?"
                    );
                    $stmt->execute([$quantite, $idProduit]);
                } else {
                    $stmt = $db->prepare(
                        "UPDATE produit 
                         SET quantite_stock = quantite_stock - ? 
                         WHERE id = ?"
                    );
                    $stmt->execute([$quantite, $idProduit]);
                }
                
                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
            }
            break;

        case 'supprimer':
            $id = $_POST['id'];

            try {
                $db->beginTransaction();
                
                // Récupérer le mouvement
                $stmt = $db->prepare("SELECT * FROM mouvements WHERE id = ?");
                $stmt->execute([$id]);
                $m = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($m) {
                    // Annuler l'impact sur le stock
                    if ($m['type'] === 'entree') {
                        $stmt = $db->prepare(
                            "UPDATE produit 
                             SET quantite_stock = quantite_stock - ? 
                             WHERE id = ?"
                        );
                        $stmt->execute([$m['quantite'], $m['id_produit']]);
                    } else {
                        $stmt = $db->prepare(
                            "UPDATE produit 
                             SET quantite_stock = quantite_stock + ? 
                             WHERE id = ?"
                        );
                        $stmt->execute([$m['quantite'], $m['id_produit']]);
                    }

                    // Supprimer le mouvement
                    $stmt = $db->prepare("DELETE FROM mouvements WHERE id = ?");
                    $stmt->execute([$id]);
                }
                
                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
            }
            break;
    }
    header("Location: mouvement.php");
    exit;
}

// Récupérer mouvements avec les noms des produits
$mouvements = $db->query("
    SELECT m.*, p.nom as produit_nom 
    FROM mouvements m
    LEFT JOIN produit p ON p.id = m.id_produit
    ORDER BY m.date_mouvement DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer produits pour les listes déroulantes
$produits = $db->query("SELECT id, nom FROM produit ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion des Mouvements - SitemStock</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="acceuil.css">
<link rel="stylesheet" href="gestionstock.css">
</head>
<body>
<div class="main-content">
    <?php include 'sidebar.php'; ?>
    <!-- WELCOME -->
    <div class="welcome-section">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2><i class="fas fa-exchange-alt me-2"></i> Gestion des Mouvements</h2>
                <p class="mb-0">Gérez les entrées et sorties de stock.</p>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajouterModal">
                    <i class="fas fa-plus me-1"></i> Ajouter un mouvement
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
    
    <!-- TABLE MOUVEMENTS -->
    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Liste des mouvements (<?= count($mouvements); ?>)</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Produit</th>
                        <th>Type</th>
                        <th>Quantité</th>
                        <th>Date</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mouvements as $mouvement): ?>
                    <tr>
                        <td><?= $mouvement['id'] ?></td>
                        <td><?= htmlspecialchars($mouvement['produit_nom']) ?></td>
                        <td>
                            <?php if ($mouvement['type'] === 'entree'): ?>
                                <span class="badge bg-success">Entrée</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Sortie</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($mouvement['quantite']) ?></td>
                        <td><?= date('d/m/Y', strtotime($mouvement['date_mouvement'])) ?></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modifierModal" onclick='chargerMouvement(<?= json_encode($mouvement) ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#supprimerModal" onclick="setDeleteId(<?= $mouvement['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($mouvements)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-2 text-muted">
                            Aucun mouvement enregistré
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL AJOUTER MOUVEMENT -->
<div class="modal fade" id="ajouterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i> Ajouter un mouvement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="ajouter">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Produit *</label>
                            <select class="form-select" name="id_produit" required>
                                <option value="">Sélectionnez un produit</option>
                                <?php foreach ($produits as $produit): ?>
                                    <option value="<?= $produit['id'] ?>"><?= htmlspecialchars($produit['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type *</label>
                            <select class="form-select" name="type" required>
                                <option value="entree">Entrée de stock</option>
                                <option value="sortie">Sortie de stock</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quantité *</label>
                            <input type="number" class="form-control" name="quantite" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date *</label>
                            <input type="date" class="form-control" name="date_mouvement" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL MODIFIER MOUVEMENT -->
<div class="modal fade" id="modifierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Modifier le mouvement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="modifier">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Produit *</label>
                            <select class="form-select" name="id_produit" id="edit_id_produit" required>
                                <option value="">Sélectionnez un produit</option>
                                <?php foreach ($produits as $produit): ?>
                                    <option value="<?= $produit['id'] ?>"><?= htmlspecialchars($produit['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type *</label>
                            <select class="form-select" name="type" id="edit_type" required>
                                <option value="entree">Entrée de stock</option>
                                <option value="sortie">Sortie de stock</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quantité *</label>
                            <input type="number" class="form-control" name="quantite" id="edit_quantite" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date *</label>
                            <input type="date" class="form-control" name="date_mouvement" id="edit_date_mouvement" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL SUPPRIMER MOUVEMENT -->
<div class="modal fade" id="supprimerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash me-1"></i> Supprimer le mouvement</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p>Voulez-vous vraiment supprimer ce mouvement ?</p>
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
function chargerMouvement(mouvement){
    document.getElementById('edit_id').value = mouvement.id;
    document.getElementById('edit_id_produit').value = mouvement.id_produit;
    document.getElementById('edit_type').value = mouvement.type;
    document.getElementById('edit_quantite').value = mouvement.quantite;
    document.getElementById('edit_date_mouvement').value = mouvement.date_mouvement;
}

// Fonction pour ouvrir le modal de suppression et passer l'id
function setDeleteId(id){
    document.getElementById('sup_id').value = id;
}
</script>
</body>
</html>