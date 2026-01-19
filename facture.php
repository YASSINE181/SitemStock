<?php
session_start();

// Démarrer la temporisation de sortie pour éviter tout affichage avant le PDF
ob_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require('fpdf186/fpdf.php');

/* ================= CONNEXION DB ================= */
$db = new PDO("mysql:host=localhost;dbname=sitemstock;charset=utf8", "root", "");
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
        cl.telephone,
        cl.adresse
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
        p.prix_vente,
        (cp.quantite * p.prix_vente) AS total_ligne
    FROM commande_produit cp
    JOIN produit p ON cp.produit_id = p.id
    WHERE cp.commande_id = ?
    ORDER BY p.nom
");
$stmt->execute([$commande_id]);
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Informations de l'entreprise
$entreprise_nom = "SITEMSTOCK SARL";
$entreprise_adresse = "123 Avenue des Entrepreneurs\nTunis 1002";
$entreprise_tel = "+216 70 123 456";
$entreprise_email = "contact@sitemstock.com";
$entreprise_site = "www.sitemstock.com";
$entreprise_siret = "123 456 789 00012";
$entreprise_tva = "FR12 345678901";

/* ================= CLASSE PDF PERSONNALISEE ================= */
class FacturePDF extends FPDF {
    // En-tête de page
    function Header() {
        global $entreprise_nom, $entreprise_adresse, $entreprise_tel, $entreprise_email;
        
        // Logo (à ajouter si vous avez un logo)
        // $this->Image('logo.png', 10, 10, 30);
        
        // Coordonnées entreprise
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(0, 51, 102); // Bleu foncé
        $this->Cell(0, 10, $entreprise_nom, 0, 1, 'L');
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(0, 0, 0);
        $this->MultiCell(80, 5, $entreprise_adresse, 0, 'L');
        $this->Cell(0, 5, 'Tel: ' . $entreprise_tel, 0, 1, 'L');
        $this->Cell(0, 5, 'Email: ' . $entreprise_email, 0, 1, 'L');
        
        // Ligne de séparation
        $this->SetDrawColor(0, 51, 102);
        $this->SetLineWidth(0.5);
        $this->Line(10, 40, 200, 40);
        
        // Titre FACTURE à droite
        $this->SetFont('Arial', 'B', 24);
        $this->SetTextColor(0, 51, 102);
        $this->SetXY(120, 15);
        $this->Cell(80, 10, 'FACTURE', 0, 1, 'R');
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(100, 100, 100);
        $this->SetXY(120, 25);
        $this->Cell(80, 5, 'Document commercial', 0, 1, 'R');
        
        $this->Ln(15);
    }
    
    // Pied de page
    function Footer() {
        global $entreprise_siret, $entreprise_tva;
        
        // Position à 1.5 cm du bas
        $this->SetY(-25);
        
        // Ligne de séparation
        $this->SetDrawColor(200, 200, 200);
        $this->SetLineWidth(0.3);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        
        // Informations légales
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 5, 'SITEMSTOCK SARL - Capital social: 50 000 DT - SIRET: ' . $entreprise_siret . ' - TVA: ' . $entreprise_tva, 0, 1, 'C');
        $this->Cell(0, 5, 'Banque: ATB - IBAN: TN59 1234 5678 9012 3456 7890 - Code BIC: ATBKTNTT', 0, 1, 'C');
        $this->Cell(0, 5, 'Cette facture est payable a reception. Tout retard de paiement entrainera des penalites de 1,5% par mois.', 0, 1, 'C');
        
        // Numéro de page
        $this->SetY(-10);
        $this->Cell(0, 5, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
    
    // Méthode pour créer un tableau
    function CreateTable($header, $data) {
        // Couleurs, épaisseur du trait et police
        $this->SetFillColor(0, 51, 102);
        $this->SetTextColor(255);
        $this->SetDrawColor(0, 51, 102);
        $this->SetLineWidth(0.3);
        $this->SetFont('Arial', 'B', 10);
        
        // En-tête
        $w = array(80, 30, 40, 40);
        for($i = 0; $i < count($header); $i++)
            $this->Cell($w[$i], 8, $header[$i], 1, 0, 'C', true);
        $this->Ln();
        
        // Restauration des couleurs et police
        $this->SetFillColor(240, 248, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 10);
        
        // Données
        $fill = false;
        $total_ht = 0;
        
        foreach($data as $row) {
            $this->Cell($w[0], 8, iconv('UTF-8', 'ISO-8859-1', $row['produit_nom']), 'LR', 0, 'L', $fill);
            $this->Cell($w[1], 8, $row['quantite'], 'LR', 0, 'C', $fill);
            $this->Cell($w[2], 8, number_format($row['prix_vente'], 2) . ' DT', 'LR', 0, 'R', $fill);
            $this->Cell($w[3], 8, number_format($row['total_ligne'], 2) . ' DT', 'LR', 0, 'R', $fill);
            $this->Ln();
            $fill = !$fill;
            $total_ht += $row['total_ligne'];
        }
        
        // Trait de fermeture
        $this->Cell(array_sum($w), 0, '', 'T');
        $this->Ln(5);
        
        return $total_ht;
    }
}

/* ================= CREATION DU PDF ================= */
$pdf = new FacturePDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// Numéro et date de facture
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(0, 51, 102);
$pdf->Cell(0, 8, 'Facture N°: ' . $commande['numero_commande'], 0, 1, 'R');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, 'Date: ' . date('d/m/Y', strtotime($commande['date_commande'])), 0, 1, 'R');
$pdf->Cell(0, 5, 'Echeance: ' . date('d/m/Y', strtotime($commande['date_commande'] . ' +30 days')), 0, 1, 'R');

