<?php
require_once('vendor/autoload.php');

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\WebDriverBy;

// --- 1. PREPARAÇÃO DO BANCO DE DADOS (SQL) ---
$db = new SQLite3('meu_banco.db');

$db->exec("CREATE TABLE IF NOT EXISTS resultados (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    titulo TEXT,
    data_coleta DATETIME DEFAULT CURRENT_TIMESTAMP
)");

echo "--- Banco de Dados Conectado ---\n";

// --- 2. CONFIGURAÇÃO DO CHROME (CORRIGIDO) ---

// MUDANÇA AQUI: Usamos __DIR__ para pegar o caminho atual + a pasta do chrome
// O caminho final será: /home/antonio_costan/bionexo/projeto-robo/chrome-linux64/chrome
$chromePath = __DIR__ . '/chrome-linux64/chrome';

$options = new ChromeOptions();
$options->setBinary($chromePath);
$options->addArguments(['--headless', '--no-sandbox', '--disable-dev-shm-usage', '--window-size=1920,1080']);

$capabilities = DesiredCapabilities::chrome();
$capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

try {
    // ATENÇÃO: O Chromedriver precisa estar rodando na porta 9515 em outro terminal
    // Ou o código deve iniciá-lo (mas mantive sua lógica original de conectar no localhost)
    $driver = RemoteWebDriver::create('http://localhost:9515', $capabilities);

    echo "1. Acessando Wikipedia...\n";
    $driver->get('https://pt.wikipedia.org/wiki/Laravel');

    echo "2. Lendo dados da página...\n";
    $elementos = $driver->findElements(WebDriverBy::cssSelector('#content h1, #content h2'));

    echo "3. Salvando no Banco de Dados...\n";
    
    $stmt = $db->prepare('INSERT INTO resultados (titulo) VALUES (:titulo)');

    foreach ($elementos as $item) {
        $texto = $item->getText();
        
        if (!empty($texto) && $texto != 'Conteúdo' && $texto != 'Menu de navegação') {
            $stmt->bindValue(':titulo', $texto);
            $stmt->execute();
            echo " [SQL] Salvo: $texto\n";
        }
    }

    echo "\n--- PROVA REAL: Lendo do Banco de Dados ---\n";
    $consulta = $db->query('SELECT * FROM resultados ORDER BY id DESC LIMIT 5');
    while ($linha = $consulta->fetchArray()) {
        echo "ID: {$linha['id']} | Título: {$linha['titulo']}\n";
    }

} catch(\Exception $e) {
    echo "ERRO: " . $e->getMessage();
} finally {
    if(isset($driver)) $driver->quit();
}