<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="navbar">
    <div class="container" style="display:flex; justify-content: space-between; align-items: center;">
        <a href="index.php" class="logo">Bilheteria Online</a>
        <nav>
            <ul class="nav-links">
                <?php if(isset($_SESSION['usuario_id'])): ?>
                    <li><a href="perfil.php"><?= htmlspecialchars($_SESSION['usuario_nome']) ?></a></li>
                    <li><a href="logout.php">Sair</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="cadastro.php">Cadastro</a></li>
                <?php endif; ?>
                <!-- Controles de acessibilidade -->
                <li><button id="darkModeBtn">Modo Dark</button></li>
                <li><button id="aumentarFonteBtn">A+</button></li>
                <li><button id="diminuirFonteBtn">A-</button></li>
                <li><button id="resetFonteBtn">Resetar Fonte</button></li>
            </ul>
        </nav>
    </div>
</header>
