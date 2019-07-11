<?php

class PedidoInsumoEntradas{
    public function __construct($db){
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
                            ifnull(sum(armazenagem.quantidade),0) as quantidadeArmazenada
                from 		pcp_pedidos_insumos pci
                            left join pcp_insumos_entrada entrada on entrada.id_pedido_insumo = pci.id
                            left join pcp_insumos_armazenagem armazenagem on armazenagem.id_pedido_insumo = pci.id
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
            $quantidadeTotal = 0;

            // Código do pedido insumo
            if(!array_key_exists('idPedidoInsumo', $request) or $request['idPedidoInsumo'] === '' or $request['idPedidoInsumo'] === null)
                throw new \Exception('Insumo do pedido de compra não informado.');
            else
                $idPedidoInsumo = $request['idPedidoInsumo'];

            // Valida se possui entrada
            if(!array_key_exists('entradas', $request) or $request['entradas'] === '' or $request['entradas'] === null or count($request['entradas']) === 0)
                throw new \Exception('É necessário informar pelo menos uma entrada');

            // Valida as entradas
            foreach($request['entradas'] as $key => $entrada){
                if(!array_key_exists('data_entrada', $entrada) or $entrada['data_entrada'] === '' or $entrada['data_entrada'] === null)
                    throw new \Exception('Data da entrada é obrigatória.');
                if(!array_key_exists('hora_entrada', $entrada) or $entrada['hora_entrada'] === '' or $entrada['hora_entrada'] === null)
                    throw new \Exception('Hora da entrada obrigatória.'); 
                if(!array_key_exists('quantidade', $entrada) or $entrada['quantidade'] === '' or $entrada['quantidade'] === null)
                    throw new \Exception('Quantidade da entrada obrigatória.');
            }

            // Valida a quantidade do insumo

            // Insere/atualiza os dados
            $id_entradas_array = array();
            foreach($request['entradas']  as $key => $entrada){
                if($entrada['id']){
                    $sql = 'update  pcp_insumos_entrada
                            set     id_pedido_insumo = :id_pedido_insumo,
                                    quantidade = :quantidade
                            where   id = :id ';
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->bindParam(':id_pedido_insumo', $idPedidoInsumo);
                    $stmt->bindParam(':quantidade', $entrada['quantidade']);
                    $stmt->bindParam(':id', $entrada['id']);
                    $stmt->execute();
                } else {
                    $sql = 'insert  into pcp_insumos_entrada
                            set     id_pedido_insumo = :id_pedido_insumo,
                                    quantidade = :quantidade';
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->bindParam(':id_pedido_insumo', $idPedidoInsumo);
                    $stmt->bindParam(':quantidade', $entrada['quantidade']);
                    $stmt->execute();
                }
            }
            
            // Deleta os ids de entrada que não estão no JSON (se caso possuir)
            if(count($id_entradas_array) > 0) {
                $sql = 'delete from pcp_insumos_entrada where id_pedido_insumo = :id_pedido_insumo and not id in ('.implode(',',$id_entradas_array).')';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id_pedido_insumo', $idPedidoInsumo);
                $stmt->execute();
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
}