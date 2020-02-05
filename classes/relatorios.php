<?php
class Relatorios{
    public function __construct($db, $spreadsheet, $writer){
        ini_set('memory_limit', '-1');
        set_time_limit(0);
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
                p.codigo,
                p.sku,
                p.id_cor idCor,
                c.nome nomeCor,
                p.id_linha_de_producao idLinhaDeProducao,
                p.mao_de_obra,
                p.materia_prima,
                lp.nome nomeLinhaDeProducao,
                plsc.id_setor idSetor,
                s.nome nomeSetor,
                lps.ordem,
                plsc.id_conjunto idConjunto,
                con.nome nomeConjunto,
                sp.id idSubproduto,
                sp.nome nomeSubproduto,
                cs.pontos_subproduto
            from pcp_produtos p
            join pcp_cores c on c.id = p.id_cor
            join pcp_linhas_de_producao lp on lp.id = p.id_linha_de_producao
            join pcp_produtos_linhas_setores_conjunto plsc on plsc.id_produto = p.id
            join pcp_setores s on s.id = plsc.id_setor
            join pcp_conjuntos con on con.id = plsc.id_conjunto
            join pcp_conjuntos_subprodutos cs on cs.id_conjunto = con.id
            join pcp_subprodutos sp on sp.id = cs.id_subproduto
            join pcp_linhas_de_producao_setores lps on lps.id_linha_de_producao = lp.id and lps.id_setor = s.id
            '.$where.'
            order by p.id, lps.ordem, s.id;
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $sheet->setCellValue('A1', 'ID Produto');
        $sheet->setCellValue('B1', 'Produto');
        $sheet->setCellValue('C1', 'Código');
        $sheet->setCellValue('D1', 'SKU');
        $sheet->setCellValue('E1', 'ID Cor');
        $sheet->setCellValue('F1', 'Cor');
        $sheet->setCellValue('G1', 'ID Linha de Produção');
        $sheet->setCellValue('H1', 'Linha de Produção');
        $sheet->setCellValue('I1', 'ID Setor');
        $sheet->setCellValue('J1', 'Setor');
        $sheet->setCellValue('K1', 'ID Conjunto');
        $sheet->setCellValue('L1', 'Conjunto');
        $sheet->setCellValue('M1', 'ID Subproduto');
        $sheet->setCellValue('N1', 'Subproduto');
        $sheet->setCellValue('O1', 'Ativo');
        $sheet->setCellValue('P1', 'Valor Mão de Obra');
        $sheet->setCellValue('Q1', 'Valor Matéria Prima');
        $sheet->setCellValue('R1', 'Pontos');

        $i = 2;
        while ($row = $stmt->fetch()) {
            $ativo = $row->ativo === 'Y' ? 'Sim' : 'Não';
            $sheet->setCellValue('A'.$i, $row->id);
            $sheet->setCellValue('B'.$i, $row->nome);
            $sheet->setCellValue('C'.$i, $row->codigo);
            $sheet->setCellValue('D'.$i, $row->sku);
            $sheet->setCellValue('E'.$i, $row->idCor);
            $sheet->setCellValue('F'.$i, $row->nomeCor);
            $sheet->setCellValue('G'.$i, $row->idLinhaDeProducao);
            $sheet->setCellValue('H'.$i, $row->nomeLinhaDeProducao);
            $sheet->setCellValue('I'.$i, $row->idSetor);
            $sheet->setCellValue('J'.$i, $row->nomeSetor);
            $sheet->setCellValue('K'.$i, $row->idConjunto);
            $sheet->setCellValue('L'.$i, $row->nomeConjunto);
            $sheet->setCellValue('M'.$i, $row->idSubproduto);
            $sheet->setCellValue('N'.$i, $row->nomeSubproduto);
            $sheet->setCellValue('O'.$i, $ativo);
            $sheet->setCellValue('P'.$i, $row->mao_de_obra);
            $sheet->setCellValue('Q'.$i, $row->materia_prima);
            $sheet->setCellValue('R'.$i, $row->pontos_subproduto);
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

    public function reportFuncionariosCadastrados($filters){
        $sheet = $this->spreadsheet->getActiveSheet(); //retornando a aba ativa
        $classFuncionarios = new Funcionarios($this->pdo);

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
            SELECT *, CONCAT("999999-", id) AS cod_barras
            FROM pcp_funcionarios
            '.$where.';
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $sheet->setCellValue('A1', 'ID Funcionário');
        $sheet->setCellValue('B1', 'Matrícula');
        $sheet->setCellValue('C1', 'Nome');
        $sheet->setCellValue('D1', 'Salário');
        $sheet->setCellValue('E1', 'Salário Base');
        $sheet->setCellValue('F1', 'Setor');
        $sheet->setCellValue('G1', 'Ativo');
        $sheet->setCellValue('H1', 'Cód. Barras');
        

        $i = 2;
        while ($row = $stmt->fetch()) {
            $ativo = $row->ativo === 'Y' ? 'Sim' : 'Não';
            $sheet->setCellValue('A'.$i, $row->id);
            $sheet->setCellValue('B'.$i, $row->matricula);
            $sheet->setCellValue('C'.$i, $row->nome);
            $sheet->setCellValue('D'.$i, $row->salario);
            $sheet->setCellValue('E'.$i, $row->salario_base);
            $sheet->setCellValue('F'.$i, $row->setor);
            $sheet->setCellValue('G'.$i, $ativo);
            $sheet->setCellValue('H'.$i, $row->cod_barras);
            $i++;
        }
        $currDateTimeObj = new DateTime();
        $currDateTime = $currDateTimeObj->format('d-m-Y-H-i-s');
        $this->writer->save('reports/funcionariosCadastrados-'.$currDateTime.'.xlsx');
        
        return json_encode(array(
            'success' => true,
            'msg' => 'Relatório gerado com sucesso.',
            'payload' => array(
                'url' => 'http://'.$_SERVER['SERVER_NAME'].'/reports/funcionariosCadastrados-'.$currDateTime.'.xlsx'
            )
        ));
    }

    public function reportProducoes($filters){
        $sheet = $this->spreadsheet->getActiveSheet();
        /*
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.'pro.'.$key.' = :'.$key;
                $i++;
            }
        }
        */
        $sql = '
            select
                pa.id_producao idProducao,
                pro.nome nomeProducao,
                pa.id_produto idProduto,
                p.nome nomeProduto,
                p.mao_de_obra,
                p.materia_prima,
                cor.nome nomeCor,
                pa.id id_acompanhamento,
                pa.id_setor idSetor,
                s.nome nomeSetor,
                lps.ordem,
                pa.data_inicial dataInicial,
                plsc.id_conjunto idConjunto,
                c.nome nomeConjunto,
                pa.id_produto,
                -- p.id_cor,
                pa.id_subproduto idSubproduto,
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
            where pro.data_inicial between "'.$filters['dataInicial'].'" and "'.$filters['dataFinal'].'"
            order by pa.id_producao, pa.id_produto, lps.ordem, pa.id_setor, pa.id_subproduto;
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $sheet->setCellValue('A1', 'Código');
        $sheet->setCellValue('B1', 'Produção');
        $sheet->setCellValue('C1', 'Produto');
        $sheet->setCellValue('D1', 'Cor');
        $sheet->setCellValue('E1', 'Setor');
        $sheet->setCellValue('F1', 'Ordem');
        $sheet->setCellValue('G1', 'Data Início');
        $sheet->setCellValue('H1', 'Conjunto');
        $sheet->setCellValue('I1', 'Subproduto');
        $sheet->setCellValue('J1', 'Quantidade Realizado');
        $sheet->setCellValue('K1', 'Quantidade Total');
        $sheet->setCellValue('L1', 'Valor Mão de Obra');
        $sheet->setCellValue('M1', 'Valor Matéria Prima');
        

        $i = 2;
        while ($row = $stmt->fetch()) {
            $sheet->setCellValue('A'.$i, $row->idProducao.'-'.$row->idProduto.'-'.$row->idConjunto.'-'.$row->idSetor.'-'.$row->idSubproduto);
            $sheet->setCellValue('B'.$i, $row->nomeProducao);
            $sheet->setCellValue('C'.$i, $row->nomeProduto);
            $sheet->setCellValue('D'.$i, $row->nomeCor);
            $sheet->setCellValue('E'.$i, $row->nomeSetor);
            $sheet->setCellValue('F'.$i, $row->ordem);
            $sheet->setCellValue('G'.$i, $row->dataInicial);
            $sheet->setCellValue('H'.$i, $row->nomeConjunto);
            $sheet->setCellValue('I'.$i, $row->nomeSubproduto);
            $sheet->setCellValue('J'.$i, $row->realizadoQuantidade);
            $sheet->setCellValue('K'.$i, $row->totalQuantidade);
            $sheet->setCellValue('L'.$i, $row->mao_de_obra);
            $sheet->setCellValue('M'.$i, $row->materia_prima);
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
  
        $sql = '
            SELECT cb.id_funcionario, f.nome, f.matricula, f.linha, f.setor, f.salario, f.salario_base, COUNT(cb.id) quantidade, sum(cb.pontos) pontos
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
        $sheet->setCellValue('D1', 'Cód. Barras');
        $sheet->setCellValue('E1', 'Linha');
        $sheet->setCellValue('F1', 'Setor');
        $sheet->setCellValue('G1', 'Salário');
        $sheet->setCellValue('H1', 'Salário Base');
        $sheet->setCellValue('I1', 'Quantidade');
        $sheet->setCellValue('J1', 'Pontos');

        $i = 2;
        while ($row = $stmt->fetch()) {
            //print_r($row);
            $sheet->setCellValue('A'.$i, $row->id_funcionario);
            $sheet->setCellValue('B'.$i, $row->nome);
            $sheet->setCellValue('C'.$i, $row->matricula);
            $sheet->setCellValue('D'.$i, '999999-'.$row->id_funcionario);
            $sheet->setCellValue('E'.$i, $row->linha);
            $sheet->setCellValue('F'.$i, $row->setor);
            $sheet->setCellValue('G'.$i, $row->salario);
            $sheet->setCellValue('H'.$i, $row->salario_base);
            $sheet->setCellValue('I'.$i, $row->quantidade);
            $sheet->setCellValue('J'.$i, $row->pontos);
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
        set_time_limit(0);
        $sheet = $this->spreadsheet->getActiveSheet(); //retornando a aba ativa

        $sql = '
            SELECT
                cb.id_producao, p.nome nome_producao,
                cb.id_produto, pro.nome nome_produto, pro.codigo codigo_produto,
                cor.nome cor_produto, pro.sku, pro.mao_de_obra, pro.materia_prima,
                cb.id_setor, s.nome nome_setor,
                cb.id_subproduto, ss.nome nome_subproduto,
                cb.codigo codigo_barras,
                cb.id_funcionario, f.nome nome_funcionario,
                cb.dt_lancamento data_lancamento,
                cb.qtdeDefeito,
                if(cb.dt_conferencia <> "0000-00-00", cb.dt_conferencia, NULL) data_conferencia,
                if(cb.dt_defeito <> "0000-00-00", cb.dt_defeito, NULL) data_defeito,
                cb.pontos
            FROM pcp_codigo_de_barras cb
            JOIN pcp_producoes p ON p.id = cb.id_producao
            JOIN pcp_produtos pro ON pro.id = cb.id_produto
            JOIN pcp_cores cor on cor.id = pro.id_cor
            JOIN pcp_setores s ON s.id = cb.id_setor
            JOIN pcp_subprodutos ss ON ss.id = cb.id_subproduto
            LEFT JOIN pcp_funcionarios f ON f.id = cb.id_funcionario
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
        $sheet->setCellValue('E1', 'SKU');
        $sheet->setCellValue('F1', 'Produto');
        $sheet->setCellValue('G1', 'ID Setor');
        $sheet->setCellValue('H1', 'Setor');
        $sheet->setCellValue('I1', 'ID Subproduto');
        $sheet->setCellValue('J1', 'Subproduto');
        $sheet->setCellValue('K1', 'Código');
        $sheet->setCellValue('L1', 'ID Funcionário');
        $sheet->setCellValue('M1', 'Funcionário');
        $sheet->setCellValue('N1', 'Data Lançamento');
        $sheet->setCellValue('O1', 'Data Conferência');
        $sheet->setCellValue('P1', 'Pontos');
        $sheet->setCellValue('Q1', 'Valor Mão de Obra');
        $sheet->setCellValue('R1', 'Valor Matéria Prima');
        $sheet->setCellValue('S1', 'Data Defeito');
        $sheet->setCellValue('T1', 'Qtde Defeitos');


        $i = 2;
        while($row = $stmt->fetch()) {
            $dataLancamentoDT = new DateTime($row->data_lancamento);
            $dataLancamento = $dataLancamentoDT->format('d/m/Y');

            if($row->data_conferencia){
                $dataConferenciaDT = new DateTime($row->data_conferencia);
                $dataConferencia = $dataConferenciaDT->format('d/m/Y');
            }
            else
                $dataConferencia = '';

            if($row->data_defeito){
                $dataDefeitoDT = new DateTime($row->data_defeito);
                $dataDefeito = $dataDefeitoDT->format('d/m/Y');
            }
            else
                $dataDefeito = '';

            $sheet->setCellValue('A'.$i, $row->id_producao);
            $sheet->setCellValue('B'.$i, $row->nome_producao);
            $sheet->setCellValue('C'.$i, $row->id_produto);
            $sheet->setCellValue('D'.$i, $row->codigo_produto);
            $sheet->setCellValue('E'.$i, $row->sku);
            $sheet->setCellValue('F'.$i, $row->nome_produto.' ('.$row->cor_produto.')');
            $sheet->setCellValue('G'.$i, $row->id_setor);
            $sheet->setCellValue('H'.$i, $row->nome_setor);
            $sheet->setCellValue('I'.$i, $row->id_subproduto);
            $sheet->setCellValue('J'.$i, $row->nome_subproduto);
            $sheet->setCellValue('K'.$i, $row->codigo_barras);
            $sheet->setCellValue('L'.$i, $row->id_funcionario);
            $sheet->setCellValue('M'.$i, $row->nome_funcionario);
            $sheet->setCellValue('N'.$i, $dataLancamento);
            $sheet->setCellValue('O'.$i, $dataConferencia);
            $sheet->setCellValue('P'.$i, $row->pontos);
            $sheet->setCellValue('Q'.$i, $row->mao_de_obra);
            $sheet->setCellValue('R'.$i, $row->materia_prima);
            $sheet->setCellValue('S'.$i, $dataDefeito);
            $sheet->setCellValue('T'.$i, $row->qtdeDefeito);

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

    public function reportNaoProduzidos($filters){
        set_time_limit(0);
        $sheet = $this->spreadsheet->getActiveSheet(); //retornando a aba ativa

        $sql = '
            SELECT
                cb.id_producao, p.nome nome_producao,
                cb.id_produto, pro.nome nome_produto, pro.codigo codigo_produto, cor.nome cor_produto,
                pro.mao_de_obra, pro.materia_prima,
                cb.id_setor, s.nome nome_setor,
                cb.id_subproduto, ss.nome nome_subproduto,
                cb.codigo codigo_barras,
                cb.pontos
            FROM pcp_codigo_de_barras cb
            JOIN pcp_producoes p ON p.id = cb.id_producao
            JOIN pcp_produtos pro ON pro.id = cb.id_produto
            JOIN pcp_cores cor on cor.id = pro.id_cor
            JOIN pcp_setores s ON s.id = cb.id_setor
            JOIN pcp_subprodutos ss ON ss.id = cb.id_subproduto
            WHERE
                cb.id_producao = :idProducao
                AND cb.lancado = "N"
        ';

        //echo "\nsql: ".$sql."\n";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':idProducao', $filters['idProducao']);
        $stmt->execute();

        $sheet->setCellValue('A1', 'ID Produção');
        $sheet->setCellValue('B1', 'Produção');
        $sheet->setCellValue('C1', 'ID Produto');
        $sheet->setCellValue('D1', 'Cód. Produto');
        $sheet->setCellValue('E1', 'Produto');
        $sheet->setCellValue('F1', 'Cor');
        $sheet->setCellValue('G1', 'ID Setor');
        $sheet->setCellValue('H1', 'Setor');
        $sheet->setCellValue('I1', 'ID Subproduto');
        $sheet->setCellValue('J1', 'Subproduto');
        $sheet->setCellValue('K1', 'Descrição');
        $sheet->setCellValue('L1', 'Código');
        $sheet->setCellValue('M1', 'Pontos');
        $sheet->setCellValue('N1', 'Valor Mão de Obra');
        $sheet->setCellValue('O1', 'Valor Matéria Prima');

        $i = 2;
        while($row = $stmt->fetch()) {
            $sheet->setCellValue('A'.$i, $row->id_producao);
            $sheet->setCellValue('B'.$i, $row->nome_producao);
            $sheet->setCellValue('C'.$i, $row->id_produto);
            $sheet->setCellValue('D'.$i, $row->codigo_produto);
            $sheet->setCellValue('E'.$i, $row->nome_produto);
            $sheet->setCellValue('F'.$i, $row->cor_produto);
            $sheet->setCellValue('G'.$i, $row->id_setor);
            $sheet->setCellValue('H'.$i, $row->nome_setor);
            $sheet->setCellValue('I'.$i, $row->id_subproduto);
            $sheet->setCellValue('J'.$i, $row->nome_subproduto);
            $sheet->setCellValue('K'.$i, $row->nome_produto.'-'.$row->cor_produto.'-'.$row->nome_subproduto);
            $sheet->setCellValue('L'.$i, $row->codigo_barras);
            $sheet->setCellValue('M'.$i, $row->pontos);
            $sheet->setCellValue('N'.$i, $row->mao_de_obra);
            $sheet->setCellValue('O'.$i, $row->materia_prima);
            $i++;
        }
        $currDateTimeObj = new DateTime();
        $currDateTime = $currDateTimeObj->format('d-m-Y-H-i-s');
        $this->writer->save('reports/reportNaoProduzidos-'.$currDateTime.'.xlsx');
        
        return json_encode(array(
            'success' => true,
            'msg' => 'Relatório gerado com sucesso.',
            'payload' => array(
                'url' => 'http://'.$_SERVER['SERVER_NAME'].'/reports/reportNaoProduzidos-'.$currDateTime.'.xlsx'
            )
        ));
    }

    public function reportEntradaInsumos($filters){
        $sheet = $this->spreadsheet->getActiveSheet(); //retornando a aba ativa
        $sql = '
            SELECT
                e.id id_entrada,
                e.dthr_entrada,
                e.id_usuario,
                u.nome nome_usuario,
                pins.id_pedido,
                pins.id_insumo,
                ins.nome nome_insumo,
                ins.ins,
                ei.quantidade
            FROM pcp_entradas e
            JOIN pcp_entrada_insumos ei ON ei.id_entrada = e.id
            JOIN pcp_usuarios u ON u.id = e.id_usuario
            JOIN pcp_pedidos_insumos pins ON pins.id = ei.id_pedido_insumo
            JOIN pcp_insumos ins ON ins.id = pins.id_insumo
            WHERE e.dthr_entrada >= "'.$filters['dataInicial'].' 00:00:00" and e.dthr_entrada <= "'.$filters['dataFinal'].' 23:59:59"
            ORDER BY e.id, ins.nome
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Data');
        $sheet->setCellValue('C1', 'ID Usuário');
        $sheet->setCellValue('D1', 'Usuário');
        $sheet->setCellValue('E1', 'ID Pedido');
        $sheet->setCellValue('F1', 'ID Insumo');
        $sheet->setCellValue('G1', 'Insumo');
        $sheet->setCellValue('H1', 'Quantidade');
        $sheet->setCellValue('I1', 'INS');

        $i = 2;
        while($row = $stmt->fetch()){
            $dataEntradaDT = new DateTime($row->dthr_entrada);
            $dataEntrada = $dataEntradaDT->format('d/m/Y');

            $sheet->setCellValue('A'.$i, $row->id_entrada);
            $sheet->setCellValue('B'.$i, $dataEntrada);
            $sheet->setCellValue('C'.$i, $row->id_usuario);
            $sheet->setCellValue('D'.$i, $row->nome_usuario);
            $sheet->setCellValue('E'.$i, $row->id_pedido);
            $sheet->setCellValue('F'.$i, $row->id_insumo);
            $sheet->setCellValue('G'.$i, $row->nome_insumo);
            $sheet->setCellValue('H'.$i, $row->quantidade);
            $sheet->setCellValue('I'.$i, $row->ins);
            $i++;
        }

        $currDateTimeObj = new DateTime();
        $currDateTime = $currDateTimeObj->format('d-m-Y-H-i-s');
        $this->writer->save('reports/reportEntradaInsumos-'.$currDateTime.'.xlsx');
        
        return json_encode(array(
            'success' => true,
            'msg' => 'Relatório gerado com sucesso.',
            'payload' => array(
                'url' => 'http://'.$_SERVER['SERVER_NAME'].'/reports/reportEntradaInsumos-'.$currDateTime.'.xlsx'
            )
        ));
    }

    public function reportArmazenagemInsumos($filters){
        $sheet = $this->spreadsheet->getActiveSheet(); //retornando a aba ativa
        $sql = '
            SELECT
                a.id id_armazenagem,
                a.dthr_armazenagem,
                a.id_usuario id_usuario,
                u.nome nome_usuario,
                pins.id_pedido id_pedido,
                pins.id_insumo,
                ins.nome nome_insumo,
                ai.id_almoxarifado,
                al.nome nome_almoxarifado,
                ai.id_posicao,
                pa.posicao nome_posicao,
                if(si.quantidade IS NOT NULL, (ai.quantidade - si.quantidade), ai.quantidade) quantidade,
                ins.ins
            FROM pcp_armazenagens a
            JOIN pcp_armazenagem_insumos ai ON ai.id_armazenagem = a.id
            left JOIN pcp_saida_insumos si ON si.id_armazenagem_insumos = ai.id
            JOIN pcp_entrada_insumos ei ON ei.id = ai.id_entrada_insumo
            JOIN pcp_usuarios u ON u.id = a.id_usuario
            JOIN pcp_pedidos_insumos pins ON pins.id = ei.id_pedido_insumo
            JOIN pcp_insumos ins ON ins.id = pins.id_insumo
            JOIN pcp_almoxarifado al ON al.id = ai.id_almoxarifado
            JOIN pcp_posicao_armazem pa ON pa.id_almoxarifado = al.id AND pa.id = ai.id_posicao
            GROUP BY ai.id
            HAVING quantidade > 0
            ORDER BY a.id, ins.nome
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Data');
        $sheet->setCellValue('C1', 'ID Usuário');
        $sheet->setCellValue('D1', 'Usuário');
        $sheet->setCellValue('E1', 'ID Pedido');
        $sheet->setCellValue('F1', 'ID Insumo');
        $sheet->setCellValue('G1', 'Insumo');
        $sheet->setCellValue('H1', 'ID Almoxarifado');
        $sheet->setCellValue('I1', 'Almoxarifado');
        $sheet->setCellValue('J1', 'ID Posição');
        $sheet->setCellValue('K1', 'Posição');
        $sheet->setCellValue('L1', 'Quantidade');
        $sheet->setCellValue('M1', 'INS');

        $i = 2;
        while($row = $stmt->fetch()){
            $dataArmazenagemDT = new DateTime($row->dthr_armazenagem);
            $dataArmazenagem = $dataArmazenagemDT->format('d/m/Y');

            $sheet->setCellValue('A'.$i, $row->id_armazenagem);
            $sheet->setCellValue('B'.$i, $dataArmazenagem);
            $sheet->setCellValue('C'.$i, $row->id_usuario);
            $sheet->setCellValue('D'.$i, $row->nome_usuario);
            $sheet->setCellValue('E'.$i, $row->id_pedido);
            $sheet->setCellValue('F'.$i, $row->id_insumo);
            $sheet->setCellValue('G'.$i, $row->nome_insumo);
            $sheet->setCellValue('H'.$i, $row->id_almoxarifado);
            $sheet->setCellValue('I'.$i, $row->nome_almoxarifado);
            $sheet->setCellValue('J'.$i, $row->id_posicao);
            $sheet->setCellValue('K'.$i, $row->nome_posicao);
            $sheet->setCellValue('L'.$i, $row->quantidade);
            $sheet->setCellValue('M'.$i, $row->ins);
            $i++;
        }

        $currDateTimeObj = new DateTime();
        $currDateTime = $currDateTimeObj->format('d-m-Y-H-i-s');
        $this->writer->save('reports/reportArmazenagemInsumos-'.$currDateTime.'.xlsx');
        
        return json_encode(array(
            'success' => true,
            'msg' => 'Relatório gerado com sucesso.',
            'payload' => array(
                'url' => 'http://'.$_SERVER['SERVER_NAME'].'/reports/reportArmazenagemInsumos-'.$currDateTime.'.xlsx'
            )
        ));
    }

    public function reportSaidaInsumos($filters){
        $sheet = $this->spreadsheet->getActiveSheet(); //retornando a aba ativa
        $sql = '
            SELECT
                s.id id_saida,
                s.dthr_saida,
                s.id_usuario id_usuario,
                u.nome nome_usuario,
                pins.id_pedido id_pedido,
                pins.id_insumo,
                ins.nome nome_insumo,
                ins.ins,
                si.id_almoxarifado,
                al.nome nome_almoxarifado,
                ai.id_posicao,
                pa.posicao nome_posicao,
                si.quantidade
            FROM pcp_saidas s
            JOIN pcp_saida_insumos si ON si.id_saida = s.id
            JOIN pcp_armazenagem_insumos ai ON ai.id = si.id_armazenagem_insumos
            JOIN pcp_entrada_insumos ei ON ei.id = ai.id_entrada_insumo
            JOIN pcp_usuarios u ON u.id = s.id_usuario
            JOIN pcp_pedidos_insumos pins ON pins.id = ei.id_pedido_insumo
            JOIN pcp_insumos ins ON ins.id = pins.id_insumo
            JOIN pcp_almoxarifado al ON al.id = si.id_almoxarifado
            JOIN pcp_posicao_armazem pa ON pa.id_almoxarifado = al.id AND pa.id = si.id_posicao
            WHERE
                s.dthr_saida BETWEEN "'.$filters['dataInicial'].' 00:00:00" AND "'.$filters['dataFinal'].' 23:59:59"
            ORDER BY s.id, ins.nome
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Data');
        $sheet->setCellValue('C1', 'ID Usuário');
        $sheet->setCellValue('D1', 'Usuário');
        $sheet->setCellValue('E1', 'ID Pedido');
        $sheet->setCellValue('F1', 'ID Insumo');
        $sheet->setCellValue('G1', 'Insumo');
        $sheet->setCellValue('H1', 'ID Almoxarifado');
        $sheet->setCellValue('I1', 'Almoxarifado');
        $sheet->setCellValue('J1', 'ID Posição');
        $sheet->setCellValue('K1', 'Posição');
        $sheet->setCellValue('L1', 'Quantidade');
        $sheet->setCellValue('M1', 'INS');

        $i = 2;
        while($row = $stmt->fetch()){
            $dataSaidaDT = new DateTime($row->dthr_saida);
            $dataSaida = $dataSaidaDT->format('d/m/Y');

            $sheet->setCellValue('A'.$i, $row->id_saida);
            $sheet->setCellValue('B'.$i, $dataSaida);
            $sheet->setCellValue('C'.$i, $row->id_usuario);
            $sheet->setCellValue('D'.$i, $row->nome_usuario);
            $sheet->setCellValue('E'.$i, $row->id_pedido);
            $sheet->setCellValue('F'.$i, $row->id_insumo);
            $sheet->setCellValue('G'.$i, $row->nome_insumo);
            $sheet->setCellValue('H'.$i, $row->id_almoxarifado);
            $sheet->setCellValue('I'.$i, $row->nome_almoxarifado);
            $sheet->setCellValue('J'.$i, $row->id_posicao);
            $sheet->setCellValue('K'.$i, $row->nome_posicao);
            $sheet->setCellValue('L'.$i, $row->quantidade);
            $sheet->setCellValue('M'.$i, $row->ins);
            $i++;
        }

        $currDateTimeObj = new DateTime();
        $currDateTime = $currDateTimeObj->format('d-m-Y-H-i-s');
        $this->writer->save('reports/reportSaidaInsumos-'.$currDateTime.'.xlsx');
        
        return json_encode(array(
            'success' => true,
            'msg' => 'Relatório gerado com sucesso.',
            'payload' => array(
                'url' => 'http://'.$_SERVER['SERVER_NAME'].'/reports/reportSaidaInsumos-'.$currDateTime.'.xlsx'
            )
        ));
    }

    public function reportEstoqueProdutos($filters){
        $sheet = $this->spreadsheet->getActiveSheet(); //retornando a aba ativa
        $sql = '
            SELECT
                cb.id_producao,
                ap.id_armazenagem,
                ap.codigo,
                ap.id_produto,
                CONCAT(p.nome, " - ", cor.nome) nome_produto,
                p.codigo codigo_produto,
                p.sku sku_produto,
                CONCAT(p.nome, "-", s.nome) descricao,
                p.mao_de_obra,
                p.materia_prima,
                ap.id_almoxarifado,
                almo.nome nome_almoxarifado,
                ap.id_posicao,
                pos.posicao nome_posicao,
                cb.dt_lancamento,
                a.dthr_armazenagem
            FROM wmsprod_armazenagem_produtos ap
            JOIN wmsprod_armazenagens a ON a.id = ap.id_armazenagem
            JOIN pcp_produtos p ON p.id = ap.id_produto
            JOIN pcp_cores cor ON cor.id = p.id_cor
            JOIN wmsprod_almoxarifados almo ON almo.id = ap.id_almoxarifado
            JOIN wmsprod_posicoes pos ON pos.id = ap.id_posicao
            JOIN pcp_codigo_de_barras cb ON cb.id = ap.id_codigo
            JOIN pcp_subprodutos s ON s.id = cb.id_subproduto
            WHERE 
                ap.estoque = "Y"
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $sheet->setCellValue('A1', 'ID Produção');
        $sheet->setCellValue('B1', 'ID Produto');
        $sheet->setCellValue('C1', 'Desc. Produto');
        $sheet->setCellValue('D1', 'Cód. Produto');
        $sheet->setCellValue('E1', 'SKU Produto');
        $sheet->setCellValue('F1', 'Descrição');
        $sheet->setCellValue('G1', 'Cód. Barras');
        $sheet->setCellValue('H1', 'Almoxarifado');
        $sheet->setCellValue('I1', 'Posição');
        $sheet->setCellValue('J1', 'Data Lançamento');
        $sheet->setCellValue('K1', 'Data Armazenagem');
        $sheet->setCellValue('L1', 'Valor Mão de Obra');
        $sheet->setCellValue('M1', 'Valor Matéria Prima');


        $i = 2;
        while($row = $stmt->fetch()){
            $dataLancamentoDT = new DateTime($row->dt_lancamento);
            $dataLancamento = $dataLancamentoDT->format('d/m/Y');
            $dataArmazenagemDT = new DateTime($row->dthr_armazenagem);
            $dataArmazenagem = $dataArmazenagemDT->format('d/m/Y');

            $sheet->setCellValue('A'.$i, $row->id_producao);
            $sheet->setCellValue('B'.$i, $row->id_produto);
            $sheet->setCellValue('C'.$i, $row->nome_produto);
            $sheet->setCellValue('D'.$i, $row->codigo_produto);
            $sheet->setCellValue('E'.$i, $row->sku_produto);
            $sheet->setCellValue('F'.$i, $row->descricao);
            $sheet->setCellValue('G'.$i, $row->codigo);
            $sheet->setCellValue('H'.$i, $row->nome_almoxarifado);
            $sheet->setCellValue('I'.$i, $row->nome_posicao);
            $sheet->setCellValue('J'.$i, $dataLancamento);
            $sheet->setCellValue('K'.$i, $dataArmazenagem);
            $sheet->setCellValue('L'.$i, $row->mao_de_obra);
            $sheet->setCellValue('M'.$i, $row->materia_prima);
            $i++;
        }

        $currDateTimeObj = new DateTime();
        $currDateTime = $currDateTimeObj->format('d-m-Y-H-i-s');
        $this->writer->save('reports/reportEstoqueProdutos-'.$currDateTime.'.xlsx');
        
        return json_encode(array(
            'success' => true,
            'msg' => 'Relatório gerado com sucesso.',
            'payload' => array(
                'url' => 'http://'.$_SERVER['SERVER_NAME'].'/reports/reportEstoqueProdutos-'.$currDateTime.'.xlsx'
            )
        ));
    }

    public function reportSaidaProdutos($filters){
        $sheet = $this->spreadsheet->getActiveSheet(); //retornando a aba ativa
        $sql = '
            SELECT
                cb.id_producao,
                sp.id_produto,
                CONCAT(p.nome, " - ", cor.nome) nome_produto,
                cor.nome cor_produto,
                p.codigo codigo_produto,
                p.sku sku_produto,
                CONCAT(p.nome, "-", sub.nome) descricao,
                p.mao_de_obra,
                p.materia_prima,
                cb.dt_lancamento,
                a.dthr_armazenagem,
                sai.dthr_saida,
                cb.codigo
            FROM wmsprod_saida_produtos sp
            JOIN wmsprod_saidas sai ON sai.id = sp.id_saida
            JOIN pcp_produtos p ON p.id = sp.id_produto
            JOIN pcp_cores cor ON cor.id = p.id_cor
            JOIN pcp_codigo_de_barras cb ON cb.id = sp.id_codigo
            JOIN pcp_subprodutos sub ON sub.id = cb.id_subproduto
            left JOIN wmsprod_armazenagem_produtos ap ON ap.id_codigo = sp.id_codigo
            JOIN wmsprod_armazenagens a ON a.id = ap.id_armazenagem
            WHERE
                sai.dthr_saida BETWEEN "'.$filters['dataInicial'].' 00:00:00" AND "'.$filters['dataFinal'].' 23:59:59"
                AND cb.id is NOT null
                AND ap.id_codigo IS NOT NULL
            ORDER BY sai.dthr_saida;
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $sheet->setCellValue('A1', 'ID Produção');
        $sheet->setCellValue('B1', 'ID Produto');
        $sheet->setCellValue('C1', 'Desc. Produto');
        $sheet->setCellValue('D1', 'Cód. Produto');
        $sheet->setCellValue('E1', 'SKU Produto');
        $sheet->setCellValue('F1', 'Descrição');
        $sheet->setCellValue('G1', 'Cód. Barras');
        $sheet->setCellValue('H1', 'Data Lançamento');
        $sheet->setCellValue('I1', 'Data Armazenagem');
        $sheet->setCellValue('J1', 'Data Saída');
        $sheet->setCellValue('K1', 'Valor Mão de Obra');
        $sheet->setCellValue('L1', 'Valor Matéria Prima');

        $i = 2;
        while($row = $stmt->fetch()){
            $dataLancamentoDT = new DateTime($row->dt_lancamento);
            $dataLancamento = $dataLancamentoDT->format('d/m/Y');
            $dataArmazenagemDT = new DateTime($row->dthr_armazenagem);
            $dataArmazenagem = $dataArmazenagemDT->format('d/m/Y');
            $dataSaidaDT = new DateTime($row->dthr_saida);
            $dataSaida = $dataSaidaDT->format('d/m/Y');

            $sheet->setCellValue('A'.$i, $row->id_producao);
            $sheet->setCellValue('B'.$i, $row->id_produto);
            $sheet->setCellValue('C'.$i, $row->nome_produto);
            $sheet->setCellValue('D'.$i, $row->codigo_produto);
            $sheet->setCellValue('E'.$i, $row->sku_produto);
            $sheet->setCellValue('F'.$i, $row->descricao);
            $sheet->setCellValue('G'.$i, $row->codigo);
            $sheet->setCellValue('H'.$i, $dataLancamento);
            $sheet->setCellValue('I'.$i, $dataArmazenagem);
            $sheet->setCellValue('J'.$i, $dataSaida);
            $sheet->setCellValue('K'.$i, $row->mao_de_obra);
            $sheet->setCellValue('L'.$i, $row->materia_prima);
            $i++;
        }

        $currDateTimeObj = new DateTime();
        $currDateTime = $currDateTimeObj->format('d-m-Y-H-i-s');
        $this->writer->save('reports/reportSaidaProdutos-'.$currDateTime.'.xlsx');
        
        return json_encode(array(
            'success' => true,
            'msg' => 'Relatório gerado com sucesso.',
            'payload' => array(
                'url' => 'http://'.$_SERVER['SERVER_NAME'].'/reports/reportSaidaProdutos-'.$currDateTime.'.xlsx'
            )
        ));
    }
}