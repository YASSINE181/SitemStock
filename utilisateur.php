<?php
require "config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ================= ACTIONS ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // ===== AJOUT =====
    if ($_POST['action'] === 'ajouter') {

        $nom = trim($_POST['nom']);
        $email = trim($_POST['email']);
        $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);

        $check = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE email=?");
        $check->execute([$email]);

        if ($check->fetchColumn() > 0) {
            $_SESSION['flash_message'] = "⚠️ Email déjà utilisé";
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO utilisateur (nom,email,mot_de_passe,etat)
                 VALUES (?,?,?,1)"
            );
            $stmt->execute([$nom, $email, $mot_de_passe]);
            $_SESSION['flash_message'] = "✅ Utilisateur ajouté";
        }

        header("Location: utilisateur.php");
        exit;
    }

    // ===== MODIFIER =====
    if ($_POST['action'] === 'modifier') {

        $stmt = $pdo->prepare(
            "UPDATE utilisateur SET nom=?, email=? WHERE id=?"
        );
        $stmt->execute([
            $_POST['nom'],
            $_POST['email'],
            $_POST['id']
        ]);

        $_SESSION['flash_message'] = "✏️ Utilisateur modifié";
        header("Location: utilisateur.php");
        exit;
    }

    // ===== SUPPRIMER (soft delete) =====
    if ($_POST['action'] === 'supprimer') {

        $stmt = $pdo->prepare(
            "UPDATE utilisateur SET etat=0 WHERE id=?"
        );
        $stmt->execute([$_POST['id']]);

        $_SESSION['flash_message'] = "🗑️ Utilisateur supprimé";
        header("Location: utilisateur.php");
        exit;
    }
}

/* ================= DONNÉES ================= */
$utilisateurs = $pdo->query(
    "SELECT id, nom, email FROM utilisateur WHERE etat=1"
)->fetchAll(PDO::FETCH_ASSOC);

$message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Utilisateurs - SiteMStock</title>

    <!-- CSS -->
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="four.css">

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>


<div class="main-content">
<?php include 'sidebar.php'; ?>

    

    <!-- ===== WELCOME ===== -->
    <div class="welcome-section mt-3">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2><i class="fas fa-users me-2"></i> Liste des utilisateurs</h2>
                <p class="mb-0">Gestion des utilisateurs</p>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-primary" onclick="ouvrirAjouterModal()">
                    <i class="fas fa-plus me-1"></i> Ajouter
                </button>
            </div>
        </div>
    </div>

    <!-- ===== TABLE ===== -->
    <div class="table-container mt-3">
        <table class="table table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($utilisateurs): ?>
                <?php foreach ($utilisateurs as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['nom']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td class="text-center">
                            <button class="btn btn-primary btn-action"
                                onclick="ouvrirModal(
                                    <?= $u['id'] ?>,
                                    '<?= addslashes($u['nom']) ?>',
                                    '<?= addslashes($u['email']) ?>'
                                )">
                                <i class="fas fa-edit"></i>
                            </button>

                            <button class="btn btn-danger btn-action"
                                onclick="ouvrirModalSupp(<?= $u['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" class="text-center">Aucun utilisateur</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ===== MODAL AJOUT ===== -->
<div class="modal fade" id="modalAjouter">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <input type="hidden" name="action" value="ajouter">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus me-1"></i> Ajouter utilisateur</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input class="form-control mb-2" name="nom" placeholder="Nom" required>
                <input class="form-control mb-2" name="email" type="email" placeholder="Email" required>
                <input class="form-control" name="mot_de_passe" type="password" placeholder="Mot de passe" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-success">Ajouter</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL MODIFIER ===== -->
<div class="modal fade" id="modalModifier">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <input type="hidden" name="action" value="modifier">
            <input type="hidden" name="id" id="edit-id">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-edit me-1"></i> Modifier utilisateur</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input class="form-control mb-2" id="edit-nom" name="nom" required>
                <input class="form-control" id="edit-email" name="email" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL SUPPRIMER ===== -->
<div class="modal fade" id="modalSupp">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content">
            <input type="hidden" name="action" value="supprimer">
            <input type="hidden" name="id" id="delete-id">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash me-1"></i> Supprimer utilisateur</h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                Voulez-vous vraiment supprimer cet utilisateur ?
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-danger">Supprimer</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== MODAL MESSAGE ===== -->
<div class="modal fade" id="modalMessage">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center">
            <div class="modal-body py-4">
                <h5><?= htmlspecialchars($message) ?></h5>
            </div>
        </div>
    </div>
</div>

<!-- JS -->
<script>
function ouvrirAjouterModal() {
    new bootstrap.Modal(document.getElementById('modalAjouter')).show();
}

function ouvrirModal(id, nom, email) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-nom').value = nom;
    document.getElementById('edit-email').value = email;
    new bootstrap.Modal(document.getElementById('modalModifier')).show();
}

function ouvrirModalSupp(id) {
    document.getElementById('delete-id').value = id;
    new bootstrap.Modal(document.getElementById('modalSupp')).show();
}
</script>

<?php if (!empty($message)): ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    let modal = new bootstrap.Modal(document.getElementById('modalMessage'));
    modal.show();
    setTimeout(() => modal.hide(), 2000);
});
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>