<?php
session_start();

/* ===== SÉCURITÉ ===== */
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/* ===== CONNEXION DB ===== */
$db = new PDO("mysql:host=localhost;dbname=sitemstock;charset=utf8mb4", 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ===== UTILISATEUR CONNECTÉ ===== */
$req = $db->prepare("SELECT nom, email FROM utilisateur WHERE id = ?");
$req->execute([$_SESSION['user_id']]);
$current_user = $req->fetch(PDO::FETCH_ASSOC);

/* ===== GESTION DES ACTIONS ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Validation email
    if (isset($_POST['email']) && !empty($_POST['email'])) {
        if (!preg_match('/@gmail\.com$/i', $_POST['email'])) {
            $_SESSION['error'] = "L'adresse email doit se terminer par @gmail.com";
            header("Location: utilisateur.php");
            exit;
        }
    }

    switch ($_POST['action']) {

        /* ========= AJOUT ========= */
        case 'ajouter':
            $stmt = $db->prepare("
                INSERT INTO utilisateur (nom, email, mot_de_passe, etat)
                VALUES (?, ?, ?, '1')
            ");
            $stmt->execute([
                $_POST['nom'],
                $_POST['email'],
                password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT)
            ]);
            break;

        /* ========= MODIFIER ========= */
        case 'modifier':
            // Lorsqu'on modifie, on remet toujours etat=1 (visible)
            if (!empty($_POST['mot_de_passe'])) {
                $stmt = $db->prepare("
                    UPDATE utilisateur
                    SET nom=?, email=?, mot_de_passe=?, etat='1'
                    WHERE id=?
                ");
                $stmt->execute([
                    $_POST['nom'],
                    $_POST['email'],
                    password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT),
                    $_POST['id']
                ]);
            } else {
                $stmt = $db->prepare("
                    UPDATE utilisateur
                    SET nom=?, email=?, etat='1'
                    WHERE id=?
                ");
                $stmt->execute([
                    $_POST['nom'],
                    $_POST['email'],
                    $_POST['id']
                ]);
            }
            break;

        /* ========= SUPPRESSION LOGIQUE ========= */
        case 'supprimer':
            if ($_POST['id'] != $_SESSION['user_id']) {
                // Met l'utilisateur en etat=0 → il ne s'affiche plus
                $stmt = $db->prepare("UPDATE utilisateur SET etat='0' WHERE id=?");
                $stmt->execute([$_POST['id']]);
            } else {
                $_SESSION['error'] = "Vous ne pouvez pas supprimer votre propre compte !";
            }
            break;
    }

    header("Location: utilisateur.php");
    exit;
}

/* ===== RÉCUPÉRATION UTILISATEURS (affichage seulement etat=1) ===== */
$utilisateurs = $db->query("
    SELECT id, nom, email, etat
    FROM utilisateur
    WHERE etat = '1'
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion des Utilisateurs - SitemStock</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="sidebar.css">
<style>
.badge-etat {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: 500;
}
.badge-actif {
    background-color: #d1fae5;
    color: #065f46;
}
.badge-inactif {
    background-color: #fee2e2;
    color: #991b1b;
}
.email-hint {
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 0.25rem;
}
</style>
</head>
<body>
<div class="main-content">
    <?php include 'sidebar.php'; ?>
    <!-- WELCOME -->
    <div class="welcome-section">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2><i class="fas fa-user-friends me-2"></i> Gestion des Utilisateurs</h2>
                <p class="mb-0">Gérez les comptes utilisateurs du système.</p>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajouterModal">
                    <i class="fas fa-user-plus me-1"></i> Ajouter un utilisateur
                </button>
            </div>
        </div>
    </div>
    
    <!-- Message d'erreur -->
    <?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
        <?= $_SESSION['error']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); endif; ?>
    
    <!-- TABLE UTILISATEURS -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>État</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($utilisateurs as $utilisateur): ?>
                    <tr>
                        <td><?= $utilisateur['id'] ?></td>
                        <td><?= htmlspecialchars($utilisateur['nom']) ?></td>
                        <td><?= htmlspecialchars($utilisateur['email']) ?></td>
                        <td>
                            <span class="badge-etat <?= $utilisateur['etat'] == '1' ? 'badge-actif' : 'badge-inactif' ?>">
                                <?= $utilisateur['etat'] == '1' ? 'Actif' : 'Inactif' ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modifierModal" onclick='chargerUtilisateur(<?= json_encode($utilisateur) ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if($utilisateur['id'] != $_SESSION['user_id']): ?>
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#supprimerModal" onclick="setDeleteId(<?= $utilisateur['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled title="Vous ne pouvez pas supprimer votre propre compte">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($utilisateurs)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-2 text-muted">
                            Aucun utilisateur enregistré
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL AJOUTER UTILISATEUR -->
<div class="modal fade" id="ajouterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formAjouter">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i> Ajouter un utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="ajouter">
                    <div class="mb-3">
                        <label class="form-label">Nom *</label>
                        <input class="form-control" name="nom" required maxlength="50">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input class="form-control" name="email" type="email" 
                               required 
                               pattern="[a-zA-Z0-9._%+-]+@gmail\.com$"
                               title="L'adresse email doit se terminer par @gmail.com"
                               oninput="validateEmail(this)">
                        <div class="email-hint">
                            <i class="fas fa-info-circle"></i> Doit se terminer par @gmail.com
                        </div>
                        <div id="emailError" class="text-danger small mt-1" style="display: none;"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe *</label>
                        <input class="form-control" name="mot_de_passe" type="password" required minlength="6">
                        <div class="email-hint">
                            <i class="fas fa-info-circle"></i> Minimum 6 caractères
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">État *</label>
                        <select class="form-select" name="etat" required>
                            <option value="1">Actif</option>
                            <option value="0">Inactif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submitAjouter">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL MODIFIER UTILISATEUR -->
