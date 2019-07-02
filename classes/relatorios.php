<?php
class Relatorios{
    public function __construct($db, $spreadsheet, $writer){
        $this->pdo = $db;
        $this->spreadsheet = $spreadsheet;
        $this->writer = $writer;
    }

    public function reportProdutosCadastrados($filters){
        $sheet = $this->spreadsheet->getActiveSheet(); //retornando a aba ativa
        $classProdutos = new Produtos($this->pdo);

        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.$key.' = :'.$key;
                $i++;
            }
        }
        $sql = '
            select
                p.id,
                p.nome,
                p.ativo,
                -- p.id_cor idCor,
                c.nome nomeCor,
                p.id_linha_de_producao idLinhaDeProducao,
                lp.nome nomeLinhaDeProducao,
                -- plsc.id_setor idSetor,
                s.nome nomeSetor,
                lps.ordem,
                -- plsc.id_conjunto idConjunto,
                con.nome nomeConjunto,
                sp.nome nomeSubproduto
            from pcp_produtos p
            join pcp_cores c on c.id = p.id_cor
            join pcp_linhas_de_producao lp on lp.id = p.id_linha_de_producao
            join pcp_produtos_linhas_setores_conjunto plsc on plsc.id_produto = p.id
            join pcp_setores s on s.id = plsc.id_setor
            join pcp_conjuntos con on con.id = plsc.id_conjunto
            join pcp_conjuntos_subprodutos cs on cs.id_conjunto = c.id
            join pcp_subprodutos sp on sp.id = cs.id_subproduto
            join pcp_linhas_de_producao_setores lps on lps.id_linha_de_producao = lp.id and lps.id_setor = s.id
            '.$where.'
            order by p.id, lps.ordem, s.id;
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $sheet->setCellValue('A1', 'Produto');
        $sheet->setCellValue('B1', 'Ativo');
        $sheet->setCellValue('C1', 'Cor');
        $sheet->setCellValue('D1', 'Linha de Produção');
        $sheet->setCellValue('E1', 'Setor');
        $sheet->setCellValue('F1', 'Conjunto');
        $sheet->setCellValue('G1', 'Subproduto');
        

        $i = 2;
        while ($row = $stmt->fetch()) {
            $ativo = $row->ativo === 'Y' ? 'Sim' : 'Não';
            $sheet->setCellValue('A'.$i, $row->nome);
            $sheet->setCellValue('B'.$i, $ativo);
            $sheet->setCellValue('C'.$i, $row->nomeCor);
            $sheet->setCellValue('D'.$i, $row->nomeLinhaDeProducao);
            $sheet->setCellValue('E'.$i, $row->nomeSetor);
            $sheet->setCellValue('F'.$i, $row->nomeConjunto);
            $sheet->setCellValue('G'.$i, $row->nomeSubproduto);
            $i++;
        }
        $currDateTimeObj = new DateTime();
        $currDateTime = $currDateTimeObj->format('d-m-Y-H-i-s');
        $this->writer->save('reports/produtosCadastrados-'.$currDateTime.'.xlsx');
        
        return json_encode(array(
            'success' => true,
            'msg' => 'Relatório gerado com sucesso.',
            'payload' => array(
                'url' => 'http://'.$_SERVER['SERVER_NAME'].'/reports/produtosCadastrados-'.$currDateTime.'.xlsx'
            )
        ));
    }

    public function reportProducoes($filters){
        $sheet = $this->spreadsheet->getActiveSheet(); //retornando a aba ativa
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.'pa.'.$key.' = :'.$key;
                $i++;
            }
        }
        $sql = '
            select
                -- pa.id_producao,
                pro.nome nomeProducao,
                p.nome nomeProduto,
                cor.nome nomeCor,
                -- pa.id id_acompanhamento,
                -- pa.id_setor,
                s.nome nomeSetor,
                lps.ordem,
                pa.data_inicial dataInicial,
                c.nome nomeConjunto,
                -- pa.id_produto,
                -- p.id_cor,
                -- pa.id_subproduto,
                ss.nome nomeSubproduto,
                pa.realizado_quantidade realizadoQuantidade,
                pa.total_quantidade totalQuantidade
            from pcp_producoes_acompanhamento pa
            join pcp_producoes pro on pro.id = pa.id_producao
            join pcp_produtos p on p.id = pa.id_produto
            join pcp_setores s on s.id = pa.id_setor
            join pcp_linhas_de_producao_setores lps on lps.id_setor = pa.id_setor and lps.id_linha_de_producao = p.id_linha_de_producao
            join pcp_produtos_linhas_setores_conjunto plsc on plsc.id_produto = pa.id_produto and plsc.id_setor = pa.id_setor
            join pcp_conjuntos c on c.id = plsc.id_conjunto
            join pcp_subprodutos ss on ss.id = pa.id_subproduto
            join pcp_cores cor on cor.id = p.id_cor
            '.$where.'
            order by pa.id_producao, pa.id_produto, lps.ordem, pa.id_setor, pa.id_subproduto;
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $sheet->setCellValue('A1', 'Produção');
        $sheet->setCellValue('B1', 'Produto');
        $sheet->setCellValue('C1', 'Cor');
        $sheet->setCellValue('D1', 'Setor');
        $sheet->setCellValue('E1', 'Ordem');
        $sheet->setCellValue('F1', 'Data Início');
        $sheet->setCellValue('G1', 'Conjunto');
        $sheet->setCellValue('H1', 'Subproduto');
        $sheet->setCellValue('I1', 'Quantidade Realizado');
        $sheet->setCellValue('J1', 'Quantidade Total');
        

        $i = 2;
        while ($row = $stmt->fetch()) {
            $sheet->setCellValue('A'.$i, $row->nomeProducao);
            $sheet->setCellValue('B'.$i, $row->nomeProduto);
            $sheet->setCellValue('C'.$i, $row->nomeCor);
            $sheet->setCellValue('D'.$i, $row->nomeSetor);
            $sheet->setCellValue('E'.$i, $row->ordem);
            $sheet->setCellValue('F'.$i, $row->dataInicial);
            $sheet->setCellValue('G'.$i, $row->nomeConjunto);
            $sheet->setCellValue('H'.$i, $row->nomeSubproduto);
            $sheet->setCellValue('I'.$i, $row->realizadoQuantidade);
            $sheet->setCellValue('J'.$i, $row->totalQuantidade);
            $i++;
        }
        $currDateTimeObj = new DateTime();
        $currDateTime = $currDateTimeObj->format('d-m-Y-H-i-s');
        $this->writer->save('reports/producoes-'.$currDateTime.'.xlsx');
        
        return json_encode(array(
            'success' => true,
            'msg' => 'Relatório gerado com sucesso.',
            'payload' => array(
                'url' => 'http://'.$_SERVER['SERVER_NAME'].'/reports/producoes-'.$currDateTime.'.xlsx'
            )
        ));
    }

    public function reportBonusPontuacao($filters){
        $sheet = $this->spreadsheet->getActiveSheet(); //retornando a aba ativa
        /*print_r($filters);

        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.$key.' = :'.$key;
                $i++;
            }
        }*/
        $sql = '
            SELECT cb.id_funcionario, f.nome, f.matricula, sum(cb.pontos) pontos
            FROM pcp_codigo_de_barras cb
            JOIN pcp_funcionarios f ON f.id = cb.id_funcionario
            where cb.dt_lancamento >= "'.$filters['dataInicial'].'" and cb.dt_lancamento <= "'.$filters['dataFinal'].'"
            AND cb.lancado = "Y"
            GROUP BY cb.id_funcionario
            ORDER BY f.nome
        ';
        //echo "\nsql: ".$sql."\n";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Funcionário');
        $sheet->setCellValue('C1', 'Matrícula');
        $sheet->setCellValue('D1', 'Pontos');

        $i = 2;
        while ($row = $stmt->fetch()) {
            //print_r($row);
            $sheet->setCellValue('A'.$i, $row->id_funcionario);
            $sheet->setCellValue('B'.$i, $row->nome);
            $sheet->setCellValue('C'.$i, $row->matricula);
            $sheet->setCellValue('D'.$i, $row->pontos);
            $i++;
        }
        $currDateTimeObj = new DateTime();
        $currDateTime = $currDateTimeObj->format('d-m-Y-H-i-s');
        $this->writer->save('reports/funcionariosPontuacoes-'.$currDateTime.'.xlsx');
        
        return json_encode(array(
            'success' => true,
            'msg' => 'Relatório gerado com sucesso.',
            'payload' => array(
                'url' => 'http://'.$_SERVER['SERVER_NAME'].'/reports/funcionariosPontuacoes-'.$currDateTime.'.xlsx'
            )
        ));
    }

    public function reportGeralLancamentoProducao($filters){
        $sheet = $this->spreadsheet->getActiveSheet(); //retornando a aba ativa

        $sql = '
            SELECT
                cb.id_producao, p.nome nome_producao,
                cb.id_produto, pro.nome nome_produto, pro.codigo codigo_produto,
                cb.id_setor, s.nome nome_setor,
                cb.id_subproduto, ss.nome nome_subproduto,
                cb.codigo codigo_barras,
                cb.id_funcionario, f.nome nome_funcionario,
                cb.dt_lancamento data_lancamento,
                cb.pontos
            FROM pcp_codigo_de_barras cb
            JOIN pcp_producoes p ON p.id = cb.id_producao
            JOIN pcp_produtos pro ON pro.id = cb.id_produto
            JOIN pcp_setores s ON s.id = cb.id_setor
            JOIN pcp_subprodutos ss ON ss.id = cb.id_subproduto
            JOIN pcp_funcionarios f ON f.id = cb.id_funcionario
            WHERE
                cb.lancado = "Y"
                and cb.dt_lancamento >= "'.$filters['dataInicial'].'" and cb.dt_lancamento <= "'.$filters['dataFinal'].'"
        ';

        //echo "\nsql: ".$sql."\n";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $sheet->setCellValue('A1', 'ID Produção');
        $sheet->setCellValue('B1', 'Produção');
        $sheet->setCellValue('C1', 'ID Produto');
        $sheet->setCellValue('D1', 'Cód. Produto');
        $sheet->setCellValue('E1', 'Produto');
        $sheet->setCellValue('F1', 'ID Setor');
        $sheet->setCellValue('G1', 'Setor');
        $sheet->setCellValue('H1', 'ID Subproduto');
        $sheet->setCellValue('I1', 'Subproduto');
        $sheet->setCellValue('J1', 'Código');
        $sheet->setCellValue('K1', 'ID Funcionário');
        $sheet->setCellValue('L1', 'Funcionário');
        $sheet->setCellValue('M1', 'Data Lançamento');
        $sheet->setCellValue('N1', 'Pontos');

        $i = 2;
        while ($row = $stmt->fetch()) {
            $dataLancamentoDT = new DateTime($row->data_lancamento);
            $dataLancamento = $dataLancamentoDT->format('d/m/Y');

            $sheet->setCellValue('A'.$i, $row->id_producao);
            $sheet->setCellValue('B'.$i, $row->nome_producao);
            $sheet->setCellValue('C'.$i, $row->id_produto);
            $sheet->setCellValue('D'.$i, $row->codigo_produto);
            $sheet->setCellValue('E'.$i, $row->nome_produto);
            $sheet->setCellValue('F'.$i, $row->id_setor);
            $sheet->setCellValue('G'.$i, $row->nome_setor);
            $sheet->setCellValue('H'.$i, $row->id_subproduto);
            $sheet->setCellValue('I'.$i, $row->nome_subproduto);
            $sheet->setCellValue('J'.$i, $row->codigo_barras);
            $sheet->setCellValue('K'.$i, $row->id_funcionario);
            $sheet->setCellValue('L'.$i, $row->nome_funcionario);
            $sheet->setCellValue('M'.$i, $dataLancamento);
            $sheet->setCellValue('N'.$i, $row->pontos);

            $i++;
        }
        $currDateTimeObj = new DateTime();
        $currDateTime = $currDateTimeObj->format('d-m-Y-H-i-s');
        $this->writer->save('reports/geralLancamentoProducao-'.$currDateTime.'.xlsx');
        
        return json_encode(array(
            'success' => true,
            'msg' => 'Relatório gerado com sucesso.',
            'payload' => array(
                'url' => 'http://'.$_SERVER['SERVER_NAME'].'/reports/geralLancamentoProducao-'.$currDateTime.'.xlsx'
            )
        ));
    }
}