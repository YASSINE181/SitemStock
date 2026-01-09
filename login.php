<?php
session_start();

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'sitemstock');
define('DB_USER', 'root');
define('DB_PASS', '');

// Connexion sécurisée à la base de données
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion DB : " . $e->getMessage());
}

// Fonction pour vérifier la structure de la table
function checkTableStructure($pdo) {
    try {
        $stmt = $pdo->query("DESCRIBE utilisateur");
        $columns = $stmt->fetchAll();
        
        $hasAutoIncrement = false;
        foreach ($columns as $col) {
            if ($col['Field'] == 'id' && strpos($col['Extra'], 'auto_increment') !== false) {
                $hasAutoIncrement = true;
                break;
            }
        }
        
        if (!$hasAutoIncrement) {
            // Corriger la table pour ajouter AUTO_INCREMENT
            $pdo->exec("ALTER TABLE utilisateur MODIFY id INT AUTO_INCREMENT PRIMARY KEY");
            return true;
        }
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Vérifier et corriger la structure de la table
checkTableStructure($pdo);

// Fonction pour nettoyer et valider les entrées
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Fonction pour valider l'email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Fonction pour hacher le mot de passe
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Fonction pour vérifier le mot de passe
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Fonction pour vérifier si un email existe déjà
function emailExists($pdo, $email) {
    $stmt = $pdo->prepare("SELECT id FROM utilisateur WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->rowCount() > 0;
}

// Fonction pour vérifier si un nom d'utilisateur existe déjà
function usernameExists($pdo, $username) {
    $stmt = $pdo->prepare("SELECT id FROM utilisateur WHERE nom = ?");
    $stmt->execute([$username]);
    return $stmt->rowCount() > 0;
}

// Traitement de l'inscription
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['register'])) {
    // Nettoyage des données
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation des données
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Le nom d'utilisateur est requis.";
    } elseif (strlen($username) < 3) {
        $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères.";
    }
    
    if (empty($email)) {
        $errors[] = "L'adresse email est requise.";
    } elseif (!validateEmail($email)) {
        $errors[] = "L'adresse email est invalide.";
    }
    
    if (empty($password)) {
        $errors[] = "Le mot de passe est requis.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    }
    
    // Vérifications supplémentaires si pas d'erreurs de base
    if (empty($errors)) {
        if (usernameExists($pdo, $username)) {
            $errors[] = "Ce nom d'utilisateur est déjà utilisé.";
        }
        
        if (emailExists($pdo, $email)) {
            $errors[] = "Cette adresse email est déjà utilisée.";
        }
    }
    
    // Si aucune erreur, procéder à l'inscription
    if (empty($errors)) {
        try {
            // Hachage sécurisé du mot de passe
            $hashedPassword = hashPassword($password);
            
            // Insertion dans la base de données
            $stmt = $pdo->prepare(
                "INSERT INTO utilisateur (nom, email, mot_de_passe) 
                 VALUES (?, ?, ?)"
            );
            
            // Exécuter la requête
            $stmt->execute([$username, $email, $hashedPassword]);
            
            // Message de succès
            $message = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
            $messageType = "success";
            
        } catch (PDOException $e) {
            // Message d'erreur détaillé
            $errorMessage = "Erreur d'inscription : " . $e->getMessage();
            
            // Si c'est l'erreur de clé auto-incrément, donner des instructions
            if (strpos($e->getMessage(), "Field 'id' doesn't have a default value") !== false) {
                $errorMessage = "Erreur de configuration de la base de données. La table 'utilisateur' n'a pas de clé auto-incrémentée.";
                $errorMessage .= "<br><br>Veuillez exécuter cette commande SQL dans phpMyAdmin :";
                $errorMessage .= "<br><code>ALTER TABLE utilisateur MODIFY id INT AUTO_INCREMENT PRIMARY KEY;</code>";
            }
            
            $message = $errorMessage;
            $messageType = "error";
        }
    } else {
        // Afficher la première erreur rencontrée
        $message = $errors[0];
        $messageType = "error";
    }
}

// Traitement de la connexion
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {
    // Nettoyage des données
    $email = sanitizeInput($_POST['username'] ?? ''); // Note: champ "username" dans le HTML mais c'est l'email
    $password = $_POST['password'] ?? '';
    
    // Validation des données
    if (empty($email) || empty($password)) {
        $message = "Veuillez remplir tous les champs.";
        $messageType = "error";
    } elseif (!validateEmail($email)) {
        $message = "Veuillez entrer une adresse email valide.";
        $messageType = "error";
    } else {
        try {
            // Recherche de l'utilisateur par email
            $stmt = $pdo->prepare(
                "SELECT id, nom, email, mot_de_passe 
                 FROM utilisateur 
                 WHERE email = ?"
            );
            
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch();
                
                // Vérification du mot de passe
                if (verifyPassword($password, $user['mot_de_passe'])) {
                    // Connexion réussie
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['nom'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['logged_in'] = true;
                    
                    // Redirection vers la page utilisateur
                    header("Location: acceuil.php");
                    exit();
                } else {
                    $message = "Mot de passe incorrect.";
                    $messageType = "error";
                }
            } else {
                $message = "Aucun compte trouvé avec cet email.";
                $messageType = "error";
            }
            
        } catch (PDOException $e) {
            $message = "Une erreur est survenue lors de la connexion. Veuillez réessayer.";
            $messageType = "error";
        }
    }
}

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: acceuil.php");
    exit();
}
?>

