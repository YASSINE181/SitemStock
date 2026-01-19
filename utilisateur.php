<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = new PDO("mysql:host=localhost;dbname=sitemstock;charset=utf8", "root", "");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ================= ACTIONS CRUD ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    /* ===== AJOUT ===== */
    if ($_POST['action'] === 'ajouter') {
        $db->beginTransaction();
        try {
            $db->prepare("
                INSERT INTO commande (numero_commande, client_id, date_commande, date_livraison, montant_total)
                VALUES (?,?,?,?,0)
            ")->execute([
                $_POST['numero_commande'],
                $_POST['client_id'],
                $_POST['date_commande'],
                $_POST['date_livraison']
            ]);

            $commande_id = $db->lastInsertId();
            $total = 0;

            foreach ($_POST['produit_id'] as $i => $pid) {
                if (!$pid) continue;

                $qte = (int)$_POST['quantite'][$i];
                $fid = $_POST['fournisseur_id'][$i];

                $prix = $db->prepare("SELECT prix_vente FROM produit WHERE id=?");
                $prix->execute([$pid]);
                $pu = (float)$prix->fetchColumn();

                $montant = $pu * $qte;
                $total += $montant;

                $db->prepare("
                    INSERT INTO commande_produit
                    (commande_id, produit_id, fournisseur_id, quantite, prix_unitaire, montant)
                    VALUES (?,?,?,?,?,?)
                ")->execute([$commande_id,$pid,$fid,$qte,$pu,$montant]);
            }

            $db->prepare("UPDATE commande SET montant_total=? WHERE id=?")
               ->execute([$total,$commande_id]);

            $db->commit();
        } catch(Exception $e){
            $db->rollBack();
            die($e->getMessage());
        }
    }

    /* ===== MODIFIER ===== */
    if ($_POST['action'] === 'modifier') {
        $db->beginTransaction();
        try {
            $id = $_POST['id'];

            $db->prepare("
                UPDATE commande
                SET numero_commande=?, client_id=?, date_commande=?, date_livraison=?
                WHERE id=?
            ")->execute([
                $_POST['numero_commande'],
                $_POST['client_id'],
                $_POST['date_commande'],
                $_POST['date_livraison'],
                $id
            ]);

            $db->prepare("DELETE FROM commande_produit WHERE commande_id=?")->execute([$id]);

            $total = 0;
            foreach ($_POST['produit_id'] as $i => $pid) {
                if (!$pid) continue;

                $qte = (int)$_POST['quantite'][$i];
                $fid = $_POST['fournisseur_id'][$i];

                $prix = $db->prepare("SELECT prix_vente FROM produit WHERE id=?");
                $prix->execute([$pid]);
                $pu = (float)$prix->fetchColumn();

                $montant = $pu * $qte;
                $total += $montant;

                $db->prepare("
                    INSERT INTO commande_produit
                    (commande_id, produit_id, fournisseur_id, quantite, prix_unitaire, montant)
                    VALUES (?,?,?,?,?,?)
                ")->execute([$id,$pid,$fid,$qte,$pu,$montant]);
            }

            $db->prepare("UPDATE commande SET montant_total=? WHERE id=?")
               ->execute([$total,$id]);

            $db->commit();
        } catch(Exception $e){
            $db->rollBack();
            die($e->getMessage());
        }
    }
=======
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
<<<<<<<< HEAD:gestioncommande.php
    header("Location: gestioncommande.php");
