<?php
require_once('vendor/autoload.php');

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Chrome\ChromeOptions;

// --- CONFIGURAÇÃO ---
// Caminho do Chrome que você baixou na pasta inicial
$chromePath = '/home/antonio_costan/chrome-linux64/chrome';

$options = new ChromeOptions();
$options->setBinary($chromePath);
$options->addArguments([
    '--headless', // Importante: Roda sem janela (obrigatório no WSL)
    '--no-sandbox',
    '--disable-dev-shm-usage',
    '--window-size=1920,1080',
]);

$capabilities = DesiredCapabilities::chrome();
$capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

echo "--- Iniciando Automação ---\n";

try {
    // Conecta ao motorista (que vamos rodar no passo seguinte)
    $driver = RemoteWebDriver::create('http://localhost:9515', $capabilities);

    // 1. Acessa o Google
    echo "Acessando Google...\n";
    $driver->get('https://www.google.com');
    
    // 2. Busca algo
    echo "Pesquisando vaga...\n";
    $elementoBusca = $driver->findElement(\Facebook\WebDriver\WebDriverBy::name('q'));
    $elementoBusca->sendKeys('Vaga Estágio PHP Laravel');
    $elementoBusca->submit();

    // 3. Tira a prova (Print)
    sleep(2); // Espera carregar
    $driver->takeScreenshot('resultado_google.png');
    echo "Sucesso! Print salvo como 'resultado_google.png'.\n";

} catch(\Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Dica: Verifique se o comando 'chromedriver' está rodando no terminal.\n";
} finally {
    if(isset($driver)) {
        $driver->quit();
    }
}