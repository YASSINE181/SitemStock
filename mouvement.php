<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Connexion DB
$db = new PDO("mysql:host=localhost;dbname=sitemstock;charset=utf8", 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Récupérer l'utilisateur connecté
$req = $db->prepare("SELECT nom, email FROM utilisateur WHERE id = ?");
$req->execute([$_SESSION['user_id']]);
$user = $req->fetch(PDO::FETCH_ASSOC);

// Vérifier si la table produit existe
$produitTableExists = $db->query("SHOW TABLES LIKE 'produit'")->rowCount() > 0;
$produits = $produitTableExists ? $db->query("SELECT id, nom, quantite_stock FROM produit ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC) : [];

// Gérer les actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id_produit = $_POST['id_produit'] ?? null;
    $type = $_POST['type'] ?? '';
    $quantite = (int)($_POST['quantite'] ?? 0);
    $date_mouvement = $_POST['date_mouvement'] ?? null;

    // Vérifier que la quantité est positive pour ajouter/modifier
    if (in_array($_POST['action'], ['ajouter','modifier']) && $quantite <= 0) {
        $_SESSION['error'] = "La quantité doit être supérieure à 0";
        header("Location: mouvement.php");
        exit;
    }

    switch ($_POST['action']) {
        case 'ajouter':
            // Récupérer le stock actuel
            $stmtProduit = $db->prepare("SELECT quantite_stock FROM produit WHERE id=?");
            $stmtProduit->execute([$id_produit]);
            $produit = $stmtProduit->fetch(PDO::FETCH_ASSOC);
            if (!$produit) {
                $_SESSION['error'] = "Produit introuvable";
                header("Location: mouvement.php");
                exit;
            }
            $stockActuel = $produit['quantite_stock'];

            // Insérer le mouvement
            $stmt = $db->prepare("INSERT INTO mouvements (id_produit, type, quantite, date_mouvement) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id_produit, $type, $quantite, $date_mouvement]);

            // Mettre à jour le stock
            $nouveauStock = ($type === 'entree') ? $stockActuel + $quantite : $stockActuel - $quantite;
            $stmt = $db->prepare("UPDATE produit SET quantite_stock=? WHERE id=?");
            $stmt->execute([$nouveauStock, $id_produit]);
            break;

        case 'modifier':
            $id_mouvement = $_POST['id'];

            // Récupérer l'ancien mouvement
            $stmtOld = $db->prepare("SELECT * FROM mouvements WHERE id=?");
            $stmtOld->execute([$id_mouvement]);
            $old = $stmtOld->fetch(PDO::FETCH_ASSOC);

            if (!$old) {
                $_SESSION['error'] = "Mouvement introuvable";
                header("Location: mouvement.php");
                exit;
            }

            // Récupérer le stock actuel du produit
            $stmtProduit = $db->prepare("SELECT quantite_stock FROM produit WHERE id=?");
            $stmtProduit->execute([$id_produit]);
            $produit = $stmtProduit->fetch(PDO::FETCH_ASSOC);
            $stockActuel = $produit['quantite_stock'];

            // Annuler l'effet de l'ancien mouvement
            $stockAprèsAnnulation = ($old['type'] === 'entree') ? $stockActuel - $old['quantite'] : $stockActuel + $old['quantite'];

            // Mettre à jour le mouvement
            $stmt = $db->prepare("UPDATE mouvements SET id_produit=?, type=?, quantite=?, date_mouvement=? WHERE id=?");
            $stmt->execute([$id_produit, $type, $quantite, $date_mouvement, $id_mouvement]);

            // Appliquer le nouveau mouvement
            $nouveauStock = ($type === 'entree') ? $stockAprèsAnnulation + $quantite : $stockAprèsAnnulation - $quantite;
            $stmt = $db->prepare("UPDATE produit SET quantite_stock=? WHERE id=?");
            $stmt->execute([$nouveauStock, $id_produit]);
            break;

        case 'supprimer':
            $id_mouvement = $_POST['id'];

            // Récupérer le mouvement
            $stmtOld = $db->prepare("SELECT * FROM mouvements WHERE id=?");
            $stmtOld->execute([$id_mouvement]);
            $old = $stmtOld->fetch(PDO::FETCH_ASSOC);

            if ($old) {
                // Récupérer le stock actuel du produit
                $stmtProduit = $db->prepare("SELECT quantite_stock FROM produit WHERE id=?");
                $stmtProduit->execute([$old['id_produit']]);
                $stockProduit = $stmtProduit->fetchColumn();

                // Annuler l'effet du mouvement
                $nouveauStock = ($old['type'] === 'entree') 
                    ? $stockProduit - $old['quantite'] 
                    : $stockProduit + $old['quantite'];

                // Mettre à jour le stock
                $stmtUpdate = $db->prepare("UPDATE produit SET quantite_stock=? WHERE id=?");
                $stmtUpdate->execute([$nouveauStock, $old['id_produit']]);
            }

            // Supprimer le mouvement
            $stmtDel = $db->prepare("DELETE FROM mouvements WHERE id=?");
            $stmtDel->execute([$id_mouvement]);
            break;
    }

    header("Location: mouvement.php");
    exit;
}

// Récupérer les mouvements
$mouvements = $produitTableExists 
    ? $db->query("SELECT m.*, p.nom AS produit_nom FROM mouvements m LEFT JOIN produit p ON m.id_produit = p.id ORDER BY m.date_mouvement DESC, m.id DESC")->fetchAll(PDO::FETCH_ASSOC)
    : $db->query("SELECT * FROM mouvements ORDER BY date_mouvement DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mouvements - SitemStock</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="sidebar.css">
<style>
.badge-type { padding: 5px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 500; }
.badge-entree { background-color: #d1fae5; color: #065f46; }
.badge-sortie { background-color: #fee2e2; color: #991b1b; }
</style>
</head>
<body>
<div class="main-content">
<?php include 'sidebar.php'; ?>

<div class="welcome-section">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2><i class="fas fa-exchange-alt me-2"></i> Gestion des Mouvements</h2>
            <p>Entrées et sorties de produits du stock.</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajouterModal">
                <i class="fas fa-plus me-1"></i> Ajouter un mouvement
            </button>
        </div>
    </div>
</div>

<?php if(isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show m-3"><?= $_SESSION['error']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error']); endif; ?>

<div class="table-container p-3">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th><th>Produit</th><th>Type</th><th>Quantité</th><th>Date</th><th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($mouvements as $m): ?>
                <tr>
                    <td><?= $m['id'] ?></td>
                    <td><?= $produitTableExists ? htmlspecialchars($m['produit_nom']) : $m['id_produit'] ?></td>
                    <td>
                        <span class="badge-type <?= $m['type']=='entree'?'badge-entree':'badge-sortie' ?>">
                            <?= ucfirst($m['type']) ?>
                        </span>
                    </td>
                    <td><?= ($m['type']=='entree'?'+':'-') . $m['quantite'] ?></td>
                    <td><?= date('d/m/Y', strtotime($m['date_mouvement'])) ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modifierModal" onclick='chargerMouvement(<?= json_encode($m) ?>)'><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#supprimerModal" onclick="document.getElementById('sup_id').value=<?= $m['id'] ?>;"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($mouvements)): ?>
                <tr><td colspan="6" class="text-center">Aucun mouvement enregistré</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modals : Ajouter / Modifier / Supprimer (inchangés, juste intégrés ci-dessous) -->
<!-- Modal Ajouter -->
<div class="modal fade" id="ajouterModal" tabindex="-1"><div class="modal-dialog">
<form method="POST" class="modal-content">
<div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus me-1"></i> Ajouter</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<input type="hidden" name="action" value="ajouter">
<div class="mb-3">
    <label>Produit *</label>
    <select class="form-select" name="id_produit" required>
        <option value="">-- Sélectionner --</option>
        <?php foreach($produits as $p): ?>
        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="mb-3">
    <label>Type *</label>
    <select class="form-select" name="type" required>
        <option value="entree">Entrée</option>
        <option value="sortie">Sortie</option>
    </select>
</div>
<div class="mb-3">
    <label>Quantité *</label>
    <input type="number" name="quantite" class="form-control" min="1" required>
</div>
<div class="mb-3">
    <label>Date *</label>
    <input type="date" name="date_mouvement" class="form-control" required value="<?= date('Y-m-d') ?>">
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
<button type="submit" class="btn btn-primary">Enregistrer</button>
</div>
</form></div></div>

<!-- Modal Modifier -->
<div class="modal fade" id="modifierModal" tabindex="-1"><div class="modal-dialog">
<form method="POST" class="modal-content">
<div class="modal-header"><h5 class="modal-title"><i class="fas fa-edit me-1"></i> Modifier</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<input type="hidden" name="action" value="modifier">
<input type="hidden" name="id" id="edit_id">
<div class="mb-3">
    <label>Produit *</label>
    <select class="form-select" name="id_produit" id="edit_id_produit" required>
        <?php foreach($produits as $p): ?>
        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="mb-3">
    <label>Type *</label>
    <select class="form-select" name="type" id="edit_type" required>
        <option value="entree">Entrée</option>
        <option value="sortie">Sortie</option>
    </select>
</div>
<div class="mb-3">
    <label>Quantité *</label>
    <input type="number" name="quantite" id="edit_quantite" class="form-control" min="1" required>
</div>
<div class="mb-3">
    <label>Date *</label>
    <input type="date" name="date_mouvement" id="edit_date_mouvement" class="form-control" required>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
<button type="submit" class="btn btn-primary">Mettre à jour</button>
</div>
</form></div></div>

<!-- Modal Supprimer -->
<div class="modal fade" id="supprimerModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered">
<form method="POST" class="modal-content">
<div class="modal-header bg-danger text-white">
<h5 class="modal-title"><i class="fas fa-trash me-1"></i> Supprimer</h5>
<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body text-center">
<p>Voulez-vous supprimer ce mouvement ?</p>
<input type="hidden" name="action" value="supprimer">
<input type="hidden" name="id" id="sup_id">
</div>
<div class="modal-footer justify-content-center">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
<button type="submit" class="btn btn-danger">Supprimer</button>
</div>
</form></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function chargerMouvement(m){
    document.getElementById('edit_id').value = m.id;
    document.getElementById('edit_id_produit').value = m.id_produit;
    document.getElementById('edit_type').value = m.type;
    document.getElementById('edit_quantite').value = m.quantite;
    const d = new Date(m.date_mouvement);
    document.getElementById('edit_date_mouvement').value = d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
}
</script>
</body>
</html>
