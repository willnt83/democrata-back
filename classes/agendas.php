<?php

class Agendas{
    public function __construct($db, $simplexlsx){
        $this->pdo = $db;
        $this->simplexlsx = $simplexlsx;
    }

    public function getAgendas($request){
        try{
            $responseData = array();
            $sql = '
                select
                    a.id idAgenda,
                    a.dthr_criacao dthrCriacao,
                    a.id_usuario idUsuario,
                    u.nome nomeUsuario
                from pcp_agendas a
                join pcp_usuarios u on u.id = a.id_usuario;
            ';
            $stmt = $this->pdo->prepare($sql);
            $res = $stmt->execute();
            
            while ($row = $stmt->fetch()) {
                $responseData[] = array(
                    'id' => $row->idAgenda,
                    'dthrCriacao' => $row->dthrCriacao,
                    'usuario' => array(
                        'id' => $row->idUsuario,
                        'nome' => $row->nomeUsuario
                    )
                );
            }

            return json_encode(array(
                'success' => true,
                'msg' => 'Agendas recuperadas com sucesso.',
                'payload' => $responseData
            ));
        }
        catch(\Exception $e){
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    public function getAgenda($filters){
        try{
            $where = '';
            if(count($filters) > 0){
                $where = 'where ';
                $i = 0;
                foreach($filters as $key => $value){
                    $and = $i > 0 ? ' and ' : '';
                    $where .= $and.'ap.'.$key.' = :'.$key;
                    $i++;
                }
            }

            $responseData = array();
            $sql = '
                SELECT
                    ap.id,
                    ap.id_agenda idAgenda,
                    ap.id_registro idRegistro,
                    ap.fornecedor,
                    ap.observacao,
                    p.sku skuProduto,
                    p.codigo codigoFornecedorProduto,
                    c.nome corProduto,
                    p.nome nomeProduto,
                    ap.quantidade,
                    ap.volumes,
                    ap.situacao,
                    ap.dt_acordada dtAcordada,
                    ap.dt_producao dtProducao,
                    ap.dias_atraso diasAtraso,
                    a.dthr_criacao dtAgendamento,
                    ap.agenda_mobly agendaMobly
                from pcp_agenda_produtos ap
                JOIN pcp_agendas a ON a.id = ap.id_agenda
                JOIN pcp_produtos p ON p.id = ap.id_produto
                JOIN pcp_cores c ON c.id = p.id_cor
                '.$where.';
            ';
            $stmt = $this->pdo->prepare($sql);
            $res = $stmt->execute($filters);
            
            while ($prod = $stmt->fetch()) {
                // Response data
                $responseData[] = array(
                    'id' => $prod->id,
                    'idAgenda' => $prod->idAgenda,
                    'idRegistro' => $prod->idRegistro,
                    'fornecedor' => $prod->fornecedor,
                    'observacao' => $prod->observacao,
                    'sku' => $prod->skuProduto,
                    'codFornecedor' => $prod->codigoFornecedorProduto,
                    'corProduto' => $prod->corProduto,
                    'nomeProduto' => $prod->nomeProduto,
                    'quantidade' => $prod->quantidade,
                    'volumes' => $prod->volumes,
                    'situacao' => $prod->situacao,
                    'dtAcordada' => $prod->dtAcordada,
                    'dtProducao' => $prod->dtProducao,
                    'diasAtraso' => $prod->diasAtraso,
                    'dtAgendamento' => $prod->dtAgendamento,
                    'agendaMobly' => $prod->agendaMobly
                );
            }

            return json_encode(array(
                'success' => true,
                'msg' => 'Agenda recuperadas com sucesso.',
                'payload' => $responseData
            ));
        }
        catch(\Exception $e){
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    public function importarAgenda(){
        try{
            if($xlsx = $this->simplexlsx->parse('./uploads/agenda.xlsx')){
                $rows = $xlsx->rows();
                array_shift($rows); // Removendo primeira linha (título)
                
                $i = 2;
                $responseData = array();
                foreach($rows as $row => $value){
                    // Validações
                    // Produto existente?
                    $sql = '
                        select
                            p.codigo codigoFornecedorProduto,
                            c.nome corProduto,
                            p.nome nomeProduto
                        from pcp_produtos p
                        join pcp_cores c on c.id = p.id_cor
                        where p.sku = :sku
                        limit 1;
                    ';
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->bindParam(':sku', $value[3]);
                    $res = $stmt->execute();
                    $prod = $stmt->fetch();
                    if(!isset($prod->nomeProduto)){
                        throw new \Exception('SKU '.$value[3].' não encontrado. (Linha: '.$i.')');
                    }

                    // Response data
                    $responseData[] = array(
                        'idRegistro' => $value[0],
                        'fornecedor' => $value[1],
                        'observacao' => $value[2],
                        'sku' => $value[3],
                        'codFornecedor' => $prod->codigoFornecedorProduto,
                        'corProduto' => $prod->corProduto,
                        'nomeProduto' => $prod->nomeProduto,
                        'quantidade' => $value[7],
                        'volumes' => $value[8],
                        'situacao' => $value[9],
                        'dtAcordada' => $value[10],
                        'dtProducao' => $value[11],
                        'diasAtraso' => $value[12],
                        'dtAgendamento' => $value[13],
                        'agendaMobly' => $value[14]
                    );
                    $i++;
                }

                return json_encode(array(
                    'success' => true,
                    'msg' => 'Planilha carregada com sucesso. Usuario: ',
                    'payload' => $responseData
                ));
            }
            else{
                throw new \Exception($this->simplexlsx->parseError());
            }
        }
        catch(\Exception $e){
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    public function salvarAgenda($request){
        try{
            // Validações
            if(!array_key_exists('idUsuario', $request) or $request['idUsuario'] === '' or $request['idUsuario'] === null)
                throw new \Exception('Campo idUsuario é obrigatório.');
            if(!array_key_exists('registros', $request) or !is_array($request['registros']) or count($request['registros']) <= 0)
                throw new \Exception('Campo registros é obrigatório.');

            // Criando registro em pcp_agendas
            $sqlInsert = '
                insert into pcp_agendas
                set
                    id_usuario = :idUsuario,
                    dthr_criacao = now();
            ';
            $stmt = $this->pdo->prepare($sqlInsert);
            $stmt->bindParam(':idUsuario', $request['idUsuario']);
            $res = $stmt->execute();

            $idAgenda = $this->pdo->lastInsertId();

            foreach($request['registros'] as $row => $value){
                // Recuperando idProduto de acordo com o SKU informado
                $sql = '
                    select p.id idProduto
                    from pcp_produtos p
                    where p.sku = :sku
                    limit 1;
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':sku', $value['sku']);
                $res = $stmt->execute();
                $prod = $stmt->fetch();

                $sqlInsert = '
                    insert into pcp_agenda_produtos
                    set
                        id_agenda = :idAgenda,
                        id_registro = :idRegistro,
                        fornecedor = :fornecedor,
                        observacao = :observacao,
                        id_produto = :idProduto,
                        quantidade = :quantidade,
                        volumes = :volumes,
                        situacao = :situacao,
                        dt_acordada = :dtAcordada,
                        dt_producao = :dtProducao,
                        dias_atraso = :diasAtraso,
                        agenda_mobly = :agendaMobly;
                ';
                $stmt = $this->pdo->prepare($sqlInsert);
                $stmt->bindParam(':idAgenda', $idAgenda);
                $stmt->bindParam(':idRegistro', $value['idRegistro']);
                $stmt->bindParam(':fornecedor', $value['fornecedor']);
                $stmt->bindParam(':observacao', $value['observacao']);
                $stmt->bindParam(':idProduto', $prod->idProduto);
                $stmt->bindParam(':quantidade', $value['quantidade']);
                $stmt->bindParam(':volumes', $value['volumes']);
                $stmt->bindParam(':situacao', $value['situacao']);
                $stmt->bindParam(':dtAcordada', $value['dtAcordada']);
                $stmt->bindParam(':dtProducao', $value['dtProducao']);
                $stmt->bindParam(':diasAtraso', $value['diasAtraso']);
                $stmt->bindParam(':agendaMobly', $value['agendaMobly']);
                $res = $stmt->execute();
            }

            return json_encode(array(
                'success' => true,
                'msg' => 'Agenda salva com sucesso.'
            ));
        }
        catch(\Exception $e){
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    public function deletarAgenda($request){
        try{
            // Validações
            if(!array_key_exists('idAgenda', $request) or $request['idAgenda'] === '' or $request['idAgenda'] === null)
                throw new \Exception('Campo idAgenda é obrigatório.');

            $sql = '
                delete
                from pcp_agenda_produtos
                where id_agenda = :idAgenda
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':idAgenda', $request['idAgenda']);
            $res = $stmt->execute();

            $sql = '
                delete
                from pcp_agendas
                where id = :idAgenda
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':idAgenda', $request['idAgenda']);
            $res = $stmt->execute();
            
            return json_encode(array(
                'success' => true,
                'msg' => 'Agenda removida com sucesso.'
            ));
        }
        catch(\Exception $e){
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }
}