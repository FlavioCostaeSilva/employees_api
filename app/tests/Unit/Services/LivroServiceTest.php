<?php

namespace Tests\Unit\Services;

use App\Models\Assunto;
use App\Models\Autor;
use App\Models\User;
use App\Repositories\LivroRepository;
use App\Services\LivroService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LivroServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LivroService $service;
    protected LivroRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new LivroRepository();
        $this->service = new LivroService($this->repository);
    }

    public function testGetAllLivros(): void
    {
        User::factory()->count(2)->create();

        $result = $this->service->getAllLivros();

        $this->assertCount(2, $result);
        $this->assertInstanceOf(User::class, $result[0]);
    }

    public function testGetLivroById(): void
    {
        $autor1 = Autor::factory()->create();
        $autor2 = Autor::factory()->create();
        $assunto1 = Assunto::factory()->create();
        $assunto2 = Assunto::factory()->create();

        $livro = User::factory()->create();
        $livro->autores()->attach([$autor1->CodAu, $autor2->CodAu]);
        $livro->assuntos()->attach([$assunto1->codAs, $assunto2->codAs]);

        $result = $this->service->getLivroById($livro->Codl);

        $this->assertEquals($livro->Codl, $result->Codl);
        $this->assertTrue($result->relationLoaded('autores'));
        $this->assertTrue($result->relationLoaded('assuntos'));
        $this->assertCount(2, $result->autores);
        $this->assertCount(2, $result->assuntos);
    }

    public function testCreateLivroSuccess(): void
    {
        $autor1 = Autor::factory()->create();
        $autor2 = Autor::factory()->create();
        $assunto1 = Assunto::factory()->create();
        $assunto2 = Assunto::factory()->create();

        $data = [
            'Titulo' => 'Test Title',
            'Editora' => 'Test Editora',
            'AnoPublicacao' => '2020',
            'Preco' => 1000,
            'autores' => [$autor1->CodAu, $autor2->CodAu],
            'assuntos' => [$assunto1->codAs, $assunto2->codAs],
        ];

        $result = $this->service->createLivro($data);

        $this->assertEquals($data['Titulo'], $result->Titulo);
        $this->assertEquals($data['Preco'], $result->Preco);
        $this->assertDatabaseHas('Livro', ['Titulo' => 'Test Title']);
        $this->assertCount(2, $result->autores);
        $this->assertCount(2, $result->assuntos);
    }

    public function testCreateLivroValidationFails(): void
    {
        $this->expectException(ValidationException::class);

        $invalidData = [
            'Titulo' => '', // Invalid
            'Editora' => 'Test Editora',
            'AnoPublicacao' => '2020',
            'Preco' => 10,
        ];

        $this->service->createLivro($invalidData);
    }

    public function testCreateLivroInvalidYear(): void
    {
        $this->expectException(ValidationException::class);

        $invalidData = [
            'Titulo' => 'Test Title',
            'Editora' => 'Test Editora',
            'AnoPublicacao' => '2026',
            'Preco' => 10,
        ];

        $this->service->createLivro($invalidData);
    }

    public function testUpdateLivroSuccess(): void
    {
        $autor1 = Autor::factory()->create();
        $assunto1 = Assunto::factory()->create();
        $livro = User::factory()->create();

        $data = [
            'Titulo' => 'Updated Title',
            'Editora' => 'Updated Editora',
            'AnoPublicacao' => '2021',
            'Preco' => 1500,
            'autores' => [$autor1->CodAu],
            'assuntos' => [$assunto1->codAs],
        ];

        $this->service->updateLivro($livro->Codl, $data);

        $result = $this->service->getLivroById($livro->Codl);

        $this->assertEquals($data['Titulo'], $result->Titulo);
        $this->assertEquals($data['Preco'], $result->Preco);
        $this->assertDatabaseHas('Livro', ['Titulo' => 'Updated Title']);
        $this->assertCount(1, $result->autores);
        $this->assertCount(1, $result->assuntos);
    }

    public function testUpdateLivroValidationFails(): void
    {
        $livro = User::factory()->create();

        $this->expectException(ValidationException::class);

        $invalidData = [
            'Titulo' => '',
            'Editora' => 'Test Editora',
            'AnoPublicacao' => '2020',
            'Preco' => 10,
        ];

        $this->service->updateLivro($livro->Codl, $invalidData);
    }

    public function testDeleteLivro(): void
    {
        $livro = User::factory()->create();

        $this->service->deleteLivro($livro->Codl);

        $this->assertDatabaseMissing('Livro', ['Codl' => $livro->Codl]);
    }

    public function testGetAutoresAndAssuntos(): void
    {
        Autor::factory()->count(2)->create();
        Assunto::factory()->count(3)->create();

        $result = $this->service->getAutoresAndAssuntos();

        $this->assertCount(2, $result['autores']);
        $this->assertCount(3, $result['assuntos']);
    }
}
