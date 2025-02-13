<?php
// Start the session at the beginning of the script
session_start();

// Define valid credentials
$VALID_USERNAME = 'evoluciona';
$VALID_PASSWORD = 'evoluciona';

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Destroy the session
    session_unset();
    session_destroy();
    // Redirect to the login form
    header("Location: index.php");
    exit;
}

// Handle Login Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    if ($username === $VALID_USERNAME && $password === $VALID_PASSWORD) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        header("Location: index.php");
        exit;
    } else {
        $error = "Usuario o contraseña inválidos.";
    }
}

$loggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;

// Function to retrieve images from a folder
function getImages($folder) {
    $images = [];
    if (is_dir($folder)) {
        $files = scandir($folder);
        foreach ($files as $file) {
            if (preg_match('/\.(jpg|jpeg|png|gif|svg)$/i', $file)) {
                $images[] = $file;
            }
        }
    }
    return $images;
}

// Define chart folders
$chartFolders = [
    'hourly' => 'img/hourly',
    'minute' => 'img/minute',
    'second' => 'img/second',
];

// Get selected chart type
$selectedType = isset($_GET['type']) && isset($chartFolders[$_GET['type']]) ? $_GET['type'] : 'hourly';
$images = $loggedIn ? getImages($chartFolders[$selectedType]) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVOmetricas</title>
    <link rel="icon" href="logo.png" type="image/svg+xml">
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap');

        /* Reset and General Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Ubuntu,'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            color: #333;
            min-height: 100vh;
        }

        /* Header Styles */
        .header {
            background-color: LightCoral;
            width: 100%;
            padding: 15px 30px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header .app-name {
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            justify-content: center;
            align-items: center;
            align-content: stretch;
        }
        .header .app-name img{
        	width:50px;
        	margin-right:20px;
        }
        .header .logout-button {
            background-color: LightCoral;
            color: white;
            border: 2px solid white;
            padding: 8px 16px;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            font-size: 1rem;
            transition: background-color 0.3s, border-color 0.3s;
        }
        .header .logout-button:hover {
            background-color:rgb(212, 100, 100);
            border-color: lightcoral;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: #fff;
            padding: 20px;
            border-right: 1px solid #ddd;
            position: fixed;
            top: 70px; /* Height of the header */
            bottom: 0;
            overflow-y: auto;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
        }
        .sidebar h3 {
            margin-bottom: 20px;
            color: LightCoral;
            font-size: 1.2rem;
        }
        .sidebar a {
            display: block;
            padding: 12px 16px;
            margin: 8px 0;
            color: LightCoral;
            text-decoration: none;
            border-radius: 8px;
            transition: background-color 0.3s, color 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: LightCoral;
            color: white;
        }

        /* Content Styles */
        .content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            margin-top: 70px; /* Height of the header */
            margin-left: 270px; /* Width of the sidebar + margin */
        }
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .image-card {
            text-align: center;
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2);
        }
        .image-card img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
        }

        /* Modal Styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 200; /* Sit on top */
            padding-top: 60px; /* Location of the box */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.8); /* Black w/ opacity */
        }
        .modal-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 80%;
            border-radius: 10px;
            animation-name: zoom;
            animation-duration: 0.6s;
        }
        @keyframes zoom {
            from {transform:scale(0)}
            to {transform:scale(1)}
        }
        .close {
            position: absolute;
            top: 30px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: color 0.3s;
            cursor: pointer;
        }
        .close:hover,
        .close:focus {
            color: #bbb;
            text-decoration: none;
            cursor: pointer;
        }

        /* Login Styles */
        .login-container {
            background: #fff;
            padding: 40px 30px;
            max-width: 400px;
            width: 90%;
            margin: 100px auto;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            text-align: center;
            position: relative;
        }
        .login-container .logo {
            width: 100px;
            margin-bottom: 20px;
        }
        .login-container h2 {
            margin-bottom: 20px;
            color: LightCoral;
            font-size: 1.8rem;
        }
        .login-container .error {
            background-color: #f8d7da;
            color: #842029;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        .login-container label {
            display: block;
            margin-bottom: 5px;
            text-align: left;
            color: #555;
            font-size: 0.95rem;
        }
        .login-container input {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .login-container input:focus {
            border-color: LightCoral;
            outline: none;
        }
        .login-container button {
            width: 100%;
            padding: 12px;
            background-color: LightCoral;
            border: none;
            color: white;
            font-size: 1rem;
            border-radius: 25px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .login-container button:hover {
            background-color: rgb(212, 100, 100);
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            .content {
                margin-left: 220px;
            }
        }
        @media (max-width: 576px) {
            .sidebar {
                display: none;
            }
            .content {
                margin-left: 0;
            }
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            .header .logout-button {
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <?php if ($loggedIn): ?>
        <!-- Header -->
        <div class="header">
            <div class="app-name"><img src="logo.png">EVOmetricas</div>
            <a href="index.php?action=logout" class="logout-button">Cerrar Sesión</a>
        </div>

        <!-- Sidebar -->
        <div class="sidebar">
            <h3>Gráficas</h3>
            <a href="index.php?type=hourly" class="<?php echo $selectedType === 'hourly' ? 'active' : ''; ?>">Última Hora</a>
           
        </div>

        <!-- Main Content -->
        <div class="content">
            <div class="dashboard">
                <?php if (!empty($images)): ?>
                    <?php foreach ($images as $image): ?>
                        <div class="image-card" onclick="openModal('<?php echo htmlspecialchars($chartFolders[$selectedType] . '/' . $image); ?>')">
                            <img src="<?php echo htmlspecialchars($chartFolders[$selectedType] . '/' . $image); ?>" alt="Chart">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No hay gráficas disponibles en la carpeta seleccionada.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- The Modal -->
        <div id="myModal" class="modal">
            <span class="close" onclick="closeModal()">&times;</span>
            <img class="modal-content" id="modalImage" alt="Chart Enlarged">
        </div>

        <!-- JavaScript for Modal -->
        <script>
            function openModal(src) {
                var modal = document.getElementById("myModal");
                var modalImg = document.getElementById("modalImage");
                modal.style.display = "block";
                modalImg.src = src;
            }

            function closeModal() {
                var modal = document.getElementById("myModal");
                modal.style.display = "none";
            }

            // Close the modal when clicking outside the image
            window.onclick = function(event) {
                var modal = document.getElementById("myModal");
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
        </script>
    <?php else: ?>
        <div class="login-container">
            <img src="logo.png" alt="EVOmetricas Logo" class="logo">
            <h2>Iniciar Sesión</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form action="index.php" method="post">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" required placeholder="Ingresa tu usuario">
                
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required placeholder="Ingresa tu contraseña">
                
                <button type="submit" name="login">Entrar</button>
            </form>
        </div>
    <?php endif; ?>
</body>
</html>

