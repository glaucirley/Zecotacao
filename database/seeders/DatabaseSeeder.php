<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Equipe;
use App\Models\Parceiro;
use App\Models\Produto;
use App\Models\ParametroSistema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed System Parameters
        ParametroSistema::create([
            'chave' => 'REENVIO_PARCIAL_MODO',
            'valor' => 'RECALCULA_TUDO',
            'descricao' => 'Modo de processamento de cotacoes devolvidas ao reabrir para edicao (RECALCULA_TUDO ou SO_ITENS_ALTERADOS)',
            'tipo' => 'texto',
            'editavel_por' => 'diretor',
        ]);

        ParametroSistema::create([
            'chave' => 'VALIDADE_PADRAO_HORAS',
            'valor' => '24',
            'descricao' => 'Validade padrao em horas de uma nova cotacao',
            'tipo' => 'numero',
            'editavel_por' => 'diretor',
        ]);

        ParametroSistema::create([
            'chave' => 'EXIGE_ANEXO_JUSTIFICATIVA',
            'valor' => 'true',
            'descricao' => 'Exige anexo de comprovante/documento ao enviar justificativa de desconto abaixo do preco minimo',
            'tipo' => 'booleano',
            'editavel_por' => 'diretor',
        ]);

        ParametroSistema::create([
            'chave' => 'DESCONTO_AVALIACAO_MODO',
            'valor' => 'ITEM_A_ITEM',
            'descricao' => 'Modo de avaliacao de desconto para alcada de aprovacao (ITEM_A_ITEM ou MEDIA_TOTAL)',
            'tipo' => 'texto',
            'editavel_por' => 'diretor',
        ]);

        // Sankhya Oracle Connection Parameters
        ParametroSistema::create([
            'chave' => 'SANKHYA_CONN_TIPO',
            'valor' => 'DIRETO',
            'descricao' => 'Tipo de conexao com o banco de dados do Sankhya (DIRETO ou SSH_TUNNEL)',
            'tipo' => 'texto',
            'editavel_por' => 'administrador',
        ]);

        ParametroSistema::create([
            'chave' => 'SANKHYA_DB_HOST',
            'valor' => '127.0.0.1',
            'descricao' => 'Endereço IP ou hostname do banco de dados Oracle do Sankhya',
            'tipo' => 'texto',
            'editavel_por' => 'administrador',
        ]);

        ParametroSistema::create([
            'chave' => 'SANKHYA_DB_PORT',
            'valor' => '1521',
            'descricao' => 'Porta do banco de dados Oracle do Sankhya',
            'tipo' => 'numero',
            'editavel_por' => 'administrador',
        ]);

        ParametroSistema::create([
            'chave' => 'SANKHYA_DB_NAME',
            'valor' => 'XE',
            'descricao' => 'Nome do Serviço ou SID do Oracle do Sankhya',
            'tipo' => 'texto',
            'editavel_por' => 'administrador',
        ]);

        ParametroSistema::create([
            'chave' => 'SANKHYA_DB_USER',
            'valor' => 'sankhya',
            'descricao' => 'Usuário do banco de dados Oracle do Sankhya',
            'tipo' => 'texto',
            'editavel_por' => 'administrador',
        ]);

        ParametroSistema::create([
            'chave' => 'SANKHYA_DB_PASS',
            'valor' => Crypt::encryptString('sankhya_senha_padrao'),
            'descricao' => 'Senha criptografada do banco de dados Oracle do Sankhya',
            'tipo' => 'texto',
            'editavel_por' => 'administrador',
        ]);

        ParametroSistema::create([
            'chave' => 'SANKHYA_SSH_HOST',
            'valor' => '',
            'descricao' => 'Host/IP do servidor SSH intermediário (informativo)',
            'tipo' => 'texto',
            'editavel_por' => 'administrador',
        ]);

        ParametroSistema::create([
            'chave' => 'SANKHYA_SSH_PORT',
            'valor' => '22',
            'descricao' => 'Porta do servidor SSH intermediário (informativo)',
            'tipo' => 'numero',
            'editavel_por' => 'administrador',
        ]);

        ParametroSistema::create([
            'chave' => 'SANKHYA_SSH_USER',
            'valor' => '',
            'descricao' => 'Usuário do servidor SSH intermediário (informativo)',
            'tipo' => 'texto',
            'editavel_por' => 'administrador',
        ]);

        // Priority and Big Accounts parameters
        ParametroSistema::create([
            'chave' => 'ALCADA_GRANDE_CONTA_VALOR',
            'valor' => '10000.00',
            'descricao' => 'Valor total limite (igual ou maior) para classificar uma cotação como Grande Conta / Alta Prioridade',
            'tipo' => 'numero',
            'editavel_por' => 'diretor',
        ]);

        ParametroSistema::create([
            'chave' => 'ALCADA_GRANDE_CONTA_QTD',
            'valor' => '100',
            'descricao' => 'Quantidade total de itens (igual ou maior) para classificar uma cotação como Grande Conta / Alta Prioridade',
            'tipo' => 'numero',
            'editavel_por' => 'diretor',
        ]);

        ParametroSistema::create([
            'chave' => 'ALCADA_GRANDE_CONTA_MARGEM',
            'valor' => '15.00',
            'descricao' => 'Margem geral calculada (igual ou menor) para classificar uma cotação como Grande Conta / Alta Prioridade',
            'tipo' => 'numero',
            'editavel_por' => 'diretor',
        ]);

        // 2. Seed Users (Director & Billing)
        $diretor = User::create([
            'nome' => 'Carlos Diretor',
            'papel' => 'diretor',
            'email' => 'diretor@zecotacao.com.br',
            'senha_hash' => Hash::make('diretor123'),
            'telefone' => '(11) 98888-7777',
            'ativo' => true,
        ]);

        $faturamento = User::create([
            'nome' => 'Aline Faturamento',
            'papel' => 'faturamento',
            'email' => 'faturamento@zecotacao.com.br',
            'senha_hash' => Hash::make('faturamento123'),
            'telefone' => '(11) 97777-6666',
            'ativo' => true,
        ]);

        // 3. Seed Gestores (who will manage the teams)
        $gestorNorte = User::create([
            'nome' => 'Marcos Gestor Norte',
            'papel' => 'gestor',
            'email' => 'gestornorte@zecotacao.com.br',
            'senha_hash' => Hash::make('gestor123'),
            'telefone' => '(11) 96666-5555',
            'limite_desconto_percentual' => 15.00, // 15% discount limit
            'ativo' => true,
        ]);

        $gestorSul = User::create([
            'nome' => 'Paula Gestor Sul',
            'papel' => 'gestor',
            'email' => 'gestorsul@zecotacao.com.br',
            'senha_hash' => Hash::make('gestor123'),
            'telefone' => '(11) 95555-4444',
            'limite_desconto_percentual' => 10.00, // 10% discount limit
            'ativo' => true,
        ]);

        // 4. Seed Teams
        $equipeNorte = Equipe::create([
            'nome' => 'Equipe Vet Norte',
            'gestor_id' => $gestorNorte->id,
        ]);

        $equipeSul = Equipe::create([
            'nome' => 'Equipe Vet Sul',
            'gestor_id' => $gestorSul->id,
        ]);

        // Link gestores to their teams (optional but good for schema integrity)
        $gestorNorte->update(['equipe_id' => $equipeNorte->id]);
        $gestorSul->update(['equipe_id' => $equipeSul->id]);

        // 5. Seed Representatives linked to teams
        User::create([
            'nome' => 'Roberto Vendedor Norte',
            'papel' => 'representante',
            'equipe_id' => $equipeNorte->id,
            'email' => 'rep1@zecotacao.com.br',
            'senha_hash' => Hash::make('rep123'),
            'telefone' => '(11) 94444-3333',
            'codigo_sankhya' => 'REP001',
            'ativo' => true,
        ]);

        User::create([
            'nome' => 'Sandra Vendedora Sul',
            'papel' => 'representante',
            'equipe_id' => $equipeSul->id,
            'email' => 'rep2@zecotacao.com.br',
            'senha_hash' => Hash::make('rep123'),
            'telefone' => '(11) 93333-2222',
            'codigo_sankhya' => 'REP002',
            'ativo' => true,
        ]);

        // 6. Seed Partners (Clients)
        Parceiro::create([
            'codigo_sankhya' => 'PAR001',
            'razao_social' => 'Clinica Veterinaria Pet Feliz Ltda',
            'nome_fantasia' => 'Pet Feliz',
            'cnpj' => '12345678000190',
            'telefone' => '(11) 3222-1111',
            'email' => 'contato@petfeliz.com.br',
            'endereco' => 'Rua das Flores, 123',
            'cidade' => 'Sao Paulo',
            'uf' => 'SP',
            'cep' => '01234000',
            'ativo' => true,
        ]);

        Parceiro::create([
            'codigo_sankhya' => 'PAR002',
            'razao_social' => 'Hospital Veterinario Vida Animal S/A',
            'nome_fantasia' => 'Vida Animal',
            'cnpj' => '98765432000121',
            'telefone' => '(21) 3555-4444',
            'email' => 'financeiro@vidaanimal.com.br',
            'endereco' => 'Av. Brasil, 5000',
            'cidade' => 'Rio de Janeiro',
            'uf' => 'RJ',
            'cep' => '20000000',
            'ativo' => true,
        ]);

        // 7. Seed Products
        Produto::create([
            'codigo_sankhya' => 'PROD001',
            'descricao' => 'Vacina V10 Importada Virbac 1 Dose',
            'unidade' => 'UN',
            'ativo' => true,
        ]);

        Produto::create([
            'codigo_sankhya' => 'PROD002',
            'descricao' => 'Racao Canina Gastrointestinal Low Fat 10kg',
            'unidade' => 'CX',
            'ativo' => true,
        ]);

        Produto::create([
            'codigo_sankhya' => 'PROD003',
            'descricao' => 'Anti-inflamatorio Vetflan Suspensao 10ml',
            'unidade' => 'UN',
            'ativo' => true,
        ]);
    }
}
