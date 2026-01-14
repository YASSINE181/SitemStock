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

    /* ===== SUPPRIMER ===== */
    if ($_POST['action'] === 'supprimer') {
        $id = $_POST['id'];
        $db->prepare("DELETE FROM commande_produit WHERE commande_id=?")->execute([$id]);
        $db->prepare("DELETE FROM commande WHERE id=?")->execute([$id]);
    }

    header("Location: gestioncommande.php");
    exit;
}

/* ================= DONNÉES ================= */
$commandes = $db->query("
    SELECT c.*, cl.nom client_nom, cl.prenom client_prenom
    FROM commande c
    JOIN client cl ON cl.id=c.client_id
    ORDER BY c.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$clients = $db->query("SELECT * FROM client")->fetchAll(PDO::FETCH_ASSOC);
$produits = $db->query("SELECT * FROM produit")->fetchAll(PDO::FETCH_ASSOC);
$fournisseurs = $db->query("SELECT * FROM fournisseur")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gestion Commandes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="gestionstock.css">
<link rel="stylesheet" href="utilisateur.css">
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
</body>
</html>