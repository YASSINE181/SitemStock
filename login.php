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

// Fonction pour valider l'email Gmail
function validateGmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/@gmail\.com$/i', $email);
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
    } elseif (!validateGmail($email)) {
        $errors[] = "L'adresse email doit se terminer par @gmail.com.";
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
                "INSERT INTO utilisateur (nom, email, mot_de_passe, etat) 
                 VALUES (?, ?, ?, '1')"
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
    } elseif (!validateGmail($email)) {
        $message = "L'adresse email doit se terminer par @gmail.com.";
        $messageType = "error";
    } else {
        try {
            // Recherche de l'utilisateur par email
            $stmt = $pdo->prepare(
                "SELECT id, nom, email, mot_de_passe, etat 
                 FROM utilisateur 
                 WHERE email = ?"
            );
            
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch();
                
                // Vérifier si le compte est actif
                if ($user['etat'] == '0') {
                    $message = "Ce compte est désactivé. Contactez l'administrateur.";
                    $messageType = "error";
                }
                // Vérification du mot de passe
                else if (verifyPassword($password, $user['mot_de_passe'])) {
                    // Connexion réussie
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['nom'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['logged_in'] = true;
                    
                    // Redirection vers la page utilisateur
                    header("Location: tableaudebord.php");
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
    header("Location: tableaudebord.php");
    exit();
}
?>
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
    <style>
        .email-hint {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.25rem;
            display: block;
        }
        .error-message {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 0.25rem;
            display: none;
        }
        .password-hint {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.25rem;
            display: block;
        }
    </style>
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
        <form method="POST" id="loginForm">
            <div class="field-wrapper slide-element">
                <input type="email" name="username" required placeholder=" " autocomplete="email"
                       pattern="[a-zA-Z0-9._%+-]+@gmail\.com$"
                       title="Veuillez entrer une adresse Gmail valide se terminant par @gmail.com"
                       oninput="validateLoginEmail(this)">
                <label>Email</label>
                <i class="fa-solid fa-user"></i>
                <span class="email-hint">Doit se terminer par @gmail.com</span>
                <div id="loginEmailError" class="error-message"></div>
            </div>
            <div class="field-wrapper slide-element">
                <input type="password" name="password" required placeholder=" " autocomplete="current-password"
                       minlength="6"
                       title="Le mot de passe doit contenir au moins 6 caractères"
                       oninput="validateLoginPassword(this)">
                <label>Password</label>
                <i class="fa-solid fa-lock"></i>
                <span class="password-hint">Minimum 6 caractères</span>
                <div id="loginPasswordError" class="error-message"></div>
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
        <form method="POST" id="registerForm">
            <div class="field-wrapper slide-element">
                <input type="text" name="username" required placeholder=" " autocomplete="username"
                       minlength="3"
                       title="Le nom d'utilisateur doit contenir au moins 3 caractères">
                <label>Username</label>
                <i class="fa-solid fa-user"></i>
                <span class="password-hint">Minimum 3 caractères</span>
            </div>
            <div class="field-wrapper slide-element">
                <input type="email" name="email" required placeholder=" " autocomplete="email"
                       pattern="[a-zA-Z0-9._%+-]+@gmail\.com$"
                       title="Veuillez entrer une adresse Gmail valide se terminant par @gmail.com"
                       oninput="validateRegisterEmail(this)">
                <label>Email</label>
                <span class="email-hint">Doit se terminer par @gmail.com</span>
                <div id="registerEmailError" class="error-message"></div>
            </div>
            <div class="field-wrapper slide-element">
                <input type="password" name="password" required placeholder=" " autocomplete="new-password"
                       minlength="6"
                       title="Le mot de passe doit contenir au moins 6 caractères"
                       oninput="validateRegisterPassword(this)">
                <label>Password</label>
                <span class="password-hint">Minimum 6 caractères</span>
                <div id="registerPasswordError" class="error-message"></div>
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
    <!-- WELCOME SECTION POUR LE REGISTER -->
    <div class="welcome-section signup">
    </div>
</div>
<!-- JS EXTERNE -->
<script src="login.js"></script>
<script>
// Validation en temps réel pour le formulaire de connexion
function validateLoginEmail(input) {
    const email = input.value;
    const errorDiv = document.getElementById('loginEmailError');
    
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

function validateLoginPassword(input) {
    const password = input.value;
    const errorDiv = document.getElementById('loginPasswordError');
    
    if (password === '') {
        errorDiv.style.display = 'none';
        return;
    }
    
    if (password.length < 6) {
        errorDiv.textContent = "Le mot de passe doit contenir au moins 6 caractères";
        errorDiv.style.display = 'block';
        input.setCustomValidity("Le mot de passe doit contenir au moins 6 caractères");
    } else {
        errorDiv.style.display = 'none';
        input.setCustomValidity('');
    }
}

// Validation en temps réel pour le formulaire d'inscription
function validateRegisterEmail(input) {
    const email = input.value;
    const errorDiv = document.getElementById('registerEmailError');
    
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

function validateRegisterPassword(input) {
    const password = input.value;
    const errorDiv = document.getElementById('registerPasswordError');
    
    if (password === '') {
        errorDiv.style.display = 'none';
        return;
    }
    
    if (password.length < 6) {
        errorDiv.textContent = "Le mot de passe doit contenir au moins 6 caractères";
        errorDiv.style.display = 'block';
        input.setCustomValidity("Le mot de passe doit contenir au moins 6 caractères");
    } else {
        errorDiv.style.display = 'none';
        input.setCustomValidity('');
    }
}

// Validation des formulaires avant soumission
document.getElementById('loginForm')?.addEventListener('submit', function(e) {
    const emailInput = this.querySelector('input[name="username"]');
    const passwordInput = this.querySelector('input[name="password"]');
    let valid = true;
    
    if (!/@gmail\.com$/i.test(emailInput.value)) {
        alert("L'adresse email doit se terminer par @gmail.com");
        emailInput.focus();
        valid = false;
    }
    
    if (valid && passwordInput.value.length < 6) {
        alert("Le mot de passe doit contenir au moins 6 caractères");
        passwordInput.focus();
        valid = false;
    }
    
    if (!valid) {
        e.preventDefault();
    }
});

document.getElementById('registerForm')?.addEventListener('submit', function(e) {
    const usernameInput = this.querySelector('input[name="username"]');
    const emailInput = this.querySelector('input[name="email"]');
    const passwordInput = this.querySelector('input[name="password"]');
    let valid = true;
    
    if (usernameInput.value.length < 3) {
        alert("Le nom d'utilisateur doit contenir au moins 3 caractères");
        usernameInput.focus();
        valid = false;
    }
    
    if (valid && !/@gmail\.com$/i.test(emailInput.value)) {
        alert("L'adresse email doit se terminer par @gmail.com");
        emailInput.focus();
        valid = false;
    }
    
    if (valid && passwordInput.value.length < 6) {
        alert("Le mot de passe doit contenir au moins 6 caractères");
        passwordInput.focus();
        valid = false;
    }
    
    if (!valid) {
        e.preventDefault();
    }
});
</script>
</body>
</html>