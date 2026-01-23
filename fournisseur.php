<?php
session_start();
require "config.php";

/* ===== SÉCURITÉ ===== */
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/* ===== UTILISATEUR CONNECTÉ ===== */
$req = $pdo->prepare("SELECT nom, email FROM utilisateur WHERE id = ?");
$req->execute([$_SESSION['user_id']]);
$user = $req->fetch(PDO::FETCH_ASSOC);

/* ===== FONCTION DE VALIDATION EMAIL ===== */
function validerEmail($email) {
    // Pattern regex pour validation email
    $pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    return preg_match($pattern, $email);
}

/* ===== ACTIONS ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $erreur = '';

    /* ===== AJOUT (etat = 1) ===== */
    if ($_POST['action'] === 'ajouter') {
        // Validation email
        if (!validerEmail($_POST['email'])) {
            $_SESSION['error'] = "Format d'email invalide. Exemple : exemple@domaine.com";
        } else {
            $stmt = $pdo->prepare(
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
    }

    /* ===== MODIFIER ===== */
    if ($_POST['action'] === 'modifier') {
        // Validation email
        if (!validerEmail($_POST['email'])) {
            $_SESSION['error'] = "Format d'email invalide. Exemple : exemple@domaine.com";
        } else {
            $stmt = $pdo->prepare(
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
    }

    /* ===== SUPPRESSION LOGIQUE (etat = 0) ===== */
    if ($_POST['action'] === 'supprimer') {
        $stmt = $pdo->prepare(
            "UPDATE fournisseur SET etat = '0' WHERE id = ?"
        );
        $stmt->execute([$_POST['id']]);
    }

    header("Location: fournisseur.php");
    exit;
}

/* ===== FOURNISSEURS ACTIFS ===== */
$fournisseurs = $pdo->query(
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

<style>
    /* Style pour espacer téléphone et email */
    .telephone-cell, .email-cell {
        padding-left: 15px !important;
    }
    
    /* Pour rendre les boutons horizontaux */
    .actions-cell {
        white-space: nowrap; /* Empêche le retour à la ligne */
    }
    
    .actions-buttons {
        display: inline-flex;
        gap: 5px; /* Espace entre les boutons */
    }
</style>
</head>

<body>

<div class="main-content">
<?php include "sidebar.php"; ?>

<!-- ===== MESSAGES D'ALERTE ===== -->
<?php if (isset($_SESSION['message'])): ?>
<div class="alert alert-success alert-dismissible fade show m-3" role="alert">
    <i class="fas fa-check-circle me-2"></i>
    <?= htmlspecialchars($_SESSION['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php unset($_SESSION['message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <?= htmlspecialchars($_SESSION['error']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

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
    <td class="telephone-cell"><?= htmlspecialchars($f['telephone']) ?></td>
    <td class="email-cell"><?= htmlspecialchars($f['email']) ?></td>
    <td><?= htmlspecialchars($f['adresse']) ?></td>
    <td class="text-center actions-cell">
        <div class="actions-buttons">
            <button class="btn btn-sm btn-outline-primary"
                onclick='ouvrirModifierModal(<?= json_encode($f) ?>)'>
                <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger"
                onclick="ouvrirSupprimerModal(<?= $f['id'] ?>)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
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
<form method="post" class="modal-content" onsubmit="return validerEmail('ajouter')">
<div class="modal-header">
    <h5 class="modal-title"><i class="fas fa-plus me-2"></i> Ajouter fournisseur</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <input type="hidden" name="action" value="ajouter">
    
    <div class="mb-2">
        <input class="form-control" name="nom" placeholder="Nom" required>
    </div>
    
    <div class="mb-2">
        <input class="form-control" name="nomLivreur" placeholder="Nom livreur" required>
    </div>
    
    <div class="mb-2">
        <input class="form-control" name="telephone" placeholder="Téléphone" required>
    </div>
    
    <div class="mb-2">
        <input class="form-control" 
               name="email" 
               type="email" 
               id="email-ajouter"
               placeholder="Email" 
               pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}"
               title="Format : exemple@domaine.com" 
               required>
        <div class="invalid-feedback" id="email-error-ajouter">
            Format d'email invalide. Exemple: exemple@domaine.com
        </div>
    </div>
    
    <div class="mb-2">
        <input class="form-control" name="adresse" placeholder="Adresse" required>
    </div>
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
<form method="post" class="modal-content" onsubmit="return validerEmail('modifier')">
<div class="modal-header">
    <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Modifier fournisseur</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <input type="hidden" name="action" value="modifier">
    <input type="hidden" name="id" id="edit-id">
    
    <div class="mb-2">
        <input class="form-control" name="nom" id="edit-nom" required>
    </div>
    
    <div class="mb-2">
        <input class="form-control" name="nomLivreur" id="edit-nomLivreur" required>
    </div>
    
    <div class="mb-2">
        <input class="form-control" name="telephone" id="edit-telephone" required>
    </div>
    
    <div class="mb-2">
        <input class="form-control" 
               name="email" 
               type="email" 
               id="email-modifier"
               pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}"
               title="Format : exemple@domaine.com" 
               required>
        <div class="invalid-feedback" id="email-error-modifier">
            Format d'email invalide. Exemple: exemple@domaine.com
        </div>
    </div>
    
    <div class="mb-2">
        <input class="form-control" name="adresse" id="edit-adresse" required>
    </div>
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
    document.getElementById('email-modifier').value = f.email;
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