<?php
// Initialize the session
session_start();
// Include config file
require_once "config.php";
require "constantes.php";

// Importation du fichier de configuration.
$jsonConfigFile = file_get_contents('data.json', true);
$jsonConfig = json_decode($jsonConfigFile);

// Copie des variables importés depuis le JSON vers les variables  d'environements.
foreach ($jsonConfig as $key => $value) {
    putenv("{$key}={$value}");
}

// Check if the user is already logged in, if yes then redirect him to welcome page
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: welcome.php");
    exit;
}

// Define variables and initialize with empty values
$username = $password = $email= "";
$username_err = $password_err = $email_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Check if username is empty
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }

    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }


    // Validate credentials
    if (empty($username_err) && empty($password_err)) {
        // Prepare a select statement
        $sql = "SELECT id, username, email, password, user_salt FROM user WHERE username = :username";

        if ($stmt = $pdo->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);

            // Set parameters
            $param_username = trim($_POST["username"]);

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Check if username exists, if yes then verify password
                if ($stmt->rowCount() == 1) {
                    if ($row = $stmt->fetch()) {
                         
                        $emailBdd = $row["email"]; 
                        $pepper = getenv('pozier'); //recup valeur du poivre
                        $userSalt = $row["user_salt"]; //recup du salt bdd
                        $bddHashed_password = $row["password"]; //recup pasword bdd
                        $passwordChiffre = openssl_encrypt($password, "AES-128-ECB", $userSalt); //chiffrage password reçu en input
                        $inputHashedPassword = hash('sha512', $passwordChiffre, ); //hash password reçu en input
                        $emailDechiffre = openssl_decrypt($emailBdd, "AES-128-ECB", $userSalt.$pepper); //dechiffrage email reçu en input
                        var_dump($inputHashedPassword );
                        var_dump($bddHashed_password);

                        //verification de l'égalité des deux password
                        if ($bddHashed_password == $inputHashedPassword) { 

                            // Password is correct, so start a new session
                            session_start();

                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["email"] = $emailDechiffre; 

                            // Redirect user to welcome page
                            header("location: welcome.php");
                        } else {
                            // Display an error message if password is not valid
                            $password_err = ERREUR_AUTHENTIFICATION;
                        }
                    } else {
                        // Display an error message if password is not valid
                        $password_err = ERREUR_AUTHENTIFICATION;
                    }
                }
            } else {
                // Display an error message if username doesn't exist
                $username_err = ERREUR_AUTHENTIFICATION;
            }
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
        // Close statement
        unset($stmt);
    }
}
// Close connection
unset($pdo);

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css" media="screen" type="text/css" />
    <title>Login</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
    
    <style type="text/css">
        body {
            font: 14px sans-serif;
        }

        .wrapper {
            width: 350px;
            padding: 20px;
        }
    </style>
</head>

<body class="container">
    <div class="wrapper">
        <h2>Login</h2>
        <p>Please fill in your credentials to login.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group <?php echo (!empty($username_err)) ? 'has-error' : ''; ?>">
                <label>Username</label>
                <input type="text" name="username" class="form-control" value="<?php echo $username; ?>">
                <span class="help-block"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                <label>Password</label>
                <input type="password" name="password" class="form-control">
                <span class="help-block"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Login">
            </div>
            <p>Don't have an account? <a href="registrationForm.php">Sign up now</a>.</p>
        </form>
    </div>
</body>

</html>