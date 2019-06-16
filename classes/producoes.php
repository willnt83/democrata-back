<?php
class Producoes{
    public function __construct($db){
        $this->pdo = $db;
        //require_once 'goods.php';
    }

    public function getProducoesTitulo($filters){
        $sql = '
            select p.id, p.nome, p.data_inicial, p.ativo
            from pcp_producoes p
            order by p.id
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $responseData = array();
        $produtos = array();
        $prevId = 0;
        while($row = $stmt->fetch()){
            $responseData[] = array(
                'id' => (int)$row->id,
                'nome' => $row->nome,
                'dataInicial' => $row->data_inicial,
                'ativo' => $row->ativo
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function getProducoes($filters){
        $sql = '
            select p.id, p.nome, p.data_inicial, p.ativo, pro.id id_produto, pro.nome nome_produto, pp.quantidade_produto
            from pcp_producoes_produtos pp
            join pcp_producoes p on p.id = pp.id_producao
            join pcp_produtos pro on pro.id = pp.id_produto
            order by p.id, pp.id_produto
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $responseData = array();
        $produtos = array();
        $prevId = 0;
        $i = 0;
        while($row = $stmt->fetch()){
            // Primeira linha
            if($i === 0){
                $responseData[] = array(
                    'id' => (int)$row->id,
                    'nome' => $row->nome,
                    'dataInicial' => $row->data_inicial,
                    'ativo' => $row->ativo
                );
                $i++;
            }

            // Demais linhas
            else{
                if($prevId != (int)$row->id){
                    $responseData[($i - 1)]['produtos'] = $produtos;
                    $produtos = [];
                    $responseData[] = array(
                        'id' => (int)$row->id,
                        'nome' => $row->nome,
                        'dataInicial' => $row->data_inicial,
                        'ativo' => $row->ativo
                    );
                    $i++;
                }
            }
            if($row->id_produto !== null){
                $produtos[] = array(
                    'id' => (int)$row->id_produto,
                    'nome' => $row->nome_produto,
                    'quantidade' => (int)$row->quantidade_produto
                );
            }
            $prevId = $row->id;
        }
        $responseData[($i - 1)]['produtos'] = $produtos;

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function getProducaoAcompanhamento($filters){
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
                pa.id_producao,
                pro.nome nome_producao,
                pa.id id_acompanhamento,
                pa.id_setor,
                s.nome nome_setor,
                lps.ordem,
                pa.data_inicial,
                pa.id_produto,
                p.nome nome_produto,
                p.id_cor,
                cor.nome nome_cor, 
                pa.id_subproduto,
                ss.nome nome_subproduto,
                pa.total_quantidade,
                pa.realizado_quantidade
            from pcp_producoes_acompanhamento pa
            join pcp_producoes pro on pro.id = pa.id_producao
            join pcp_produtos p on p.id = pa.id_produto
            join pcp_setores s on s.id = pa.id_setor
            join pcp_linhas_de_producao_setores lps on lps.id_setor = pa.id_setor and lps.id_linha_de_producao = p.id_linha_de_producao
            join pcp_subprodutos ss on ss.id = pa.id_subproduto
            join pcp_cores cor on cor.id = p.id_cor
            '.$where.'
            order by lps.ordem, pa.id_setor, pa.id_producao, pa.id_produto, pa.id_subproduto;
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        $producoes = array();
        $produtos = array();
        $subprodutos = array();
        $prevProducaoId = 0;
        $prevProdutoId = 0;
        $prevSetorId = 0;
        $producaoIndex = 0;
        $produtoIndex = 0;
        $setorIndex = 0;
        $linha = 0;

        // Sem filtros, recupera todas os acompanhamentos de todas as produções

            while ($row = $stmt->fetch()) {
                $linha++;
                //echo "\n\n=========================================================================================================================================\nlinha ".$linha;
                // Primeira linha
                if($setorIndex === 0){
                    $responseData[] = array(
                        'id' => (int)$row->id_setor,
                        'nome' => $row->nome_setor,
                        'ordem' => (int)$row->ordem
                    );

                    $producoes[] = array(
                        'id' => (int)$row->id_producao,
                        'nome' => $row->nome_producao,
                        'dataInicial' => $row->data_inicial
                    );

                    $produtos[] = array(
                        'id' => (int)$row->id_produto,
                        'nome' => $row->nome_produto,
                        'cor' => array(
                            'id' => $row->id_cor,
                            'nome' => $row->nome_cor
                        )
                    );

                    $setorIndex++;
                    $producaoIndex++;
                    $produtoIndex++;
                }
                // Demais linhas
                else{
                    // Setor é diferente do anterior?
                    if($prevSetorId != (int)$row->id_setor){
                        //echo "\n[SETOR] diferente do anterior...";
                        $produtos[($produtoIndex - 1)]['subprodutos'] = $subprodutos;
                        $producoes[($producaoIndex - 1)]['produtos'] = $produtos;
                        $responseData[($setorIndex - 1)]['producoes'] = $producoes;

                        /*echo "\n\nresponseData\n<pre>";
                        print_r($responseData);
                        echo "</pre>";*/
                        
                        $producaoIndex = 0;
                        $produtoIndex = 0;
                        $producoes = array();
                        $produtos = array();
                        $subprodutos = array();

                        // Pega o próximo setor
                        $responseData[] = array(
                            'id' => (int)$row->id_setor,
                            'nome' => $row->nome_setor,
                            'ordem' => (int)$row->ordem
                        );

                        // Pega a proxima producao
                        $producoes[] = array(
                            'id' => (int)$row->id_producao,
                            'nome' => $row->nome_producao,
                            'dataInicial' => $row->data_inicial
                        );

                        // Pega o proximo produto
                        $produtos[] = array(
                            'id' => (int)$row->id_produto,
                            'nome' => $row->nome_produto,
                            'cor' => array(
                                'id' => $row->id_cor,
                                'nome' => $row->nome_cor
                            )
                        );

                        $setorIndex++;
                        $producaoIndex++;
                        $produtoIndex++;
                    }
                    else{
                        // Produção é diferente da anterior?
                        if($prevProducaoId != (int)$row->id_producao){
                            //echo "\n[PRODUCAO] diferente da anterior...";
                            $produtos[($produtoIndex - 1)]['subprodutos'] = $subprodutos;
                            $producoes[($producaoIndex - 1)]['produtos'] = $produtos;


                            /*echo "\n\nproducoes\n<pre>";
                            print_r($producoes);
                            echo "</pre>";*/

                            $produtoIndex = 0;
                            $produtos = array();
                            $subprodutos = array();

                            // Pega a próxima produção
                            $producoes[] = array(
                                'id' => (int)$row->id_producao,
                                'nome' => $row->nome_producao,
                                'dataInicial' => $row->data_inicial
                            );

                            // Pega o proximo produto
                            $produtos[] = array(
                                'id' => (int)$row->id_produto,
                                'nome' => $row->nome_produto,
                                'cor' => array(
                                    'id' => $row->id_cor,
                                    'nome' => $row->nome_cor
                                )
                            );

                            $producaoIndex++;
                            $produtoIndex++;
                        }
                        else{
                            // Produto é diferente do anterior
                            if($prevProdutoId != (int)$row->id_produto){
                                //echo "\n[PRODUTO] diferente do anterior...";
                                $produtos[($produtoIndex -1)]['subprodutos'] = $subprodutos;

                                /*echo "\n\nprodutos\n<pre>";
                                print_r($produtos);
                                echo "</pre>";*/

                                $subprodutos = array();

                                // Pega o produto novo
                                $produtos[] = array(
                                    'id' => (int)$row->id_produto,
                                    'nome' => $row->nome_produto,
                                    'cor' => array(
                                        'id' => $row->id_cor,
                                        'nome' => $row->nome_cor
                                    )
                                );

                                $produtoIndex++;
                            }
                        }
                    }
                }

                // Pega em todas as linhas
                $subprodutos[] = array(
                    'idAcompanhamento' => (int)$row->id_acompanhamento,
                    'id' => (int)$row->id_subproduto,
                    'nome' => $row->nome_subproduto,
                    'realizadoQuantidade' => (int)$row->realizado_quantidade,
                    'totalQuantidade' => (int)$row->total_quantidade
                );

                $prevProducaoId = $row->id_producao;
                $prevSetorId = $row->id_setor;
                $prevProdutoId = $row->id_produto;
            }

            $produtos[($produtoIndex - 1)]['subprodutos'] = $subprodutos;
            $producoes[($producaoIndex - 1)]['produtos'] = $produtos;
            $responseData[($setorIndex - 1)]['producoes'] = $producoes;

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function createUpdateProducao($request){
        try{
            // Validações
            if(!array_key_exists('nome', $request) or $request['nome'] === '' or $request['nome'] === null)
                throw new \Exception('Campo Nome é obrigatório.');
            if(!array_key_exists('ativo', $request) or $request['ativo'] === '' or $request['ativo'] === null)
                throw new \Exception('Campo Ativo é obrigatório.');
            if(!array_key_exists('dataInicial', $request) or $request['dataInicial'] === '' or $request['dataInicial'] === null)
                throw new \Exception('Campo Data Inicial é obrigatório.');

            if($request['id']){
                // Edit
                $sql = '
                    update pcp_producoes
                    set
                        nome = :nome,
                        data_inicial = :data_inicial,
                        ativo = :ativo
                    where id = :id;
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id', $request['id']);
                $stmt->bindParam(':nome', $request['nome']);
                $stmt->bindParam(':data_inicial', $request['dataInicial']);
                $stmt->bindParam(':ativo', $request['ativo']);
                $stmt->execute();
                $producaoId = $request['id'];
                $msg = 'Produção atualizada com sucesso.';
            }
            else{
                $sql = '
                    insert into pcp_producoes
                    set
                        nome = :nome,
                        data_inicial = :data_inicial,
                        ativo = :ativo
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':nome', $request['nome']);
                $stmt->bindParam(':data_inicial', $request['dataInicial']);
                $stmt->bindParam(':ativo', $request['ativo']);
                $stmt->execute();
                $producaoId = $this->pdo->lastInsertId();
                $msg = 'Produção cadastrada com sucesso.';
            }

            $sqlDelete = '
                delete from pcp_producoes_acompanhamento
                where id_producao = :id;
            ';
            $stmt2 = $this->pdo->prepare($sqlDelete);
            $stmt2->bindParam(':id', $producaoId);
            $stmt2->execute();

            $sql = '
                delete from pcp_producoes_produtos
                where id_producao = :id;
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $producaoId);
            $stmt->execute();

            // Relação produção x produtos
            if($request['produtos'] !== null and count($request['produtos']) > 0){
                foreach($request['produtos'] as $key => $produto){
                    $sqlProducoesProdutos = '
                        insert into pcp_producoes_produtos
                        set
                            id_producao = :id_producao,
                            id_produto = :id_produto,
                            quantidade_produto = :quantidade_produto;
                    ';

                    $stmt = $this->pdo->prepare($sqlProducoesProdutos);
                    $stmt->bindParam(':id_producao', $producaoId);
                    $stmt->bindParam(':id_produto', $produto['id']);
                    $stmt->bindParam(':quantidade_produto', $produto['quantidade']);
                    $stmt->execute();

                    // Relação produção x linha de produção
                    $sql2 = '
                        select
                            plsc.id_produto,
                            plsc.id_linha_de_producao,
                            plsc.id_setor,
                            plsc.id_conjunto,
                            lps.ordem,
                            cs.id_subproduto,
                            cs.quantidade_subproduto
                        from pcp_produtos_linhas_setores_conjunto plsc
                        join pcp_linhas_de_producao_setores lps on lps.id_linha_de_producao = plsc.id_linha_de_producao and lps.id_setor = plsc.id_setor
                        join pcp_conjuntos_subprodutos cs on cs.id_conjunto = plsc.id_conjunto
                        where plsc.id_produto = :id_produto
                        order by plsc.id_produto, lps.ordem, plsc.id_setor;
                    ';
                    $stmt = $this->pdo->prepare($sql2);
                    $stmt->bindParam(':id_produto', $produto['id']);
                    $stmt->execute();
                    $idSetorAnterior = null;
                    $ordemAnterior = null;
                    $dataInicial = $request['dataInicial'];
                    $i = 0;
                    while($row = $stmt->fetch()){
                        //echo "\n================================\niteracao ".$i;
                        if($row->id_setor != $idSetorAnterior){
                            $idSetorAnterior = $row->id_setor;

                            /*
                            echo '<br>======================';
                            echo '<br>setor: '.$row->id_setor;
                            echo '<br>ordem: '.$row->ordem;
                            */

                            if($ordemAnterior != $row->ordem){
                                $ordemAnterior = $row->ordem;
                                $date = new DateTime($dataInicial);
                                if($i > 0){
                                    $date->add(new DateInterval('P1D'));
                                }
                                //echo '<br>Verificando data: '.$date->format('Y-m-d');
                                // Busca na tabela pcp_dias_nao_uteis para ver se o dia é útil ou não
                                $hit = false;
                                while(!$hit){
                                    $hit = false;
                                    $sqlNaoUtil = '
                                        select count(id) hits
                                        from pcp_dias_nao_uteis
                                        where data = :dataVerificacao;
                                    ';
                                    $stmtNaoUtil = $this->pdo->prepare($sqlNaoUtil);
                                    $dataVerificacao = $date->format('Y-m-d');
                                    $stmtNaoUtil->bindParam(':dataVerificacao', $dataVerificacao);
                                    $stmtNaoUtil->execute();
                                    $rowNaoUtil = $stmtNaoUtil->fetch();

                                    if($rowNaoUtil->hits > 0){
                                        //echo '<br>data não útil!';
                                        //Data é não útil, incrementa 1 dia
                                        $date->add(new DateInterval('P1D'));
                                    }
                                    else{
                                        //echo '<br>data útil!';
                                        //Data é dia útil, aborta o while
                                        $hit = true;
                                    }
                                }
                                $dataInicial = $date->format('Y-m-d');
                            }
                        }
                        //echo '<br>Data inicial: '.$dataInicial;

                        // Se data inicial selecionado for dia não útil, atualiza data_inicial em pcp_producoes com a primeira data últil
                        if($i === 0){
                            $sqlUpdateDataInicial = '
                                update pcp_producoes
                                set data_inicial = :data_inicial
                                where
                                    id = :id;
                            ';
                            $stmtUpdateDataInicial = $this->pdo->prepare($sqlUpdateDataInicial);
                            $stmtUpdateDataInicial->bindParam(':data_inicial', $dataInicial);
                            $stmtUpdateDataInicial->bindParam(':id', $producaoId);
                            $stmtUpdateDataInicial->execute();
                        }

                        $quantidadeTotal = $row->quantidade_subproduto * $produto['quantidade'];

                        //echo "\ninsert pcp_producoes_acompanhamento...";
                        $sqlInsert = '
                            insert into pcp_producoes_acompanhamento
                            set
                                id_producao = :id_producao,
                                id_produto = :id_produto,
                                id_setor = :id_setor,
                                data_inicial = :data_inicial,
                                id_subproduto = :id_subproduto,
                                total_quantidade = :total_quantidade;
                        ';
                        /*
                        echo "\nid_producao ".$producaoId;
                        echo "\nid_produto ".$row->id_produto;
                        echo "\nid_setor ".$row->id_setor;
                        echo "\ndata_inicial ".$dataInicial;
                        echo "\nid_subproduto ".$row->id_subproduto;
                        echo "\ntotal_quantidade ".$quantidadeTotal;
                        */
                        $stmtIn = $this->pdo->prepare($sqlInsert);
                        $stmtIn->bindParam(':id_producao', $producaoId);
                        $stmtIn->bindParam(':id_produto', $row->id_produto);
                        $stmtIn->bindParam(':id_setor', $row->id_setor);
                        $stmtIn->bindParam(':data_inicial', $dataInicial);
                        $stmtIn->bindParam(':id_subproduto', $row->id_subproduto);
                        $stmtIn->bindParam(':total_quantidade', $quantidadeTotal);
                        $stmtIn->execute();
                        $i++;
                    }
                }
            }

            // Reponse
            return json_encode(array(
                'success' => true,
                'msg' => $msg
            ));
        }catch(\Exception $e){
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    public function deleteProducao($request){
        try{
            // Validações
            if(!array_key_exists('id', $request) or $request['id'] === '' or $request['id'] === null)
                throw new \Exception('Campo Id é obrigatório.');

            $sql = '
                delete from pcp_producoes
                where id = :id
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($request);

            $sql = '
                    delete from pcp_producoes_produtos
                    where id_producao = :id;
                ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($request);

            $sql = '
                delete from pcp_producoes_acompanhamento
                where id_producao = :id;
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($request);

            return json_encode(array(
                'success' => true,
                'msg' => 'Produção removida com sucesso.'
            ));
        }
        catch(PDOException $e){
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    public function updateRealizadoQuantidade($request){
        try{
            // Validações
            /*
            if(!array_key_exists('idAcompanhamento', $request) or $request['idAcompanhamento'] === '' or $request['idAcompanhamento'] === null)
                throw new \Exception('Campo idAcompanhamento é obrigatório.');
            if(!array_key_exists('realizadoQuantidade', $request) or $request['realizadoQuantidade'] === '' or $request['realizadoQuantidade'] === null)
                throw new \Exception('Campo realizadoQuantidade é obrigatório.');
            */

            $sql = '
                update pcp_producoes_acompanhamento
                set
                    realizado_quantidade = :realizadoQuantidade
                where id = :idAcompanhamento;
            ';
            forEach($request as $item => $valores){
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':realizadoQuantidade', $valores['realizadoQuantidade']);
                $stmt->bindParam(':idAcompanhamento', $valores['idAcompanhamento']);
                $stmt->execute();
            }
            $msg = 'Quantidade atualizada com sucesso.';

            return json_encode(array(
                'success' => true,
                'msg' => $msg
            ));
        }
        catch(\Exception $e){
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    public function generateImage($img, $file){
        $file_parts = explode('/', $file);
        if(!is_dir($file_parts[0].'/'.$file_parts[1])){
            mkdir($file_parts[0].'/'.$file_parts[1], 0777, true);
        }

        $image_parts = explode(";base64,", $img);
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        $image_base64 = base64_decode($image_parts[1]);
        
        file_put_contents($file, $image_base64);
        return $file;
    }

    public function gerarCodigosDeBarras($filters){
        //http://www.fpdf.org/
        require('../vendor/fpdf/fpdf.php');
        //https://github.com/picqer/php-barcode-generator
        $generator = new Picqer\Barcode\BarcodeGeneratorPNG();
        
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
                pa.id_producao,
                pa.id_produto,
                plsc.id_conjunto,
                pa.id_setor,
                s.nome nome_setor,
                pa.id_subproduto,
                ss.nome nome_subproduto,
                pa.total_quantidade
            from pcp_producoes_acompanhamento pa
            join pcp_setores s on s.id = pa.id_setor
            join pcp_producoes pro on pro.id = pa.id_producao
            join pcp_produtos p on p.id = pa.id_produto
            join pcp_linhas_de_producao_setores lps on lps.id_setor = pa.id_setor and lps.id_linha_de_producao = p.id_linha_de_producao
            join pcp_subprodutos ss on ss.id = pa.id_subproduto
            join pcp_cores cor on cor.id = p.id_cor
            JOIN pcp_produtos_linhas_setores_conjunto plsc ON plsc.id_produto = pa.id_produto AND plsc.id_linha_de_producao = p.id_linha_de_producao AND plsc.id_setor = pa.id_setor
            '.$where.'
            order by lps.ordem, pa.id_setor, pa.id_producao, pa.id_produto, pa.id_subproduto;
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);
        $barCodes = array();

        // Monta o vetor com dois elementos: strBase64 (código de barras) e file (caminho completo do arquivo de imagem a ser gerado)
        while($row = $stmt->fetch()){
            if($row->total_quantidade > 1){
                $i = 1;
                while($i <= $row->total_quantidade){
                    $barCode = $row->id_producao."-".$row->id_produto."-".$row->id_conjunto."-".$row->id_setor."-".$row->id_subproduto."-".$i;
                    $barCodes[] = array(
                        'setor' => $row->nome_setor,
                        'nomeSubproduto' => $row->nome_subproduto,
                        'strBase64' => $barCode,
                        'file' => 'barcodes/'.$row->id_producao.'/'.$barCode.'.png'
                    );
                    $i++;
                }
            }
            else{
                $barCode = $row->id_producao."-".$row->id_produto."-".$row->id_conjunto."-".$row->id_setor."-".$row->id_subproduto."-1";
                $barCodes[] = array(
                    'setor' => $row->nome_setor,
                    'nomeSubproduto' => $row->nome_subproduto,
                    'strBase64' => $barCode,
                    'file' => 'barcodes/'.$row->id_producao.'/'.$barCode.'.png'
                );
            }
        }

        $i = 0;
        $pdf = new FPDF();

        $lastSetor = null;
        while($i < count($barCodes)){
            // Título do Setor
            if($barCodes[$i]['setor'] != $lastSetor){
                $pdf->AddPage();
                $pdf->SetFont('Arial','B',16);
                $pdf->Cell( 188, 18, utf8_decode($barCodes[$i]['setor']), 1, 0, 'C', false);
                $pdf->SetFont('Arial','B',8);
                $pdf->Ln();
                $lastSetor = $barCodes[$i]['setor'];
            }

            // Impar
            if($i % 2 == 0){
                // Armazena os dados em vetor auxiliar
                $aux = $barCodes[$i];
            }
            // Par
            else{
                // Impressao
                // Gerando a string base64 da imagem
                $strBase64Aux = 'data:image/png;base64,'.base64_encode($generator->getBarcode($aux['strBase64'], $generator::TYPE_CODE_128));
                $strBase64 = 'data:image/png;base64,'.base64_encode($generator->getBarcode($barCodes[$i]['strBase64'], $generator::TYPE_CODE_128));
                // Gerando o arquivo de imagem
                $imgAux = $this->generateImage($strBase64Aux, $aux['file']);
                $img = $this->generateImage($strBase64, $barCodes[$i]['file']);
                
                $pdf->Cell(94, 7 , $aux['strBase64'].' - '.$aux['nomeSubproduto'], 1, 0, 'C');
                $pdf->Cell(94, 7 , $barCodes[$i]['strBase64'].' - '.$aux['nomeSubproduto'], 1, 0, 'C');
                $pdf->Ln();
                $pdf->Cell(47, 14 , $pdf->Image($imgAux, $pdf->GetX()+3, $pdf->GetY()+3, 40, 8), 1, 0, 'C', false);
                $pdf->Cell(47, 14 , $pdf->Image($imgAux, $pdf->GetX()+3, $pdf->GetY()+3, 40, 8), 1, 0, 'C', false);
                $pdf->Cell(47, 14 , $pdf->Image($img, $pdf->GetX()+3, $pdf->GetY()+3, 40, 8), 1, 0, 'C', false);
                $pdf->Cell(47, 14 , $pdf->Image($img, $pdf->GetX()+3, $pdf->GetY()+3, 40, 8), 1, 0, 'C', false);
                $pdf->Ln();
                $pdf->Ln();
            }
            $i++;
        }
        
        $path = 'barcodes/'.$filters['id_producao'].'/producao-'.$filters['id_producao'].'.pdf';
        $pdf->Output('F', $path, true);

        return json_encode(array(
            'success' => true,
            'payload' => array(
                'url' => $path
            )
        ));
    }
}