<?php
session_start();

/* ================= CONFIG ================= */
define('DB_HOST', 'localhost');
define('DB_NAME', 'sitemstock');
define('DB_USER', 'root');
define('DB_PASS', '');

/* ======== FORM ACTIVE (login / register) ======== */
$activeForm = 'login';

/* ================= DB CONNECTION ================= */
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
    die("Erreur DB");
}

/* ================= FUNCTIONS ================= */
function sanitize($v) {
    return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
}

function isGmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/@gmail\.com$/i', $email);
}

function emailExists($pdo, $email) {
    $stmt = $pdo->prepare("SELECT id FROM utilisateur WHERE email=?");
    $stmt->execute([$email]);
    return $stmt->rowCount() > 0;
}
function usernameExists($pdo, $username) {
    $stmt = $pdo->prepare("SELECT id FROM utilisateur WHERE nom=?");
    $stmt->execute([$username]);
    return $stmt->rowCount() > 0;
}

/* ================= REGISTER ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {

    $activeForm = 'register';

    $username = sanitize($_POST['username'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $errors = [];

    if (strlen($username) < 3) {
        $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères.";
    }

    if (!isGmail($email)) {
        $errors[] = "L'adresse email doit se terminer par @gmail.com.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    }

    if (empty($errors)) {
        if (usernameExists($pdo, $username)) {
            $errors[] = "Ce nom d'utilisateur est déjà utilisé.";
        }
        if (emailExists($pdo, $email)) {
            $errors[] = "Cette adresse email est déjà utilisée.";
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare(
            "INSERT INTO utilisateur (nom,email,mot_de_passe,etat) VALUES (?,?,?,1)"
        );
        $stmt->execute([$username, $email, $hash]);

        $message = "Inscription réussie. Vous pouvez vous connecter.";
        $messageType = "success";
        $activeForm = 'login';
    } else {
        $message = $errors[0];
        $messageType = "error";
    }
}

/* ================= LOGIN ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $activeForm = 'login';

    $email = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!isGmail($email) || strlen($password) < 6) {
        $message = "Identifiants invalides.";
        $messageType = "error";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM utilisateur 
                                JOIN role ON utilisateur.role = role.id_Role
                                WHERE email=?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch();

            if ($user['etat'] == 0) {
                $message = "Compte désactivé.";
                $messageType = "error";
            } elseif (password_verify($password, $user['mot_de_passe'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['nom'];
                $_SESSION['role'] = $user['label_Role'];
                $_SESSION['logged_in'] = true;
                header("Location: tableaudebord.php");
                exit;
            } else {
                $message = "Mot de passe incorrect.";
                $messageType = "error";
            }
        } else {
            $message = "Aucun compte trouvé.";
            $messageType = "error";
        }
    }
}

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: tableaudebord.php");
    exit;
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
        <h2 class="slide-element">Se connecter</h2>
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
                <label>Mot de passe</label>
                <i class="fa-solid fa-lock"></i>
                <span class="password-hint">Minimum 6 caractères</span>
                <div id="loginPasswordError" class="error-message"></div>
            </div>
            <div class="field-wrapper slide-element">
                <button class="submit-button" type="submit" name="login">Se connecter</button>
            </div>
            <div class="switch-link slide-element">
                <p>Vous n'avez pas de compte?<br>
                    <a href="#" class="register-trigger">S'inscrire</a>
                </p>
            </div>
        </form>
    </div>
    <div class="welcome-section signin">
        <h2 class="slide-element">Bienvenue!</h2>
    </div>
    <!-- REGISTER -->
    <div class="credentials-panel signup">
        <h2 class="slide-element">S'inscrire</h2>
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
                <label>Nom de utilisateur</label>
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
                <label>Mot de passe</label>
                <span class="password-hint">Minimum 6 caractères</span>
                <div id="registerPasswordError" class="error-message"></div>
            </div>
            <div class="field-wrapper slide-element">
                <button class="submit-button" type="submit" name="register">S'inscrire</button>
            </div>
            <div class="switch-link slide-element">
                <p>vous avez un compte?<br>
                    <a href="#" class="login-trigger">Se connecter</a>
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
</body>
</html>