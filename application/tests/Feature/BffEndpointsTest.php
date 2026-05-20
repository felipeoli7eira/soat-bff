<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BffEndpointsTest extends TestCase
{
    private const UPLOAD_SERVICE_URL = 'http://fake-upload-service';

    protected function tearDown(): void
    {
        putenv('UPLOAD_SERVICE_BASE_URL');
        unset($_ENV['UPLOAD_SERVICE_BASE_URL'], $_SERVER['UPLOAD_SERVICE_BASE_URL']);
        putenv('REPORT_SERVICE_BASE_URL');
        unset($_ENV['REPORT_SERVICE_BASE_URL'], $_SERVER['REPORT_SERVICE_BASE_URL']);
        parent::tearDown();
    }

    private function clearUploadServiceUrl(): void
    {
        putenv('UPLOAD_SERVICE_BASE_URL');
        unset($_ENV['UPLOAD_SERVICE_BASE_URL'], $_SERVER['UPLOAD_SERVICE_BASE_URL']);
    }

    private function defineReportServiceUrl(): void
    {
        putenv('REPORT_SERVICE_BASE_URL=http://fake-report-service');
        $_ENV['REPORT_SERVICE_BASE_URL']    = 'http://fake-report-service';
        $_SERVER['REPORT_SERVICE_BASE_URL'] = 'http://fake-report-service';
    }

    private function defineUploadServiceUrl(): void
    {
        putenv('UPLOAD_SERVICE_BASE_URL=' . self::UPLOAD_SERVICE_URL);
        $_ENV['UPLOAD_SERVICE_BASE_URL']    = self::UPLOAD_SERVICE_URL;
        $_SERVER['UPLOAD_SERVICE_BASE_URL'] = self::UPLOAD_SERVICE_URL;
    }

    public function test_ping_retorna_pong(): void
    {
        $this->getJson('/api/ping')
            ->assertOk()
            ->assertJson(['err' => false, 'msg' => 'pong']);
    }

    public function test_ping_upload_retorna_500_quando_url_nao_definida(): void
    {
        $this->clearUploadServiceUrl();

        $this->getJson('/api/ping/upload')
            ->assertStatus(500)
            ->assertJson(['err' => true]);
    }

    public function test_ping_upload_retorna_200_quando_servico_responde(): void
    {
        $this->defineUploadServiceUrl();

        Http::fake([
            self::UPLOAD_SERVICE_URL . '*' => Http::response(['err' => false, 'msg' => 'pong'], 200),
        ]);

        $this->getJson('/api/ping/upload')
            ->assertOk()
            ->assertJson(['err' => false, 'msg' => 'Ping realizado com sucesso.']);
    }

    public function test_ping_upload_retorna_500_quando_servico_nao_responde(): void
    {
        $this->defineUploadServiceUrl();

        Http::fake([
            self::UPLOAD_SERVICE_URL . '*' => Http::response(null, 503),
        ]);

        $this->getJson('/api/ping/upload')
            ->assertStatus(500)
            ->assertJson(['err' => true]);
    }

    public function test_ping_upload_retorna_400_quando_ping_endpoint_falha(): void
    {
        $this->defineUploadServiceUrl();

        Http::fake([
            self::UPLOAD_SERVICE_URL       => Http::response(null, 200),
            self::UPLOAD_SERVICE_URL . '/' => Http::response(null, 200),
            self::UPLOAD_SERVICE_URL . '/api/ping' => Http::response(null, 503),
        ]);

        $this->getJson('/api/ping/upload')
            ->assertStatus(400)
            ->assertJson(['err' => true]);
    }

    public function test_ping_upload_retorna_400_quando_conexao_falha(): void
    {
        $this->defineUploadServiceUrl();

        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
        });

        $this->getJson('/api/ping/upload')
            ->assertStatus(400)
            ->assertJson(['err' => true]);
    }

    public function test_status_uuid_retorna_200(): void
    {
        $this->defineReportServiceUrl();

        Http::fake([
            'http://fake-report-service'            => Http::response(null, 200),
            'http://fake-report-service/api/status/*' => Http::response(['err' => false, 'status' => 'RECEBIDO'], 200),
        ]);

        $this->getJson('/api/status/a1b2c3d4-e5f6-7890-abcd-ef1234567890')
            ->assertOk()
            ->assertJson(['err' => false]);
    }

    public function test_report_uuid_retorna_200(): void
    {
        $this->defineReportServiceUrl();

        Http::fake([
            'http://fake-report-service'            => Http::response(null, 200),
            'http://fake-report-service/api/report/*' => Http::response('%PDF-1.4 fake content', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $this->get('/api/report/a1b2c3d4-e5f6-7890-abcd-ef1234567890')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_upload_retorna_500_quando_url_nao_definida(): void
    {
        $this->clearUploadServiceUrl();

        $this->postJson('/api/upload')
            ->assertStatus(500)
            ->assertJson(['err' => true]);
    }

    public function test_upload_retorna_400_quando_arquivo_nao_enviado(): void
    {
        $this->defineUploadServiceUrl();

        $this->postJson('/api/upload')
            ->assertStatus(400)
            ->assertJson(['err' => true]);
    }

    public function test_upload_retorna_400_quando_tipo_invalido(): void
    {
        $this->defineUploadServiceUrl();

        $this->post('/api/upload', [
            'diagram' => UploadedFile::fake()->create('document.txt', 100, 'text/plain'),
        ], ['Accept' => 'application/json'])
            ->assertStatus(400)
            ->assertJson(['err' => true]);
    }

    public function test_upload_retorna_201_quando_servico_aceita_arquivo(): void
    {
        $this->defineUploadServiceUrl();

        $uploadResponse = [
            'err'  => false,
            'data' => [
                'protocol_uuid' => 'uuid-123',
                'original_name' => 'diagram.jpg',
                'unique_name'   => 'unique-name.jpg',
                'mime_type'     => 'image/jpeg',
                'size'          => 1024,
                'endpoint'      => self::UPLOAD_SERVICE_URL . '/bucket/unique-name.jpg',
            ],
        ];

        Http::fake([
            self::UPLOAD_SERVICE_URL . '/api/upload' => Http::response($uploadResponse, 201),
        ]);

        $this->post('/api/upload', [
            'diagram' => UploadedFile::fake()->create('diagram.jpg', 100, 'image/jpeg'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJson(['err' => false, 'msg' => 'Upload realizado com sucesso.']);
    }

    public function test_fallback_retorna_404(): void
    {
        $this->getJson('/api/rota-inexistente')
            ->assertNotFound()
            ->assertJson(['err' => true]);
    }
}
