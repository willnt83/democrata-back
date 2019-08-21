<?php

class EntradaInsumos extends PedidosInsumos{
    public function __construct($db){
        parent::__construct($db);
        $this->pdo = $db;
    }

    public function getEntradaInsumos($filters){
        $where = '';
        
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $nick = ($key == 'idEntradaInsumo') ? 'pei.' : 'pe.';
                $key_where = ($key == 'idEntradaInsumo') ? 'id' : $key;
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.$nick.$key_where.' = :'.$key;
                $i++;                
            }
        }

        // Auxiliares
        $i = 0;
        $entradaId = 0;
        $responseData = array();

        // Insumo(s) + Quantidades
        $sql = 'select      pe.id, pe.dthr_entrada, u.id as idUsuario, 
                            u.id as idUsuario, u.nome as nomeUsuario, 
                            pei.id as item, pei.id_pedido_insumo as idPedidoInsumo, pei.quantidade,
                            pc.id as idPedido, pc.chave_nf as chaveNF,
                            f.id as idFornecedor, f.nome as nomeFornecedor,
                            ins.id as idInsumo, ins.nome as nomeInsumo, ins.ins as insInsumo,
                            ifnull((
                                select 	sum(ent.quantidade) 
                                from 	pcp_entrada_insumos ent 
                                where 	ent.id_pedido_insumo = pci.id
                            ),0) as quantidadeConferida
                from	    pcp_entradas pe
                            inner join pcp_usuarios u on pe.id_usuario = u.id
                            inner join pcp_entrada_insumos pei on pe.id = pei.id_entrada
                            inner join pcp_pedidos_insumos pci on pci.id = pei.id_pedido_insumo
                            inner join pcp_pedidos pc on pc.id = pci.id_pedido
                            inner join pcp_fornecedores f on f.id = pc.id_fornecedor
                            inner join pcp_insumos ins on ins.id = pci.id_insumo               
                '.$where.'
                order by    pe.id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);  
        while ($row = $stmt->fetch()) {            
            // Entrada
            if ($entradaId != (int) $row->id) {
                $dthr_entrada = explode(' ', $row->dthr_entrada);
                unset($row->dthr_entrada);

                $i++;
                $responseData[] = (array) array(
                    'id' => (int) $row->id,
                    'data_entrada' => (isset($dthr_entrada[0]) and $dthr_entrada[0]) ? $dthr_entrada[0] : null,
                    'hora_entrada' => (isset($dthr_entrada[1]) and $dthr_entrada[1]) ? $dthr_entrada[1] : null,
                    'idUsuario' => (int) $row->idUsuario,
                    'nomeUsuario' => $row->nomeUsuario,
                );
            }

            // Insumos (Itens)
            $responseData[($i-1)]['insumos'][] = array(
                'id'                  => (int) $row->item,
                'idInsumo'            => (int) $row->idInsumo,
                'nomeInsumo'          => $row->nomeInsumo,
                'insInsumo'           => $row->insInsumo,
                'quantidade'          => (float) $row->quantidade,
                'idPedido'            => (int) $row->idPedido,
                'idPedidoInsumo'      => (int) $row->idPedidoInsumo,
                'idFornecedor'        => (int) $row->idFornecedor,
                'nomeFornecedor'      => $row->nomeFornecedor,
                'chaveNF'             => $row->chaveNF,
                'quantidadeConferida' => (float) $row->quantidadeConferida
            );

            $entradaId = $row->id;
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function createUpdateEntradaInsumos($request){
        try{
            // Valida a entrada
            if(!array_key_exists('data_entrada', $request) or $request['data_entrada'] === '' or $request['data_entrada'] === null)
                throw new \Exception('Data da entrada é obrigatória');
            if(!array_key_exists('hora_entrada', $request) or $request['hora_entrada'] === '' or $request['hora_entrada'] === null)
                throw new \Exception('Hora da entrada é obrigatória'); 
            if(!array_key_exists('usuario', $request) or $request['usuario'] === '' or $request['usuario'] === null)
                throw new \Exception('Usuário da entrada é obrigatório'); 

            // Valida se possui entrada
            if(!array_key_exists('entradas', $request) or $request['entradas'] === '' or $request['entradas'] === null or count($request['entradas']) === 0)
                throw new \Exception('É necessário informar pelo menos uma entrada');

            // Valida as entradas de insumos, retornando o idPedidoInsumo, o status do pedido insumo e a soma das quantidades
            foreach($request['entradas'] as $key => $entrada){
                if(!array_key_exists('idPedido', $entrada) or $entrada['idPedido'] === '' or $entrada['idPedido'] === null)
                    throw new \Exception('Código do Pedido é obrigatório nas entradas');
                if(!array_key_exists('idInsumo', $entrada) or $entrada['idInsumo'] === '' or $entrada['idInsumo'] === null)
                    throw new \Exception('Código do Insumo é obrigatório nas entradas'); 
                if(!array_key_exists('quantidade', $entrada) or $entrada['quantidade'] === '' or $entrada['quantidade'] === null)
                    throw new \Exception('Quantidade da entrada é obrigatória.');
                else
                    $entrada['quantidade'] = (float) $entrada['quantidade'];

                // Retora o id, status e quantidades do pedido insumo
                $dadosReturn = $this->getDadosPedidoInsumo($entrada['idPedido'],$entrada['idInsumo']);
                if(!$dadosReturn['success']) {
                    throw new \Exception('Erro ao inserir as entradas! Tente novamente.');
                }

                // id do pedido insumo
                $entrada['idPedidoInsumo'] = $dadosReturn['id'];

                // Status que será aplicado ao pedido insumo
                if($dadosReturn['quantidade'] >= ($dadosReturn['quantidadeConferida'] + $entrada['quantidade'])){
                    $statusInsumo = 'C';
                } else {
                    $statusInsumo = 'E';
                }

                // Verifica se alterará o status do insumo
                if($dadosReturn['status'] !== $statusInsumo){
                    $entrada['status'] = $statusInsumo;
                } else {
                    $entrada['status'] = '';
                }

                // Atualiza o array
                $request['entradas'][$key] = $entrada;
            }

            // Auxiliares
            $id_pedido_compra           = array();
            $id_entrada_insumos_array   = array();

            // Pedido Insumos
            $pedidoInsumos = new PedidosInsumos($this->pdo);

            // Insere/atualiza a Entrada
            if(isset($request['id']) and $request['id']){
                $sql = 'update  pcp_entradas
                        set     dthr_entrada = CONCAT(:data_entrada," ",:hora_entrada)
                        where   id = :id';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id', $request['id']);
                $stmt->bindParam(':data_entrada', $request['data_entrada']);
                $stmt->bindParam(':hora_entrada', $request['hora_entrada']);
                $stmt->execute();
                $entradaId = $request['id'];
                $msg = 'Entrada atualizada com sucesso.';
            }
            else{
                $sql = 'insert  into pcp_entradas
                        set     dthr_entrada = CONCAT(:data_entrada," ",:hora_entrada),
                                id_usuario = :usuario';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':data_entrada', $request['data_entrada']);
                $stmt->bindParam(':hora_entrada', $request['hora_entrada']);
                $stmt->bindParam(':usuario', $request['usuario']);
                $stmt->execute();
                $entradaId = $this->pdo->lastInsertId();
                $msg = 'Pedido de compra cadastrado com sucesso.';
            }            

            // Insere/atualiza as entrada insumos
            foreach($request['entradas']  as $key => $entrada){ 
                if($entrada['id']){
                    $sql = 'update  pcp_entrada_insumos
                            set     id_pedido_insumo = :id_pedido_insumo,
                                    quantidade = :quantidade
                            where   id = :id ';
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->bindParam(':id_pedido_insumo', $entrada['idPedidoInsumo']);
                    $stmt->bindParam(':quantidade', $entrada['quantidade']);
                    $stmt->bindParam(':id', $entrada['id']);
                    $stmt->execute();
                    $id_entrada_insumos_array[] = $entrada['id'];
                } else {
                    $sql = 'insert  into pcp_entrada_insumos
                            set     id_entrada = :id_entrada,
                                    id_pedido_insumo = :id_pedido_insumo,
                                    quantidade = :quantidade,
                                    id_usuario = :usuario,
                                    dthr_entrada = CONCAT(:data_entrada," ",:hora_entrada)';
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->bindParam(':id_entrada', $entradaId);
                    $stmt->bindParam(':id_pedido_insumo', $entrada['idPedidoInsumo']);
                    $stmt->bindParam(':quantidade', $entrada['quantidade']);
                    $stmt->bindParam(':usuario', $request['usuario']);
                    $stmt->bindParam(':data_entrada', $request['data_entrada']);
                    $stmt->bindParam(':hora_entrada', $request['hora_entrada']);
                    $stmt->execute();
                    $idItem = $this->pdo->lastInsertId();
                    if($idItem) $id_entrada_insumos_array[] = $idItem;
                }

                // Salva o pedido para verificar o status posteriormentes
                if(!in_array($entrada['idPedido'],$id_pedido_compra))
                    $id_pedido_compra[] = $entrada['idPedido'];

                // Altera o status do insumo do pedido de compra
                if($entrada['idPedidoInsumo'] and trim($entrada['status']) != ''){                    
                    $pedidoInsumos->changeStatus($entrada['idPedidoInsumo'], trim($entrada['status']));
                }
            }
            
            // Deleta os ids de entrada que não estão no JSON (se caso possuir)
            if(count($id_entrada_insumos_array) > 0) {
                $sql = 'delete from pcp_entrada_insumos where id_entrada = :id_entrada and not id in ('.implode(',',$id_entrada_insumos_array).')';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id_entrada', $entradaId);
                $stmt->execute();
            }

            // Verifica e altera o status dos pedidos
            if(count($id_pedido_compra) > 0) {

            }

            return json_encode(array(
                'success' => true,
                'msg' => 'Dados atualizados com sucesso.'
            ));            

        } catch(\Exception $e) {
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    private function getDadosPedidoInsumo($idPedido = 0, $idInsumo = 0){
        $sql = 'select		pci.id, pci.status, pci.quantidade,
                            ifnull(sum(entrada.quantidade),0) as quantidadeConferida
                from 		pcp_pedidos_insumos pci
                            left join pcp_entrada_insumos as entrada on entrada.id_pedido_insumo = pci.id
                where       pci.id_pedido = :id_pedido and pci.id_insumo = :id_insumo
                group by	pci.id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_pedido', $idPedido);
        $stmt->bindParam(':id_insumo', $idInsumo);
        $stmt->execute();
        $row = $stmt->fetch();
        if($row){
            return array(
                'success'             => true,
                'id'                  => (int) $row->id,
                'status'              => $row->status,
                'quantidade'          => (float) $row->quantidade,
                'quantidadeConferida' => (float) $row->quantidadeConferida
            ); 
        } else {
            return array('success' => false); 
        }          
    }
}