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
                // Table's nickname and statuses list
                if($key === 'id') {
                    $nick = 'pc.';
                    $equalBinding = ' = :'.$key;
                } else if($key === 'status' or $key === 'statusInsumo'){
                    $nick = 'pci.';
                    $statusesArray = explode(',', $value);
                    if(count($statusesArray) > 1) {
                        $equalBinding = '';
                        unset($filters[$key]);
                        foreach($statusesArray as $key_status=>$value_status){
                            if($key_status > 0) $equalBinding .= ' or ';
                            $equalBinding .= $nick.'status = :'.$key.$key_status;
                            $filters[$key.$key_status] = $value_status;
                        }
                        $key = '';                        
                        $nick = '';
                    } else {
                        $equalBinding = ' = :'.$key;
                        $key = 'status';
                    }                    
                } else {
                    $nick = '';
                    $equalBinding = ' = :'.$key;
                }
                
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.$nick.$key.$equalBinding;
                $i++;
            }
        }

        $sql = 'select 	pc.id, pc.dthr_pedido, pc.dt_prevista, pc.chave_nf, pc.id_fornecedor as idFornecedor, f.nome as nomeFornecedor,
                        pci.id as item, pci.id_insumo as idInsumo, ins.nome as nomeInsumo, ins.ins, 
                        um.nome as nomeUnidadeMedida, pci.status as statusInsumo, pci.dthr_recebimento, 
                        pci.quantidade, ifnull(sum(entrada.entrada_quantidade),0) as quantidade_conferida
                from	pcp_pedidos pc
                        inner join pcp_pedidos_insumos pci on pci.id_pedido = pc.id
                        inner join pcp_insumos ins on pci.id_insumo = ins.id
                        inner join pcp_fornecedores f on pc.id_fornecedor = f.id
                        left join pcp_unidades_medida um on um.id = ins.id_unidade_medida
                        left join pcp_insumos_entrada entrada on pci.id = entrada.id_pedido_insumo
                '.$where.'
                group by pci.id
                order by pc.id, pci.id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $i = 0;
        $pedidoId = 0;
        $responseData = array();
        while ($row = $stmt->fetch()) {
            // Pedido
            if ($pedidoId != (int) $row->id) {
                $dthr_pedido = explode(' ', $row->dthr_pedido);
                $responseData[] = array(
                    'id'                => (int) $row->id,
                    'data_pedido'       => (isset($dthr_pedido[0]) and $dthr_pedido[0])  ? $dthr_pedido[0] : null,
                    'hora_pedido'       => (isset($dthr_pedido[1]) and $dthr_pedido[1])  ? $dthr_pedido[1] : null,
                    'data_prevista'     => $row->dt_prevista,
                    'idFornecedor'      => (int) $row->idFornecedor,
                    'nomeFornecedor'    => $row->nomeFornecedor,
                    'chave_nf'          => $row->chave_nf,
                    'insumos'           => array()
                );
                $i++;
            }

            // Insumos
            $dthr_recebimento = explode(' ', $row->dthr_recebimento);
            $responseData[($i-1)]['insumos'][] = array(
                'item'                  => (int) $row->item,
                'id'                    => (int) $row->idInsumo,
                'nome'                  => $row->nomeInsumo,
                'ins'                   => $row->ins,
                'unidademedida'         => $row->nomeUnidadeMedida,
                'quantidade'            => (float) $row->quantidade,
                'quantidade_conferida'  => (float) $row->quantidade_conferida,                                
                'data_recebimento'      => (isset($dthr_recebimento[0]) and $dthr_recebimento[0]) ? $dthr_recebimento[0] : null,
                'hora_recebimento'      => (isset($dthr_recebimento[1]) and $dthr_recebimento[1]) ? $dthr_recebimento[1] : null,
                'statusInsumo'          => $row->statusInsumo
            );

            $pedidoId = $row->id;
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }
}