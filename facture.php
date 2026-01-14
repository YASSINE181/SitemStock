
<?php
require('fpdf186/fpdf.php');

/* ================= CONNEXION DB ================= */
$db = new PDO("mysql:host=localhost;dbname=sitemstock", "root", "");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ================= VERIFICATION ================= */
if (!isset($_GET['commande_id'])) {
    die('ID commande manquant');
}
$commande_id = (int)$_GET['commande_id'];

/* ================= COMMANDE + CLIENT ================= */
$stmt = $db->prepare("
    SELECT 
        c.id,
        c.numero_commande,
        c.date_commande,
        c.montant_total,
        cl.nom AS client_nom,
        cl.prenom AS client_prenom,
        cl.email,
        cl.telephone
    FROM commande c
    JOIN client cl ON c.client_id = cl.id
    WHERE c.id = ?
");
$stmt->execute([$commande_id]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$commande) {
    die('Commande introuvable');
}

/* ================= PRODUITS ================= */
$stmt = $db->prepare("
    SELECT 
        p.nom AS produit_nom,
        cp.quantite,
        p.prix_vente
    FROM commande_produit cp
    JOIN produit p ON cp.produit_id = p.id
    WHERE cp.commande_id = ?
");
$stmt->execute([$commande_id]);
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= PDF ================= */
$pdf = new FPDF();
$pdf->AddPage();

/* ===== TITRE ===== */
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'FACTURE',0,1,'C');

/* ===== CLIENT ===== */
$pdf->Ln(5);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,7,'Informations Client',0,1);

$pdf->SetFont('Arial','',10);
$pdf->Cell(0,6,'Nom : '.$commande['client_nom'].' '.$commande['client_prenom'],0,1);
$pdf->Cell(0,6,'Email : '.$commande['email'],0,1);
$pdf->Cell(0,6,'Telephone : '.$commande['telephone'],0,1);

/* ===== COMMANDE ===== */
$pdf->Ln(4);
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,6,'Numero commande : '.$commande['numero_commande'],0,1);
$pdf->Cell(0,6,'Date commande : '.$commande['date_commande'],0,1);

/* ===== TABLE PRODUITS ===== */
$pdf->Ln(6);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(80,8,'Produit',1);
$pdf->Cell(30,8,'Quantite',1,0,'C');
$pdf->Cell(40,8,'Prix unitaire',1,0,'R');
$pdf->Cell(40,8,'Total',1,0,'R');
$pdf->Ln();

$pdf->SetFont('Arial','',10);
$total = 0;

foreach ($produits as $p) {
    $ligne_total = $p['quantite'] * $p['prix_vente'];
    $total += $ligne_total;

    $pdf->Cell(80,8,$p['produit_nom'],1);
    $pdf->Cell(30,8,$p['quantite'],1,0,'C');
    $pdf->Cell(40,8,number_format($p['prix_vente'],2).' DT',1,0,'R');
    $pdf->Cell(40,8,number_format($ligne_total,2).' DT',1,0,'R');
    $pdf->Ln();
}

/* ===== TOTAL GENERAL ===== */
$pdf->Ln(3);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(150,8,'TOTAL GENERAL',0,0,'R');
$pdf->Cell(40,8,number_format($total,2).' DT',1,0,'R');

/* ===== SIGNATURE TEXTUELLE ===== */
$pdf->Ln(15);
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,6,'Signature et cachet',0,1,'R');

$pdf->Ln(15);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,'Le Responsable',0,1,'R');

$pdf->SetFont('Arial','',9);
$pdf->Cell(0,5,'Date : '.date('d/m/Y'),0,1,'R');

/* ===== AFFICHAGE PDF ===== */
$pdf->Output('I', 'facture_'.$commande['numero_commande'].'.pdf');