<!-- LE RESTE DE VOTRE HTML EXISTANT (JE NE LE MODIFIE PAS) -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login & Signup</title>
    <!-- CSS EXTERNE -->
    <link rel="stylesheet" href="login.css">

    <!-- FONT AWESOME -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="background-shape"></div>
    <div class="secondary-shape"></div>
    <div class="credentials-panel signin">
        <h2 class="slide-element">Login</h2>
        <?php if (isset($message) && isset($_POST['login'])): ?>
            <p style="text-align:center;color:<?php echo ($messageType ?? '') === 'success' ? 'green' : 'red'; ?>;">
                <?= htmlspecialchars($message) ?>
            </p>
        <?php endif; ?>
        <form method="POST">
            <div class="field-wrapper slide-element">
                <input type="text" name="username" required placeholder=" " autocomplete="email">
                <label>Email</label>
                <i class="fa-solid fa-user"></i>
            </div>
            <div class="field-wrapper slide-element">
                <input type="password" name="password" required placeholder=" " autocomplete="current-password">
                <label>Password</label>
                <i class="fa-solid fa-lock"></i>
            </div>
            <div class="field-wrapper slide-element">
                <button class="submit-button" type="submit" name="login">Login</button>
            </div>
            <div class="switch-link slide-element">
                <p>Don't have an account?<br>
                    <a href="#" class="register-trigger">Sign Up</a>
                </p>
            </div>
        </form>
    </div>
    <div class="welcome-section signin">
        <h2 class="slide-element">WELCOME BACK!</h2>
    </div>
    <!-- REGISTER -->
    <div class="credentials-panel signup">
        <h2 class="slide-element">Register</h2>
        <?php if (isset($message) && isset($_POST['register'])): ?>
            <p style="text-align:center;color:<?php echo ($messageType ?? '') === 'success' ? 'green' : 'red'; ?>;">
                <?php 
                // Pour les messages d'erreur détaillés, afficher avec nl2br pour les sauts de ligne
                if (($messageType ?? '') === 'error' && strpos($message, '<br>') !== false) {
                    echo nl2br(htmlspecialchars($message));
                } else {
                    echo htmlspecialchars($message);
                }
                ?>
            </p>
        <?php endif; ?>
        <form method="POST">
            <div class="field-wrapper slide-element">
                <input type="text" name="username" required placeholder=" " autocomplete="username">
                <label>Username</label>
                <i class="fa-solid fa-user"></i>
            </div>
            <div class="field-wrapper slide-element">
                <input type="email" name="email" required placeholder=" " autocomplete="email">
                <label>Email</label>
            </div>
            <div class="field-wrapper slide-element">
                <input type="password" name="password" required placeholder=" " autocomplete="new-password">
                <label>Password</label>
            </div>
            <div class="field-wrapper slide-element">
                <button class="submit-button" type="submit" name="register">Register</button>
            </div>
            <div class="switch-link slide-element">
                <p>Already have an account?<br>
                    <a href="#" class="login-trigger">Sign In</a>
                </p>
            </div>
        </form>
    </div>
</div>
<!-- JS EXTERNE -->
<script src="login.js"></script>
</body>
</html>