$pdf->Ln(10);

// Informations client
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(0, 51, 102);
$pdf->Cell(0, 8, iconv('UTF-8', 'ISO-8859-1', 'Facturé à :'), 0, 1);
$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(0, 0, 0);

$client_adresse_complete = $commande['client_nom'] . ' ' . $commande['client_prenom'];
if (!empty($commande['adresse'])) {
    $client_adresse_complete .= "\n" . $commande['adresse'];
}

$pdf->MultiCell(80, 6, iconv('UTF-8', 'ISO-8859-1', $client_adresse_complete), 0, 'L');
$pdf->Cell(0, 6, 'Tel: ' . $commande['telephone'], 0, 1, 'L');
$pdf->Cell(0, 6, 'Email: ' . $commande['email'], 0, 1, 'L');

$pdf->Ln(10);

// Tableau des produits
$header = array('Description', 'Quantite', 'Prix Unitaire', 'Total');
$total_ht = $pdf->CreateTable($header, $produits);

// Calcul des montants
$taux_tva = 0.19; // 19% de TVA
$montant_tva = $total_ht * $taux_tva;
$montant_ttc = $total_ht + $montant_tva;

// Récapitulatif des montants
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(130);
$pdf->Cell(30, 8, 'Total HT:', 0, 0, 'R');
$pdf->Cell(30, 8, number_format($total_ht, 2) . ' DT', 0, 1, 'R');

$pdf->Cell(130);
$pdf->Cell(30, 8, 'TVA (19%):', 0, 0, 'R');
$pdf->Cell(30, 8, number_format($montant_tva, 2) . ' DT', 0, 1, 'R');

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(0, 51, 102);
$pdf->Cell(130);
$pdf->Cell(30, 10, 'Total TTC:', 0, 0, 'R');
$pdf->Cell(30, 10, number_format($montant_ttc, 2) . ' DT', 0, 1, 'R');

// Conditions de paiement
$pdf->Ln(15);
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 8, iconv('UTF-8', 'ISO-8859-1', 'Conditions de paiement:'), 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, iconv('UTF-8', 'ISO-8859-1', 'Paiement par virement bancaire dans les 30 jours suivant la date de facturation.'), 0, 1);
$pdf->Cell(0, 6, 'IBAN: TN59 1234 5678 9012 3456 7890 - BIC: ATBKTNTT', 0, 1);

// Mentions
$pdf->Ln(10);
$pdf->SetFont('Arial', 'I', 9);
$pdf->SetTextColor(100, 100, 100);
$pdf->MultiCell(0, 5, iconv('UTF-8', 'ISO-8859-1', "En cas de retard de paiement, seront exigibles, conformément à l'article L. 441-6 du code de commerce, une indemnité forfaitaire pour frais de recouvrement de 40€ et des pénalités de retard au taux annuel de 15%."), 0, 'L');

// Signature
$pdf->Ln(15);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, iconv('UTF-8', 'ISO-8859-1', 'Fait à Tunis, le ') . date('d/m/Y'), 0, 1, 'R');
$pdf->Ln(15);
$pdf->Cell(0, 5, '__________________________', 0, 1, 'R');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 5, iconv('UTF-8', 'ISO-8859-1', 'Le Directeur Commercial'), 0, 1, 'R');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 5, 'SITEMSTOCK SARL', 0, 1, 'R');

/* ===== AFFICHAGE PDF ===== */
// Nettoyer le tampon de sortie avant d'envoyer le PDF
ob_end_clean();
$pdf->Output('I', 'Facture_' . $commande['numero_commande'] . '.pdf');