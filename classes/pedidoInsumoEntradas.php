<?php

class PedidoInsumoEntradas extends PedidosInsumos{
    public function __construct($db){
        parent::__construct($db);
        $this->pdo = $db;
    }

    public function getPedidoInsumoEntradas($filters){
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $nick = ($key == 'id_pedido' or $key == 'id') ? 'pci.' : 'entrada.';
                $key_where = ($key == 'id_entrada') ? 'id' : $key;
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.$nick.$key_where.' = :'.$key;
                $i++;                
            }
        }

        $responseData = array();

        // Insumo(s) + Quantidades
        $sql = 'select		pci.id, pci.id_pedido, pci.quantidade,	
                            ifnull(sum(entrada.quantidade),0) as quantidadeConferida,
                            ifnull((select sum(arm.quantidade) from pcp_insumos_armazenagem arm where arm.id_pedido_insumo = pci.id),0) as quantidadeArmazenada
                from 		pcp_pedidos_insumos pci
                            left join pcp_insumos_entrada entrada on entrada.id_pedido_insumo = pci.id
                '.$where.'
                group by	pci.id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);        
        while ($row = $stmt->fetch()) {
            $row->id = (int) $row->id;
            $row->id_pedido = (int) $row->id_pedido;
            $row->quantidade = (float) $row->quantidade;
            $row->quantidadeConferida = (float) $row->quantidadeConferida;
            $row->quantidadeArmazenada = (float) $row->quantidadeArmazenada;

            $dataInsumo = $row;
            $dataInsumo->entradas = array();

            // Itens
            $sqlItem = 'select		entrada.id, entrada.dthr_entrada, entrada.quantidade
                        from 		pcp_insumos_entrada entrada                                
                        where       entrada.id_pedido_insumo = :id_pedido_insumo
                        order by	entrada.id';
            $stmtItem = $this->pdo->prepare($sqlItem);
            $stmtItem->bindParam(':id_pedido_insumo', $row->id);
            $stmtItem->execute();
            while ($rowItem = $stmtItem->fetch()) {
                $dthr_entrada = explode(' ', $rowItem->dthr_entrada);
                unset($row->dthr_entrada);

                $rowItem->id = (int) $rowItem->id;            
                $rowItem->data_entrada = (isset($dthr_entrada[0]) and $dthr_entrada[0])  ? $dthr_entrada[0] : null;
                $rowItem->hora_entrada = (isset($dthr_entrada[0]) and $dthr_entrada[0])  ? $dthr_entrada[0] : null;
                $rowItem->quantidade = (float) $rowItem->quantidade;

                $dataInsumo->entradas[] = $rowItem;                
            }

            $responseData[] = $dataInsumo;
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function createUpdatePedidoInsumoEntradas($request){
        try{
            $quantidadeTotalEntrada = 0;

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

            // Valida as entradas, retornao idPedidoInsumo e soma as quantidades
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

                // Status do insumo
                if($dadosReturn['quantidade'] >= ($dadosReturn['quantidadeConferida'] + $entrada['quantidade'])){
                    $entrada['status'] = 'C';
                } else {
                    $entrada['status'] = 'E';
                }
            }

            // Entrada
            if($request['id']){
                // Edit
                $sql = 'update  pcp_entradas
                        set     dthr_entrada = CONCAT(:data_entrada," ",:hora_entrada),
                                id_usuario = :usuario
                        where   id = :id';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id', $request['id']);
                $stmt->bindParam(':data_entrada', $request['data_entrada']);
                $stmt->bindParam(':hora_entrada', $request['hora_entrada']);
                $stmt->bindParam(':usuario', $request['usuario']);
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
            $id_entradas_array = array();
            foreach($request['entradas']  as $key => $entrada){
                if($entrada['id']){
                    $sql = 'update  pcp_insumos_entrada
                            set     id_pedido_insumo = :id_pedido_insumo,
                                    dthr_entrada = CONCAT(:data_entrada," ",:hora_entrada),
                                    quantidade = :quantidade
                            where   id = :id ';
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->bindParam(':id_pedido_insumo', $idPedidoInsumo);
                    $stmt->bindParam(':data_entrada', $entrada['data_entrada']);
                    $stmt->bindParam(':hora_entrada', $entrada['hora_entrada']);
                    $stmt->bindParam(':quantidade', $entrada['quantidade']);
                    $stmt->bindParam(':id', $entrada['id']);
                    $stmt->execute();
                } else {
                    $sql = 'insert  into pcp_insumos_entrada
                            set     id_pedido_insumo = :id_pedido_insumo,
                                    dthr_entrada = CONCAT(:data_entrada," ",:hora_entrada),                            
                                    quantidade = :quantidade';
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->bindParam(':id_pedido_insumo', $idPedidoInsumo);
                    $stmt->bindParam(':data_entrada', $entrada['data_entrada']);
                    $stmt->bindParam(':hora_entrada', $entrada['hora_entrada']);                    
                    $stmt->bindParam(':quantidade', $entrada['quantidade']);
                    $stmt->execute();
                }
            }
            
            // // Deleta os ids de entrada que não estão no JSON (se caso possuir)
            // if(count($id_entradas_array) > 0) {
            //     $sql = 'delete from pcp_insumos_entrada where id_pedido_insumo = :id_pedido_insumo and not id in ('.implode(',',$id_entradas_array).')';
            //     $stmt = $this->pdo->prepare($sql);
            //     $stmt->bindParam(':id_pedido_insumo', $idPedidoInsumo);
            //     $stmt->execute();
            // }
            
            // Altera o status do insumo do pedido de compra
            if(in_array($dadosReturn['status'],array('S','E')) and $quantidadeTotalEntrada === $dadosReturn['quantidade']){
                $this->changeStatus($idPedidoInsumo, 'C');
            } else if($dadosReturn['status'] === 'S'){
                $this->changeStatus($idPedidoInsumo, 'E');
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