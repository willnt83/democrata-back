<?php
class PedidosCompra{

    private $statusPedidoArray;
    private $statusInsumoArray;

    public function __construct($db){
        $this->pdo = $db;
        $this->statusPedidoArray = array('A','F');
        $this->statusInsumoArray = array('S','E','C');
        //require_once 'goods.php';
    }

    public function getPedidosCompra($filters){
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.'pc.'.$key.' = :'.$key;
                $i++;
            }
        }

        $responseData = array();

        $sql = 'select 	pc.id, pc.dthr_pedido, pc.dt_prevista, pc.chave_nf, pc.status as statusPedido, 
                        pc.id_fornecedor as idFornecedor, f.nome as nomeFornecedor, count(*) as insumos
                from	pcp_pedidos pc
                        inner join pcp_pedidos_insumos pci on pci.id_pedido = pc.id
                        inner join pcp_fornecedores f on pc.id_fornecedor = f.id
                '.$where.'
                group by pc.id
                order by pc.id, pci.id_insumo';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);
        while ($row = $stmt->fetch()) {
            $dthr_pedido = split(' ', $row->dthr_pedido);            
            $responseData[] = array(
                'id'                => (int) $row->id,
                'data_pedido'       => $dthr_pedido[0] ? $dthr_pedido[0] : null,
                'hora_pedido'       => $dthr_pedido[1] ? $dthr_pedido[1] : null,
                'data_prevista'     => $row->dt_prevista,
                'idFornecedor'      => $row->idFornecedor,
                'nomeFornecedor'    => $row->nomeFornecedor,
                'chave_nf'          => $row->chave_nf,
                'status'            => $row->statusPedido,
                'insumos'           => array()
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function getPedidosCompraInsumos($filters){
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.'pc.'.$key.' = :'.$key;
                $i++;
            }
        }

        $sql = 'select 	pc.id, pc.dthr_pedido, pc.dt_prevista, pc.chave_nf, pc.status as statusPedido,
                        pc.id_fornecedor as idFornecedor, f.nome as nomeFornecedor,
                        pci.id_insumo as idInsumo, ins.nome as nomeInsumo, ins.ins, pci.status as statusInsumo,
                        pci.quantidade, pci.quantidade_conferida, pci.dthr_recebimento, pci.local
                from	pcp_pedidos pc
                        inner join pcp_pedidos_insumos pci on pci.id_pedido = pc.id
                        inner join pcp_insumos ins on pci.id_insumo = ins.id
                        inner join pcp_fornecedores f on pc.id_fornecedor = f.id
                '.$where.'
                order by pc.id, pci.id_insumo';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $i = 0;
        $pedidoId = 0;
        $responseData = array();
        while ($row = $stmt->fetch()) {
            // Pedido
            if ($pedidoId != (int) $row->id) {
                $dthr_pedido = split(' ', $row->dthr_pedido);
                $responseData[] = array(
                    'id'                => (int) $row->id,
                    'data_pedido'       => $dthr_pedido[0] ? $dthr_pedido[0] : null,
                    'hora_pedido'       => $dthr_pedido[1] ? $dthr_pedido[1] : null,
                    'data_prevista'     => $row->dt_prevista,
                    'idFornecedor'      => $row->idFornecedor,
                    'nomeFornecedor'    => $row->nomeFornecedor,
                    'chave_nf'          => $row->chave_nf,
                    'status'            => $row->statusPedido,
                    'insumos'           => array()
                );
                $i++;
            }

            // Insumos
            $dthr_recebimento = split(' ', $row->dthr_recebimento);
            $responseData[($i-1)]['insumos'][] = array(
                'id'                    => (int) $row->idInsumo,
                'nome'                  => $row->nomeInsumo,
                'ins'                   => $row->ins,
                'quantidade'            => (float) $row->quantidade,
                'quantidade_conferida'  => (float) $row->quantidade_conferida,                                
                'data_recebimento'      => $dthr_recebimento[0] ? $dthr_recebimento[0] : null,
                'hora_recebimento'      => $dthr_recebimento[1] ? $dthr_recebimento[1] : null,
                'local'                 => $row->local,
                'status'                => $row->statusInsumo
            );

            $pedidoId = $row->id;
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function createUpdatePedidoCompra($request){
        try{
            // Validações
            if(!array_key_exists('data_pedido', $request) or $request['data_pedido'] === '' or $request['data_pedido'] === null)
                throw new \Exception('Data do pedido é obrigatório.');
            if(!array_key_exists('hora_pedido', $request) or $request['hora_pedido'] === '' or $request['hora_pedido'] === null)
                throw new \Exception('Hora do pedido é obrigatório.');             

            // Status do Pedido
            if(!array_key_exists('status', $request) or !in_array(trim(strtoupper($request['status'])),$this->statusPedidoArray))
                $request['status'] = 'A';

            // Pedido de Compra
            if($request['id']){
                // Edit
                $sql = 'update  pcp_pedidos
                        set     data_pedido = :data_pedido,
                                hora_pedido = :hora_pedido,
                                status = :status
                        where   id = :id';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id', $request['id']);
                $stmt->bindParam(':data_pedido', $request['data_pedido']);
                $stmt->bindParam(':hora_pedido', $request['hora_pedido']);
                $stmt->bindParam(':status', trim(strtoupper($request['status'])));
                $stmt->execute();
                $pedidoId = $request['id'];
                $msg = 'Pedido de compra atualizado com sucesso.';
            }
            else{
                $sql = 'insert into pcp_pedidos
                        set data_pedido = :data_pedido,
                            hora_pedido = :hora_pedido,
                            status = :status';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':data_pedido', $request['data_pedido']);
                $stmt->bindParam(':hora_pedido', $request['hora_pedido']);
                $stmt->bindParam(':status', trim(strtoupper($request['status'])));
                $stmt->execute();
                $pedidoId = $this->pdo->lastInsertId();
                $msg = 'Pedido de compra cadastrado com sucesso.';
            }

            // Deletando os Insumos
            $sql = 'delete from pcp_pedidos_insumos where id_pedido = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $pedidoId);
            $stmt->execute();

            // Inserindo os insumos
            foreach($request['insumos'] as $key => $insumo){
                // Status do Insumo
                if(!array_key_exists('status', $insumo) or !in_array(trim(strtoupper($insumo['status'])),$this->statusInsumoArray))
                    $insumo['status'] = 'S';

                $sql = 'insert into pcp_pedidos_insumos
                        set id_pedido = :id_pedido,
                            id_insumo = :id_insumo,
                            id_fornecedor = :id_fornecedor,
                            quantidade = :quantidade,
                            chave_nf = :chave_nf,
                            data_prevista_entrega = :data_prevista_entrega,
                            data_recebimento = :data_recebimento,
                            hora_recebimento = :hora_recebimento,
                            local = :local,
                            status = :status';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id_pedido', $pedidoId);
                $stmt->bindParam(':id_insumo', $insumo['id']);
                $stmt->bindParam(':id_fornecedor', $insumo['id_fornecedor']);
                $stmt->bindParam(':quantidade', $insumo['quantidade']);
                $stmt->bindParam(':chave_nf', $insumo['chave_nf']);
                $stmt->bindParam(':data_prevista_entrega', $insumo['data_prevista_entrega']);
                $stmt->bindParam(':data_recebimento', $insumo['data_recebimento']);
                $stmt->bindParam(':hora_recebimento', $insumo['hora_recebimento']);
                $stmt->bindParam(':local', $insumo['local']);
                $stmt->bindParam(':status', $insumo['status']);
                $stmt->execute();
            }

            // Reponse
            return json_encode(array(
                'success' => true,
                'msg' => $msg
            ));
        } catch(\Exception $e) {
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    public function deletePedidoCompra($filters){
        try{
            // Deletando os Insumos
            $sql = 'delete from pcp_pedidos_insumos where id_pedido = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $filters['id']);
            $stmt->execute();
            
            // Deletando o Pedido de Compras
            $sql = 'delete from pcp_pedidos where id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $filters['id']); 
            $stmt->execute();

            return json_encode(array(
                'success' => true,
                'msg' => 'Pedido de compra removido com sucesso.'
            ));
        }
        catch(PDOException $e){
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }
}