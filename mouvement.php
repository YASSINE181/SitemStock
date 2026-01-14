<?php
require "config.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$message = "";

/* ================= TRAITEMENT ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    /* ========= AJOUT ========= */
    if ($_POST['action'] === 'ajouter') {

        $idProduit = $_POST['id_produit'];
        $type = $_POST['type'];
        $quantite = (int) $_POST['quantite'];
        $date = $_POST['date_mouvement'];

        // Insert mouvement
        $pdo->prepare(
            "INSERT INTO mouvements (id_produit, type, quantite, date_mouvement)
             VALUES (?,?,?,?)"
        )->execute([$idProduit, $type, $quantite, $date]);

        // Update stock 
        if ($type === 'entree') {
            $pdo->prepare(
                "UPDATE produit 
                 SET quantite_stock = quantite_stock + ? 
                 WHERE id = ?"
            )->execute([$quantite, $idProduit]);
        } else {
            $pdo->prepare(
                "UPDATE produit 
                 SET quantite_stock = quantite_stock - ? 
                 WHERE id = ?"
            )->execute([$quantite, $idProduit]);
        }

        $_SESSION['flash_message'] = "✅ Mouvement ajouté";
        header("Location: mouvement.php");
        exit;
    }

    /* ========= MODIFIER ========= */
    if ($_POST['action'] === 'modifier') {

        $id = $_POST['id'];
        $idProduit = $_POST['id_produit'];
        $type = $_POST['type'];
        $quantite = (int) $_POST['quantite'];
        $date = $_POST['date_mouvement'];

        // Ancien mouvement
        $stmt = $pdo->prepare("SELECT * FROM mouvements WHERE id=?");
        $stmt->execute([$id]);
        $ancien = $stmt->fetch(PDO::FETCH_ASSOC);

        // Annuler ancien impact
        if ($ancien['type'] === 'entree') {
            $pdo->prepare(
                "UPDATE produit 
                 SET quantite_stock = quantite_stock - ? 
                 WHERE id = ?"
            )->execute([$ancien['quantite'], $ancien['id_produit']]);
        } else {
            $pdo->prepare(
                "UPDATE produit 
                 SET quantite_stock = quantite_stock + ? 
                 WHERE id = ?"
            )->execute([$ancien['quantite'], $ancien['id_produit']]);
        }

        // Update mouvement
        $pdo->prepare(
            "UPDATE mouvements 
             SET id_produit=?, type=?, quantite=?, date_mouvement=?
             WHERE id=?"
        )->execute([$idProduit, $type, $quantite, $date, $id]);

        // Appliquer nouveau impact
        if ($type === 'entree') {
            $pdo->prepare(
                "UPDATE produit 
                 SET quantite_stock = quantite_stock + ? 
                 WHERE id = ?"
            )->execute([$quantite, $idProduit]);
        } else {
            $pdo->prepare(
                "UPDATE produit 
                 SET quantite_stock = quantite_stock - ? 
                 WHERE id = ?"
            )->execute([$quantite, $idProduit]);
        }

        $_SESSION['flash_message'] = "✏️ Mouvement modifié";
        header("Location: mouvement.php");
        exit;
    }

    /* ========= SUPPRIMER ========= */
    if ($_POST['action'] === 'supprimer') {

        $id = $_POST['id'];

        $stmt = $pdo->prepare("SELECT * FROM mouvements WHERE id=?");
        $stmt->execute([$id]);
        $m = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($m) {
            if ($m['type'] === 'entree') {
                $pdo->prepare(
                    "UPDATE produit 
                     SET quantite_stock = quantite_stock - ? 
                     WHERE id = ?"
                )->execute([$m['quantite'], $m['id_produit']]);
            } else {
                $pdo->prepare(
                    "UPDATE produit 
                     SET quantite_stock = quantite_stock + ? 
                     WHERE id = ?"
                )->execute([$m['quantite'], $m['id_produit']]);
            }

            $pdo->prepare("DELETE FROM mouvements WHERE id=?")->execute([$id]);
        }

        $_SESSION['flash_message'] = "🗑️ Mouvement supprimé";
        header("Location: mouvement.php");
        exit;
    }
}


/* ================= DONNÉES ================= */
$mouvements = $pdo->query(
    "SELECT m.*, p.nom AS nom_produit 
     FROM mouvements m
     LEFT JOIN produit p ON p.id = m.id_produit
     ORDER BY m.date_mouvement DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$produits = $pdo->query("SELECT id, nom FROM produit")->fetchAll(PDO::FETCH_ASSOC);
$message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mouvements - SiteMStock</title>

    <!-- CSS -->
    <link rel="stylesheet" href="sidebar.css">

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>


<div class="main-content">

    <?php include 'sidebar.php'; ?>


   

    <!-- ================= WELCOME ================= -->
    <div class="welcome-section mt-3">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2><i class="fas fa-exchange-alt me-2"></i> Mouvements de stock</h2>
                <p class="mb-0">Entrées et sorties des produits</p>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-primary" onclick="ouvrirAjouterModal()">
                    <i class="fas fa-plus me-1"></i> Ajouter
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
                        <td><?= htmlspecialchars($m['nom_produit']) ?></td>
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
