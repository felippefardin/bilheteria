<footer class="footer">
    <div class="container">
        <p>&copy; <?php echo date('Y'); ?> Bilheteria Online - Todos os direitos reservados</p>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const darkBtn = document.getElementById('darkModeBtn');
    const aumentarBtn = document.getElementById('aumentarFonteBtn');
    const diminuirBtn = document.getElementById('diminuirFonteBtn');
    const resetBtn = document.getElementById('resetFonteBtn');

    let darkMode = localStorage.getItem('darkMode') === 'true';
    let fontSize = parseInt(localStorage.getItem('fontSize')) || 16;

    if(darkMode) body.classList.add('dark-mode');
    body.style.fontSize = fontSize + 'px';

    darkBtn?.addEventListener('click', () => {
        darkMode = body.classList.toggle('dark-mode');
        localStorage.setItem('darkMode', darkMode);
    });

    aumentarBtn?.addEventListener('click', () => {
        fontSize += 2;
        body.style.fontSize = fontSize + 'px';
        localStorage.setItem('fontSize', fontSize);
    });

    diminuirBtn?.addEventListener('click', () => {
        fontSize -= 2;
        if(fontSize < 12) fontSize = 12;
        body.style.fontSize = fontSize + 'px';
        localStorage.setItem('fontSize', fontSize);
    });

    resetBtn?.addEventListener('click', () => {
        fontSize = 16;
        body.style.fontSize = fontSize + 'px';
        localStorage.setItem('fontSize', fontSize);
    });
});
</script>
