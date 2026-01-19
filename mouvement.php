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
$mouvements = $db->query("SELECT m.*, p.nom as produit_nom 
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
<link rel="stylesheet" href="sidebar.css">
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

    <!-- ================= TABLE ================= -->
    <div class="table-container mt-3">
        <table class="table table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Produit</th>
                    <th>Type</th>
                    <th>Quantité</th>
                    <th>Date</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($mouvements): ?>
                <?php foreach ($mouvements as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['produit_nom']) ?></td>
                        <td>
                           <span class="badge <?= $m['type']=='entree'?'bg-success':'bg-danger' ?>">
                            <?= $m['type']=='entree'?'Entrée':'Sortie' ?>
                            </span>
                        </td>
                        <td><?= $m['quantite'] ?></td>
                        <td><?= $m['date_mouvement'] ?></td>
                        <td class="text-center">
                            <button class="btn btn-primary btn-action"
                                onclick='ouvrirModal(
                                    <?= $m["id"] ?>,
                                    <?= $m["id_produit"] ?>,
                                    "<?= $m["type"] ?>",
                                    <?= $m["quantite"] ?>,
                                    "<?= $m["date_mouvement"] ?>")'>
                                <i class="fas fa-edit"></i>
                            </button>

                            <button class="btn btn-danger btn-action"
                                onclick="ouvrirModalSupp(<?= $m['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">Aucun mouvement</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ================= MODALS ================= -->

<!-- AJOUT -->
<div class="modal fade" id="modalAjouter">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <input type="hidden" name="action" value="ajouter">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-1"></i> Ajouter mouvement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <select name="id_produit" class="form-select mb-2" required>
                    <option value="">Produit</option>
                    <?php foreach ($produits as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?></option>
                    <?php endforeach; ?>
                </select>

               <select name="type" class="form-select mb-2" required>
                    <option value="entree">Entrée</option>
                    <option value="sortie">Sortie</option>
                </select>


                <input class="form-control mb-2" type="number" name="quantite" min="1" required>
                <input class="form-control" type="date" name="date_mouvement" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-success">Ajouter</button>
            </div>
        </form>
    </div>
</div>

<!-- MODIFIER -->
<div class="modal fade" id="modalModifier">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <input type="hidden" name="action" value="modifier">
            <input type="hidden" name="id" id="edit-id">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-1"></i> Modifier mouvement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <select name="id_produit" id="edit-produit" class="form-select mb-2" required>
                    <?php foreach ($produits as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="type" id="edit-type" class="form-select mb-2" required>
                     <option value="entree">Entrée</option>
                     <option value="sortie">Sortie</option>
                </select>


                <input class="form-control mb-2" id="edit-quantite" name="quantite" type="number" min="1" required>
                <input class="form-control" id="edit-date" name="date_mouvement" type="date" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- SUPPRIMER -->
<div class="modal fade" id="modalSupp" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash me-1"></i> Supprimer mouvement</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center">
                <p>Voulez-vous vraiment supprimer ce mouvement ?</p>
                <input type="hidden" name="action" value="supprimer">
                <input type="hidden" name="id" id="delete-id">
            </div>

            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-danger">Supprimer</button>
            </div>
        </form>
    </div>
</div>

<script>
function ouvrirAjouterModal() {
    new bootstrap.Modal(document.getElementById('modalAjouter')).show();
}

function ouvrirModal(id, produit, type, qte, date) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-produit').value = produit;
    document.getElementById('edit-type').value = type;
    document.getElementById('edit-quantite').value = qte;
    document.getElementById('edit-date').value = date;
    new bootstrap.Modal(document.getElementById('modalModifier')).show();
}

function ouvrirModalSupp(id) {
    document.getElementById('delete-id').value = id;
    new bootstrap.Modal(document.getElementById('modalSupp')).show();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- MODAL MESSAGE -->
<div class="modal fade" id="modalMessage" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center">
            <div class="modal-body py-4">
                <h5 class="mb-0">
                    <?= htmlspecialchars($message) ?>
                </h5>
            </div>
        </div>
    </div>
</div>
<?php if (!empty($message)): ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    let modal = new bootstrap.Modal(document.getElementById('modalMessage'));
    modal.show();

    // fermer après 2 secondes
    setTimeout(() => {
        modal.hide();
    }, 2000);
});
</script>
<?php endif; ?>

</body>
</html>