<div class="modal fade" id="modifierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formModifier">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Modifier l'utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="modifier">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Nom *</label>
                        <input class="form-control" name="nom" id="edit_nom" required maxlength="50">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input class="form-control" name="email" type="email" 
                               id="edit_email" 
                               required 
                               pattern="[a-zA-Z0-9._%+-]+@gmail\.com$"
                               title="L'adresse email doit se terminer par @gmail.com"
                               oninput="validateEmail(this)">
                        <div class="email-hint">
                            <i class="fas fa-info-circle"></i> Doit se terminer par @gmail.com
                        </div>
                        <div id="editEmailError" class="text-danger small mt-1" style="display: none;"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe (laisser vide pour ne pas changer)</label>
                        <input class="form-control" name="mot_de_passe" type="password" minlength="6">
                        <div class="email-hint">
                            <i class="fas fa-info-circle"></i> Minimum 6 caractères si modifié
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">État *</label>
                        <select class="form-select" name="etat" id="edit_etat" required>
                            <option value="1">Actif</option>
                            <option value="0">Inactif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submitModifier">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL SUPPRIMER UTILISATEUR -->
<div class="modal fade" id="supprimerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash me-1"></i> Supprimer l'utilisateur</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p>Voulez-vous vraiment supprimer cet utilisateur ?</p>
                <p class="text-danger"><small>Cette action est irréversible</small></p>
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
function chargerUtilisateur(utilisateur){
    document.getElementById('edit_id').value = utilisateur.id;
    document.getElementById('edit_nom').value = utilisateur.nom;
    document.getElementById('edit_email').value = utilisateur.email;
    document.getElementById('edit_etat').value = utilisateur.etat;
}

// Fonction pour ouvrir le modal de suppression et passer l'id
function setDeleteId(id){
    document.getElementById('sup_id').value = id;
}

// Validation en temps réel de l'email
function validateEmail(input) {
    const email = input.value;
    const errorDiv = input.id === 'edit_email' 
        ? document.getElementById('editEmailError') 
        : document.getElementById('emailError');
    
    if (email === '') {
        errorDiv.style.display = 'none';
        return;
    }
    
    // Vérification avec regex pour @gmail.com
    const gmailRegex = /@gmail\.com$/i;
    if (!gmailRegex.test(email)) {
        errorDiv.textContent = "L'email doit se terminer par @gmail.com";
        errorDiv.style.display = 'block';
        input.setCustomValidity("L'email doit se terminer par @gmail.com");
    } else {
        errorDiv.style.display = 'none';
        input.setCustomValidity('');
    }
}

// Validation des formulaires avant soumission
document.getElementById('formAjouter')?.addEventListener('submit', function(e) {
    const emailInput = this.querySelector('input[name="email"]');
    if (!/@gmail\.com$/i.test(emailInput.value)) {
        e.preventDefault();
        alert("L'adresse email doit se terminer par @gmail.com");
        emailInput.focus();
    }
});

document.getElementById('formModifier')?.addEventListener('submit', function(e) {
    const emailInput = this.querySelector('input[name="email"]');
    if (!/@gmail\.com$/i.test(emailInput.value)) {
        e.preventDefault();
        alert("L'adresse email doit se terminer par @gmail.com");
        emailInput.focus();
    }
});
</script>
</body>
</html>