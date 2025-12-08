<?php
namespace Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Service\JwtService;
use Firebase\JWT\JWT;
use Exception;

class JwtServiceTest extends TestCase {
    
    private JwtService $jwtService;
    private const TEST_USER_ID = 42;
    
    protected function setUp(): void {
        $_ENV['JWT_SECRET_KEY'] = 'chave_secreta_para_testes_longa'; 
        
        $this->jwtService = new JwtService();
    }

    protected function tearDown(): void {
        // Limpeza após o teste
        unset($_ENV['JWT_SECRET_KEY']);
    }

    public function testEncodeGeneratesValidToken(): void {
        $token = $this->jwtService->encode(self::TEST_USER_ID);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);        
        $this->assertCount(3, explode('.', $token));
    }

    public function testDecodeValidTokenReturnsPayload(): void {
        $token = $this->jwtService->encode(self::TEST_USER_ID);
        $payload = $this->jwtService->decode($token);

        $this->assertIsObject($payload);        
        $this->assertObjectHasProperty('uid', $payload);
        
        $this->assertSame(self::TEST_USER_ID, $payload->uid);
    }

    public function testDecodeThrowsExceptionOnInvalidToken(): void {
        $invalidToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1aWQiOjQyfQ.sOmEtHiNgWrOnG123456789';

        $this->expectException(\Firebase\JWT\SignatureInvalidException::class); 
        
        $this->jwtService->decode($invalidToken);
    }
    
    public function testDecodeThrowsExceptionOnExpiredToken(): void {
        $issuedAt = time() - (60 * 60 * 25); // 25 horas atrás
        $expirationTime = $issuedAt + 10; // Expira em 10 segundos após a emissão

        // Gera o payload expirado
        $payload = [
            'iat'  => $issuedAt,
            'exp'  => $expirationTime,
            'uid'  => self::TEST_USER_ID
        ];
        
        $secretKey = $_ENV['JWT_SECRET_KEY'];
        $expiredToken = JWT::encode($payload, $secretKey, 'HS256');

        $this->expectException(\Firebase\JWT\ExpiredException::class);
        
        $this->jwtService->decode($expiredToken);
    }
}
