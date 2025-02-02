<?php

class Produtos {
    public function __construct($db){
        $this->pdo = $db;
        //require_once 'goods.php';
    }

    public function getProdutos($filters){
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.'p.'.$key.' = :'.$key;
                $i++;
            }
        }

        $sql = '
            select p.id, p.nome, c.nome nome_cor
            from pcp_produtos p
            join pcp_cores c on c.id = p.id_cor
            '.$where.'
            order by p.nome;
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':ativo', $filters['ativo']);
        $stmt->execute($filters);

        $responseData = array();
        while ($row = $stmt->fetch()) {
            $responseData[] = array(
                'id' => (int)$row->id,
                'nome' => $row->nome,
                'cor' => $row->nome_cor
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function getProdutosFull($filters){
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.'p.'.$key.' = :'.$key;
                $i++;
            }
        }

        $sql = '
            select
                p.id,
                p.nome,
                p.codigo,
                p.sku,
                p.ativo,
                p.id_cor idCor,
                c.nome nomeCor,
                p.mao_de_obra,
                p.materia_prima,
                p.id_linha_de_producao idLinhaDeProducao,
                p.volume,
                lp.nome nomeLinhaDeProducao,
                plsc.id_setor idSetor,
                s.nome nomeSetor,
                lps.ordem,
                plsc.id_conjunto idConjunto,
                con.nome nomeConjunto
            from pcp_produtos p
            join pcp_cores c on c.id = p.id_cor
            join pcp_linhas_de_producao lp on lp.id = p.id_linha_de_producao
            join pcp_produtos_linhas_setores_conjunto plsc on plsc.id_produto = p.id
            join pcp_setores s on s.id = plsc.id_setor
            join pcp_conjuntos con on con.id = plsc.id_conjunto
            join pcp_linhas_de_producao_setores lps on lps.id_linha_de_producao = lp.id and lps.id_setor = s.id
            '.$where.'
            order by p.id, lps.ordem, s.id
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $filters['id']);
        $stmt->execute($filters);

        $responseData = array();
        $setores = array();
        $prevId = 0;
        $i = 0;
        while ($row = $stmt->fetch()) {
            // Primeira linha
            if($i === 0){
                $responseData[] = array(
                    'id' => (int)$row->id,
                    'nome' => $row->nome,
                    'codigo' => $row->codigo,
                    'sku' => $row->sku,
                    'ativo' => $row->ativo,
                    'maoDeObra' => $row->mao_de_obra,
                    'materiaPrima' => $row->materia_prima,
                    'volume' => $row->volume,
                    'cor' => array(
                        'id' => (int)$row->idCor,
                        'nome' => $row->nomeCor
                    ),
                    'linhaDeProducao' => array(
                        'id' => (int)$row->idLinhaDeProducao,
                        'nome' => $row->nomeLinhaDeProducao
                    )
                );
                $i++;
            }
            // Demais linhas
            else{
                if($prevId != (int)$row->id){
                    $responseData[($i - 1)]['setores'] = $setores;
                    $setores = [];
                    $responseData[] = array(
                        'id' => (int)$row->id,
                        'nome' => $row->nome,
                        'codigo' => $row->codigo,
                        'sku' => $row->sku,
                        'ativo' => $row->ativo,
                        'maoDeObra' => $row->mao_de_obra,
                        'materiaPrima' => $row->materia_prima,
                        'volume' => $row->volume,
                        'cor' => array(
                            'id' => (int)$row->idCor,
                            'nome' => $row->nomeCor
                        ),
                        'linhaDeProducao' => array(
                            'id' => (int)$row->idLinhaDeProducao,
                            'nome' => $row->nomeLinhaDeProducao
                        )
                    );
                    $i++;
                }
            }
            if($row->idSetor !== null){
                $setores[] = array(
                    'id' => (int)$row->idSetor,
                    'nome' => $row->nomeSetor,
                    'ordem' => (int)$row->ordem,
                    'conjunto' => array(
                        'id' => (int)$row->idConjunto,
                        'nome' => $row->nomeConjunto
                    )
                );
            }

            $prevId = $row->id;
        }
        $responseData[($i - 1)]['setores'] = $setores;

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function createUpdateProduto($request){
        try{
            // Validações
            if(!array_key_exists('nome', $request)
                or $request['nome'] === ''
                or $request['nome'] === null)
                throw new \Exception('Campo Nome é obrigatório.');
            if(!array_key_exists('codigo', $request)
                or $request['codigo'] === ''
                or $request['codigo'] === null)
                throw new \Exception('Campo Nome é obrigatório.');
            if(!array_key_exists('ativo', $request) or $request['ativo'] === '' or $request['ativo'] === null)
                throw new \Exception('Campo Ativo é obrigatório.');
            if(!array_key_exists('cor', $request) or $request['cor'] === '' or $request['cor'] === null)
                throw new \Exception('Campo Cor é obrigatório.');
            if(!array_key_exists('idLinhaDeProducao', $request) or $request['idLinhaDeProducao'] === '' or $request['idLinhaDeProducao'] === null)
                throw new \Exception('Campo Linha de Produção é obrigatório.');

            if($request['id'] !== null){
                // Edit
                $sql = '
                    update pcp_produtos
                    set
                        nome = :nome,
                        codigo = :codigo,
                        sku = :sku,
                        ativo = :ativo,
                        id_cor = :cor,
                        mao_de_obra = :maoDeObra,
                        materia_prima = :materiaPrima,
                        volue = :volume
                    where id = :id;
                ';

                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id', $request['id']);
                $stmt->bindParam(':nome', $request['nome']);
                $stmt->bindParam(':codigo', $request['codigo']);
                $stmt->bindParam(':sku', $request['sku']);
                $stmt->bindParam(':cor', $request['cor']);
                $stmt->bindParam(':maoDeObra', $request['maoDeObra']);
                $stmt->bindParam(':materiaPrima', $request['materiaPrima']);
                $stmt->bindParam(':ativo', $request['ativo']);
                $stmt->bindParam(':volume', $request['volume']);
                $stmt->execute();
                $produtoId = $request['id'];
                $msg = 'Produto atualizado com sucesso.';
            }
            else{
                // Insert
                $sql = '
                    insert into pcp_produtos
                    set
                        nome = :nome,
                        codigo = :codigo,
                        sku = :sku,
                        id_cor = :cor,
                        mao_de_obra = :maoDeObra,
                        materia_prima = :materiaPrima,
                        id_linha_de_producao = :id_linha_de_producao,
                        ativo = :ativo,
                        volue = :volume
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':nome', $request['nome']);
                $stmt->bindParam(':codigo', $request['codigo']);
                $stmt->bindParam(':sku', $request['sku']);
                $stmt->bindParam(':cor', $request['cor']);
                $stmt->bindParam(':maoDeObra', $request['maoDeObra']);
                $stmt->bindParam(':materiaPrima', $request['materiaPrima']);
                $stmt->bindParam(':id_linha_de_producao', $request['idLinhaDeProducao']);
                $stmt->bindParam(':ativo', $request['ativo']);
                $stmt->bindParam(':volume', $request['volume']);
                $stmt->execute();
                $produtoId = $this->pdo->lastInsertId();
                $msg = 'Produto cadastrado com sucesso.';
            }

            // Relação produto x linha de producao x setores x conjunto
            if($request['setoresConjuntos'] !== null and count($request['setoresConjuntos']) > 0){
                $sql = '
                    delete from pcp_produtos_linhas_setores_conjunto
                    where id_produto = :id;
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id', $produtoId);
                $stmt->execute();

                foreach($request['setoresConjuntos'] as $key => $setorConjunto){
                    $sql = '
                        insert into pcp_produtos_linhas_setores_conjunto
                        set
                            id_produto = :id_produto,
                            id_linha_de_producao = :id_linha_de_producao,
                            id_setor = :id_setor,
                            id_conjunto = :id_conjunto;
                    ';

                    $stmt = $this->pdo->prepare($sql);
                    $stmt->bindParam(':id_produto', $produtoId);
                    $stmt->bindParam(':id_linha_de_producao', $request['idLinhaDeProducao']);
                    $stmt->bindParam(':id_setor', $setorConjunto['id']);
                    $stmt->bindParam(':id_conjunto', $setorConjunto['idConjunto']);
                    $stmt->execute();
                }
            }

            // Insumos do Produto
            $sql = '
                delete from pcp_produtos_insumos
                where id_produto = :id;
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $produtoId);
            $stmt->execute();

            foreach($request['insumos'] as $key => $insumos){
                $sql = '
                    insert into pcp_produtos_insumos
                    set
                        id_produto = :id_produto,
                        id_insumo  = :id_insumo,
                        qtd_insumo = :qtd_insumo
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id_produto', $produtoId);
                $stmt->bindParam(':id_insumo', $insumos['id']);
                $stmt->bindParam(':qtd_insumo', $insumos['qtd']);
                $stmt->execute();
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

    public function deleteProduto($filters){
        try{
            $sql = '
                delete from pcp_produtos_linhas_setores_conjunto
                where id_produto = :id;
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $filters['id']); 
            $stmt->execute();

            $sql = '
                delete from pcp_produtos_insumos
                where id_produto = :id
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $filters['id']); 
            $stmt->execute();
            
            $sql = '
                delete from pcp_produtos
                where id = :id
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $filters['id']); 
            $stmt->execute();

            return json_encode(array(
                'success' => true,
                'msg' => 'Produto removido com sucesso.'
            ));
        }
        catch(PDOException $e){
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    public function getProdutoInsumos($filters){
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.'pin.'.$key.' = :'.$key;
                $i++;
            }
        }

        $sql = '
        select 	    pin.id_produto, pin.qtd_insumo, 
                    ins.id idInsumo, ins.nome nomeInsumo, pin.qtd_insumo, ins.ins,
                    uni.id idUnidade, uni.nome nomeUnidade, uni.unidade unidadeUnidade
        from	    pcp_produtos p
		            inner join pcp_produtos_insumos pin on pin.id_produto = p.id 
		            inner join pcp_insumos ins on ins.id = pin.id_insumo 
		            inner join pcp_unidades_medida uni on uni.id = ins.id_unidade_medida 
        '.$where.'
        order by    ins.id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);
        
        $responseData = array();
        while ($row = $stmt->fetch()) {
            $responseData[] = array(
                'id'        => (int) $row->idInsumo,
                'nome'      => $row->nomeInsumo,
                'ins'       => $row->ins,
                'qtd'       => (float) $row->qtd_insumo,
                'unidade'   => array(
                    'id'       => (int) $row->idUnidade,
                    'nome'     => $row->nomeUnidade,
                    'medida'   => $row->unidadeUnidade
                )
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));        
    }
}