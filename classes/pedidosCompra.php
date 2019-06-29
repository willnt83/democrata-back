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
                order by pc.id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);
        while ($row = $stmt->fetch()) {
            $dthr_pedido = explode(' ', $row->dthr_pedido);            
            $responseData[] = array(
                'id'                => (int) $row->id,
                'data_pedido'       => (isset($dthr_pedido[0]) and $dthr_pedido[0]) ? $dthr_pedido[0] : null,
                'hora_pedido'       => (isset($dthr_pedido[1]) and $dthr_pedido[1]) ? $dthr_pedido[1] : null,
                'data_prevista'     => $row->dt_prevista,
                'idFornecedor'      => (int) $row->idFornecedor,
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
                        pci.id_insumo as idInsumo, ins.nome as nomeInsumo, ins.ins, um.nome as nomeUnidadeMedida, 
                        pci.status as statusInsumo, pci.quantidade, pci.quantidade_conferida, pci.dthr_recebimento, pci.local
                from	pcp_pedidos pc
                        inner join pcp_pedidos_insumos pci on pci.id_pedido = pc.id
                        inner join pcp_insumos ins on pci.id_insumo = ins.id
                        inner join pcp_fornecedores f on pc.id_fornecedor = f.id
                        inner join pcp_unidades_medida um on um.id = ins.id_unidade_medida
                '.$where.'
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
                    'status'            => $row->statusPedido,
                    'insumos'           => array()
                );
                $i++;
            }

            // Insumos
            $dthr_recebimento = explode(' ', $row->dthr_recebimento);
            $responseData[($i-1)]['insumos'][] = array(
                'id'                    => (int) $row->idInsumo,
                'nome'                  => $row->nomeInsumo,
                'ins'                   => $row->ins,
                'unidademedida'         => $row->nomeUnidadeMedida,
                'quantidade'            => (float) $row->quantidade,
                'quantidade_conferida'  => (float) $row->quantidade_conferida,                                
                'data_recebimento'      => (isset($dthr_recebimento[0]) and $dthr_recebimento[0]) ? $dthr_recebimento[0] : null,
                'hora_recebimento'      => (isset($dthr_recebimento[1]) and $dthr_recebimento[1]) ? $dthr_recebimento[1] : null,
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
                throw new \Exception('Hora do pedido é obrigatória.');
            if(!array_key_exists('chave_nf', $request) or $request['chave_nf'] === '' or $request['chave_nf'] === null)
                throw new \Exception('Chave da Nota Fiscal é obrigatória.');
            if(!array_key_exists('idFornecedor', $request) or $request['idFornecedor'] === '' or $request['idFornecedor'] === null)
                throw new \Exception('Fornecedor é obrigatório.');
            if(!array_key_exists('data_prevista', $request) or $request['data_prevista'] === '' or $request['data_prevista'] === null)
                throw new \Exception('Data de previsão é obrigatória.');                              

            // Valida os insumos
            foreach($request['insumos'] as $key => $insumo){
                if(!array_key_exists('idInsumo', $insumo) or $insumo['idInsumo'] === '' or $insumo['idInsumo'] === null)
                    throw new \Exception('Insumo é obrigatório.');
                if(!array_key_exists('quantidade', $insumo) or $insumo['quantidade'] === '' or $insumo['quantidade'] === null)
                    throw new \Exception('Quantidade é obrigatória.');                    
            }

            // Status do Pedido
            if(!array_key_exists('status', $request) or !in_array(trim(strtoupper($request['status'])),$this->statusPedidoArray))
                $request['status'] = 'A';

            // Pedido de Compra
            if($request['id']){
                // Edit
                $sql = 'update  pcp_pedidos
                        set     dthr_pedido = CONCAT(:data_pedido," ",:hora_pedido),
                                chave_nf = :chave_nf,
                                id_fornecedor = :id_fornecedor,
                                dt_prevista = :dt_prevista,
                                status = :status
                        where   id = :id';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id', $request['id']);
                $stmt->bindParam(':data_pedido', $request['data_pedido']);
                $stmt->bindParam(':hora_pedido', $request['hora_pedido']);
                $stmt->bindParam(':chave_nf', $request['chave_nf']);
                $stmt->bindParam(':id_fornecedor', $request['idFornecedor']);
                $stmt->bindParam(':dt_prevista', $request['data_prevista']);
                $stmt->bindParam(':status', trim(strtoupper($request['status'])));
                $stmt->execute();
                $pedidoId = $request['id'];
                $msg = 'Pedido de compra atualizado com sucesso.';
            }
            else{
                $sql = 'insert into pcp_pedidos
                        set dthr_pedido = CONCAT(:data_pedido," ",:hora_pedido),
                            chave_nf = :chave_nf,
                            id_fornecedor = :id_fornecedor,
                            dt_prevista = :dt_prevista,
                            status = :status';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':data_pedido', $request['data_pedido']);
                $stmt->bindParam(':hora_pedido', $request['hora_pedido']);
                $stmt->bindParam(':chave_nf', $request['chave_nf']);
                $stmt->bindParam(':id_fornecedor', $request['idFornecedor']);
                $stmt->bindParam(':dt_prevista', $request['data_prevista']);
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
                            quantidade = :quantidade,
                            status = :status';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id_pedido', $pedidoId);
                $stmt->bindParam(':id_insumo', $insumo['idInsumo']);
                $stmt->bindParam(':quantidade', $insumo['quantidade']);
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