<?php
require_once('vendor/autoload.php');

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\WebDriverBy;

// --- 1. PREPARAÇÃO DO BANCO DE DADOS (SQL) ---
// Cria um arquivo de banco de dados local
$db = new SQLite3('meu_banco.db');

// Cria a tabela SE ela não existir (Comando SQL Puro)
$db->exec("CREATE TABLE IF NOT EXISTS resultados (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    titulo TEXT,
    data_coleta DATETIME DEFAULT CURRENT_TIMESTAMP
)");

echo "--- Banco de Dados Conectado ---\n";

// --- 2. CONFIGURAÇÃO DO CHROME ---
$chromePath = '/home/antonio_costan/chrome-linux64/chrome';
$options = new ChromeOptions();
$options->setBinary($chromePath);
$options->addArguments(['--headless', '--no-sandbox', '--disable-dev-shm-usage', '--window-size=1920,1080']);

$capabilities = DesiredCapabilities::chrome();
$capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

try {
    $driver = RemoteWebDriver::create('http://localhost:9515', $capabilities);

    // MUDANÇA: Vamos usar a Wikipedia do Laravel, que é mais fácil de ler que o Google
    echo "1. Acessando Wikipedia...\n";
    $driver->get('https://pt.wikipedia.org/wiki/Laravel');

    // Pega o título principal (h1) e os subtítulos (h2)
    echo "2. Lendo dados da página...\n";
    $elementos = $driver->findElements(WebDriverBy::cssSelector('#content h1, #content h2'));

    echo "3. Salvando no Banco de Dados...\n";
    
    $stmt = $db->prepare('INSERT INTO resultados (titulo) VALUES (:titulo)');

    foreach ($elementos as $item) {
        $texto = $item->getText();
        
        if (!empty($texto) && $texto != 'Conteúdo' && $texto != 'Menu de navegação') {
            // Executa o INSERT no banco (SQL)
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