<?php
session_start();

/* ===== SÉCURITÉ ===== */
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/* ===== CONNEXION DB ===== */
$db = new PDO("mysql:host=localhost;dbname=sitemstock", 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ===== UTILISATEUR CONNECTÉ ===== */
$req = $db->prepare("SELECT nom, email FROM utilisateur WHERE id = ?");
$req->execute([$_SESSION['user_id']]);
$user = $req->fetch(PDO::FETCH_ASSOC);

/* ===== ACTIONS ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    /* ===== AJOUT (etat = 1) ===== */
   if ($_POST['action'] === 'ajouter') {
    $stmt = $db->prepare(
        "INSERT INTO fournisseur (nom, nomLivreur, telephone, email, adresse, etat)
         VALUES (?, ?, ?, ?, ?, '1')"
    );
    $stmt->execute([
        $_POST['nom'],
        $_POST['nomLivreur'],
        $_POST['telephone'],
        $_POST['email'],
        $_POST['adresse']
    ]);
}

    /* ===== MODIFIER ===== */
    if ($_POST['action'] === 'modifier') {
        $stmt = $db->prepare(
            "UPDATE fournisseur
             SET nom=?, nomLivreur=?, telephone=?, email=?, adresse=?
             WHERE id=?"
        );
        $stmt->execute([
            $_POST['nom'],
            $_POST['nomLivreur'],
            $_POST['telephone'],
            $_POST['email'],
            $_POST['adresse'],
            $_POST['id']
        ]);
    }

    /* ===== SUPPRESSION LOGIQUE (etat = 0) ===== */
    if ($_POST['action'] === 'supprimer') {
        $stmt = $db->prepare(
            "UPDATE fournisseur SET etat = '0' WHERE id = ?"
        );
        $stmt->execute([$_POST['id']]);
    }

    header("Location: fournisseur.php");
    exit;
}

/* ===== FOURNISSEURS ACTIFS ===== */
$fournisseurs = $db->query(
    "SELECT * FROM fournisseur WHERE etat = '1' ORDER BY id DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gestion des Fournisseurs - SitemStock</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="sidebar.css">
</head>

<body>

<div class="main-content">
<?php include "sidebar.php"; ?>

<!-- ===== WELCOME ===== -->
<div class="welcome-section mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2><i class="fas fa-truck me-2"></i> Gestion des Fournisseurs</h2>
            <p class="mb-0">Gérez l'ensemble des fournisseurs et leurs informations.</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAjouter">
                <i class="fas fa-plus me-1"></i> Ajouter un fournisseur
            </button>
        </div>
    </div>
</div>

<!-- ===== TABLE ===== -->
<div class="table-container">
<table class="table table-hover align-middle">
<thead class="table-dark">
<tr>
    <th>Nom</th>
    <th>Livreur</th>
    <th>Téléphone</th>
    <th>Email</th>
    <th>Adresse</th>
    <th class="text-center">Actions</th>
</tr>
</thead>
<tbody>

<?php if (!empty($fournisseurs)): ?>
<?php foreach ($fournisseurs as $f): ?>
<tr>
    <td><?= htmlspecialchars($f['nom']) ?></td>
    <td><?= htmlspecialchars($f['nomLivreur']) ?></td>
    <td><?= htmlspecialchars($f['telephone']) ?></td>
    <td><?= htmlspecialchars($f['email']) ?></td>
    <td><?= htmlspecialchars($f['adresse']) ?></td>
    <td class="text-center">
        <button class="btn btn-sm btn-outline-primary me-1"
            onclick='ouvrirModifierModal(<?= json_encode($f) ?>)'>
            <i class="fas fa-edit"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger"
            onclick="ouvrirSupprimerModal(<?= $f['id'] ?>)">
            <i class="fas fa-trash"></i>
        </button>
    </td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr>
    <td colspan="6" class="text-center text-muted py-4">
        Aucun fournisseur
    </td>
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
<div class="modal-header">
    <h5 class="modal-title"><i class="fas fa-plus me-2"></i> Ajouter fournisseur</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <input type="hidden" name="action" value="ajouter">
    <input class="form-control mb-2" name="nom" placeholder="Nom" required>
    <input class="form-control mb-2" name="nomLivreur" placeholder="Nom livreur" required>
    <input class="form-control mb-2" name="telephone" placeholder="Téléphone" required>
    <input class="form-control mb-2" name="email" type="email" placeholder="Email" required>
    <input class="form-control" name="adresse" placeholder="Adresse" required>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
    <button type="submit" class="btn btn-primary">Ajouter</button>
</div>
</form>
</div>
</div>

<!-- MODIFIER -->
<div class="modal fade" id="modalModifier">
<div class="modal-dialog">
<form method="post" class="modal-content">
<div class="modal-header">
    <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Modifier fournisseur</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <input type="hidden" name="action" value="modifier">
    <input type="hidden" name="id" id="edit-id">
    <input class="form-control mb-2" name="nom" id="edit-nom" required>
    <input class="form-control mb-2" name="nomLivreur" id="edit-nomLivreur" required>
    <input class="form-control mb-2" name="telephone" id="edit-telephone" required>
    <input class="form-control mb-2" name="email" id="edit-email" required>
    <input class="form-control" name="adresse" id="edit-adresse" required>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
    <button type="submit" class="btn btn-primary">Enregistrer</button>
</div>
</form>
</div>
</div>

<!-- SUPPRIMER -->
<div class="modal fade" id="modalSupprimer">
<div class="modal-dialog modal-dialog-centered">
<form method="post" class="modal-content">
<div class="modal-header bg-danger text-white">
    <h5 class="modal-title"><i class="fas fa-trash me-1"></i> Supprimer fournisseur</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body text-center">
    <p>Voulez-vous vraiment supprimer ce fournisseur ?</p>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function ouvrirModifierModal(f) {
    document.getElementById('edit-id').value = f.id;
    document.getElementById('edit-nom').value = f.nom;
    document.getElementById('edit-nomLivreur').value = f.nomLivreur;
    document.getElementById('edit-telephone').value = f.telephone;
    document.getElementById('edit-email').value = f.email;
    document.getElementById('edit-adresse').value = f.adresse;
    new bootstrap.Modal(document.getElementById('modalModifier')).show();
}

function ouvrirSupprimerModal(id) {
    document.getElementById('delete-id').value = id;
    new bootstrap.Modal(document.getElementById('modalSupprimer')).show();
}
</script>

</body>
</html>
