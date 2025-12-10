<?php
require("vendor/autoload.php");

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

$baseDir = dirname(__FILE__);
$yamlFile = $baseDir . '/openapi-spec.yaml';
$jsonOutput = $baseDir . '/public/swagger.json';

try {
    $yamlContent = file_get_contents($yamlFile);
    if ($yamlContent === false) {
        throw new \Exception("Erro ao ler o arquivo YAML em: " . $yamlFile);
    }
    
    $specArray = Yaml::parse($yamlContent);
    $jsonSpec = json_encode($specArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    file_put_contents($jsonOutput, $jsonSpec);

    echo "Documentação Swagger/OpenAPI gerada com sucesso\n";
    
} catch (ParseException $e) {
    printf("Erro de Parsing YAML: %s\n", $e->getMessage());
    exit(1);
} catch (\Exception $e) {
    printf("Erro na geração do documento: %s\n", $e->getMessage());
    exit(1);
}
