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
}