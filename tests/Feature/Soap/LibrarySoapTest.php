<?php

namespace Tests\Feature\Soap;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibrarySoapTest extends TestCase
{
    use RefreshDatabase;

    private function envelope(string $nim): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
    xmlns:m="http://sipus.del.ac.id/soap/clearance">
    <soap:Body>
        <m:CheckClearanceStatusRequest>
            <m:nim_peminjam>{$nim}</m:nim_peminjam>
        </m:CheckClearanceStatusRequest>
    </soap:Body>
</soap:Envelope>
XML;
    }

    public function test_wsdl_endpoint_returns_valid_xml_contract(): void
    {
        $response = $this->get('/soap/sipus.wsdl');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/xml; charset=utf-8');
        $this->assertStringContainsString('<definitions', $response->getContent());
        $this->assertStringContainsString('http://sipus.del.ac.id/soap/clearance', $response->getContent());
        $this->assertStringContainsString('CheckClearanceStatus', $response->getContent());
        $this->assertStringContainsString('LibrarySoapService', $response->getContent());
    }

    public function test_soap_endpoint_returns_cleared_for_student_without_active_loans(): void
    {
        $xml = $this->envelope('12S24053');

        $response = $this->call('POST', '/soap/sipus', [], [], [], [
            'CONTENT_TYPE' => 'text/xml; charset=utf-8',
        ], $xml);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/xml; charset=utf-8');
        $this->assertStringContainsString('<m:is_cleared>true</m:is_cleared>', $response->getContent());
        $this->assertStringContainsString('<m:active_loans>0</m:active_loans>', $response->getContent());
        $this->assertStringContainsString('BEBAS PUSTAKA', $response->getContent());
    }

    public function test_soap_endpoint_returns_not_cleared_for_student_with_active_loans(): void
    {
        $kategori = Kategori::factory()->create();
        $buku = Buku::factory()->create(['kategori_id' => $kategori->id]);

        Peminjaman::create([
            'kode_pinjam' => 'PINJAM-SOAP-0001',
            'buku_id' => $buku->id,
            'nim_peminjam' => '12S24099',
            'nama_peminjam' => 'Mahasiswa Tanggungan',
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(7)->toDateString(),
            'status' => 'dipinjam',
        ]);

        $xml = $this->envelope('12S24099');

        $response = $this->call('POST', '/soap/sipus', [], [], [], [
            'CONTENT_TYPE' => 'text/xml; charset=utf-8',
        ], $xml);

        $response->assertOk();
        $this->assertStringContainsString('<m:is_cleared>false</m:is_cleared>', $response->getContent());
        $this->assertStringContainsString('<m:active_loans>1</m:active_loans>', $response->getContent());
        $this->assertStringContainsString('BELUM BEBAS', $response->getContent());
    }

    public function test_soap_endpoint_returns_fault_on_empty_body(): void
    {
        $response = $this->call('POST', '/soap/sipus', [], [], [], [
            'CONTENT_TYPE' => 'text/xml; charset=utf-8',
        ], '');

        $response->assertStatus(500);
        $this->assertStringContainsString('<soap:Fault>', $response->getContent());
        $this->assertStringContainsString('<faultcode>soap:Client</faultcode>', $response->getContent());
        $this->assertStringContainsString('Body XML SOAP tidak boleh kosong', $response->getContent());
    }

    public function test_soap_endpoint_returns_fault_on_malformed_xml(): void
    {
        $badXml = '<soap:Envelope><soap:Body><unclosed></soap:Envelope>';

        $response = $this->call('POST', '/soap/sipus', [], [], [], [
            'CONTENT_TYPE' => 'text/xml; charset=utf-8',
        ], $badXml);

        $response->assertStatus(500);
        $this->assertStringContainsString('<soap:Fault>', $response->getContent());
        $this->assertStringContainsString('Malformed XML', $response->getContent());
    }

    public function test_soap_endpoint_returns_fault_when_nim_tag_is_missing(): void
    {
        $noNimXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
    xmlns:m="http://sipus.del.ac.id/soap/clearance">
    <soap:Body>
        <m:CheckClearanceStatusRequest>
        </m:CheckClearanceStatusRequest>
    </soap:Body>
</soap:Envelope>
XML;

        $response = $this->call('POST', '/soap/sipus', [], [], [], [
            'CONTENT_TYPE' => 'text/xml; charset=utf-8',
        ], $noNimXml);

        $response->assertStatus(500);
        $this->assertStringContainsString('<soap:Fault>', $response->getContent());
        $this->assertStringContainsString('tidak ditemukan', $response->getContent());
    }

    public function test_soap_endpoint_returns_fault_when_nim_is_empty(): void
    {
        $emptyNimXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
    xmlns:m="http://sipus.del.ac.id/soap/clearance">
    <soap:Body>
        <m:CheckClearanceStatusRequest>
            <m:nim_peminjam>   </m:nim_peminjam>
        </m:CheckClearanceStatusRequest>
    </soap:Body>
</soap:Envelope>
XML;

        $response = $this->call('POST', '/soap/sipus', [], [], [], [
            'CONTENT_TYPE' => 'text/xml; charset=utf-8',
        ], $emptyNimXml);

        $response->assertStatus(500);
        $this->assertStringContainsString('<soap:Fault>', $response->getContent());
        $this->assertStringContainsString('tidak boleh kosong', $response->getContent());
    }
}