========
>>>>>>> yassine

    /* ===== SUPPRIMER ===== */
    if ($_POST['action'] === 'supprimer') {
        $id = $_POST['id'];
        $db->prepare("DELETE FROM commande_produit WHERE commande_id=?")->execute([$id]);
        $db->prepare("DELETE FROM commande WHERE id=?")->execute([$id]);
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
>>>>>>> yassine
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<<<<<<< HEAD
<title>Gestion Commandes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="gestionstock.css">
<link rel="stylesheet" href="acceuil.css">
</head>
<body>

<div class="main-content">
<?php include 'sidebar.php'; ?>

<div class="welcome-section">
<div class="row">
<div class="col-md-8">
<h2><i class="fas fa-cart-shopping"></i> Gestion des Commandes</h2>
</div>
<div class="col-md-4 text-end">
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajouterModal">
<i class="fas fa-plus"></i> Ajouter
</button>
</div>
</div>
</div>

<div class="table-container">
<table class="table table-hover">
<thead class="table-light">
<tr>
<th>ID</th><th>Numéro</th><th>Client</th><th>Date</th><th>Livraison</th><th>Total</th><th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach($commandes as $c): ?>
<tr>
<td><?= $c['id'] ?></td>
<td><?= $c['numero_commande'] ?></td>
<td><?= $c['client_nom'].' '.$c['client_prenom'] ?></td>
<td><?= $c['date_commande'] ?></td>
<td><?= $c['date_livraison'] ?></td>
<td><?= number_format($c['montant_total'],2) ?> €</td>
<td class="text-center">

<button class="btn btn-sm btn-outline-primary"
onclick="chargerCommande(<?= $c['id'] ?>)"
data-bs-toggle="modal" data-bs-target="#modifierModal">
<i class="fas fa-edit"></i>
</button>

<button class="btn btn-sm btn-outline-danger"
onclick="setDeleteId(<?= $c['id'] ?>)"
data-bs-toggle="modal" data-bs-target="#supprimerModal">
<i class="fas fa-trash"></i>
</button>

<a href="facture.php?commande_id=<?= $c['id'] ?>" target="_blank"
class="btn btn-sm btn-outline-success">
<i class="fas fa-file-pdf"></i>
</a>

</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<!-- ================= MODALS ================= -->

<!-- AJOUT -->
<div class="modal fade" id="ajouterModal">
<div class="modal-dialog modal-lg">
<form method="POST" class="modal-content">
<input type="hidden" name="action" value="ajouter">
<div class="modal-body">

<input class="form-control mb-2" name="numero_commande" placeholder="Numéro" required>

<select class="form-select mb-2" name="client_id" required>
<?php foreach($clients as $cl): ?>
<option value="<?= $cl['id'] ?>"><?= $cl['nom'].' '.$cl['prenom'] ?></option>
<?php endforeach; ?>
</select>

<input type="date" class="form-control mb-2" name="date_commande" required>
<input type="date" class="form-control mb-2" name="date_livraison" required>

<div id="produits-container">
<div class="row g-2 produit-item mb-2">
<div class="col-md-4">
<select class="form-select" name="produit_id[]">
<?php foreach($produits as $p): ?>
<option value="<?= $p['id'] ?>"><?= $p['nom'] ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-3">
<select class="form-select" name="fournisseur_id[]">
<?php foreach($fournisseurs as $f): ?>
<option value="<?= $f['id'] ?>"><?= $f['nom'] ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-2">
<input type="number" name="quantite[]" class="form-control" value="1" min="1">
</div>
<div class="col-md-3 text-end">
<button type="button" class="btn btn-danger remove-produit">X</button>
</div>
</div>
</div>

<button type="button" id="add-produit" class="btn btn-secondary btn-sm">+ Produit</button>

</div>
<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
<button class="btn btn-success">Ajouter</button>
</div>
</form>
</div>
</div>

<!-- MODIFIER -->
<div class="modal fade" id="modifierModal">
<div class="modal-dialog modal-lg">
<form method="POST" class="modal-content">
<input type="hidden" name="action" value="modifier">
<input type="hidden" name="id" id="edit_id">
<div class="modal-body">
<input class="form-control mb-2" id="edit_numero" name="numero_commande">
<select class="form-select mb-2" id="edit_client" name="client_id">
<?php foreach($clients as $cl): ?>
<option value="<?= $cl['id'] ?>"><?= $cl['nom'].' '.$cl['prenom'] ?></option>
<?php endforeach; ?>
</select>
<input type="date" class="form-control mb-2" id="edit_date_commande" name="date_commande">
<input type="date" class="form-control mb-2" id="edit_date_livraison" name="date_livraison">
<div id="edit-produits-container"></div>
</div>
<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
<button class="btn btn-primary">Enregistrer</button>
</div>
</form>
</div>
</div>

<!-- SUPPRIMER -->
<div class="modal fade" id="supprimerModal">
<div class="modal-dialog">
<form method="POST" class="modal-content">
<input type="hidden" name="action" value="supprimer">
<input type="hidden" name="id" id="sup_id">
<div class="modal-body text-center">
Supprimer cette commande ?
</div>
<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
<button class="btn btn-danger">Supprimer</button>
</div>
</form>
</div>
</div>

<script>
function setDeleteId(id){ document.getElementById('sup_id').value=id; }

document.getElementById('add-produit').onclick = ()=>{
let c=document.getElementById('produits-container');
let f=c.querySelector('.produit-item');
let n=f.cloneNode(true);
n.querySelectorAll('input,select').forEach(e=>e.value='');
c.appendChild(n);
n.querySelector('.remove-produit').onclick=()=>n.remove();
};

function chargerCommande(id){
fetch('load_commande.php?id='+id)
.then(r=>r.json())
.then(d=>{
edit_id.value=d.commande.id;
edit_numero.value=d.commande.numero_commande;
edit_client.value=d.commande.client_id;
edit_date_commande.value=d.commande.date_commande;
edit_date_livraison.value=d.commande.date_livraison;
});
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
=======
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion des Commandes - SitemStock</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<<<<<<<< HEAD:gestioncommande.php
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="acceuil.css">
<link rel="stylesheet" href="gestionstock.css">
<style>
    .btn-action {
        width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 0 3px;
    }
    .table-container {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-top: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .produit-item {
        margin-bottom: 10px;
        padding: 10px;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        background-color: #f8f9fa;
    }
</style>
========
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="sidebar.css">
>>>>>>>> yassine:utilisateur.php
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
=======
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
>>>>>>> 887e58d0182093755b3fa36b5e1b2f3278439f70
                </button>
            </div>
        </div>
    </div>
<<<<<<< HEAD
    
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
                        <td><?= number_format($commande['montant_total'], 2) ?> €</td>
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
=======

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
>>>>>>> 887e58d0182093755b3fa36b5e1b2f3278439f70
            </div>
        </form>
    </div>
</div>

<<<<<<< HEAD
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
                            ${p.nom} (${parseFloat(p.prix_vente).toFixed(2)}€)
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
>>>>>>> yassine
</body>
</html>
=======
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
>>>>>>> 887e58d0182093755b3fa36b5e1b2f3278439f70
