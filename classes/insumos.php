<?php

class Insumos{
    public function __construct($db){
        $this->pdo = $db;
    }

    public function getInsumos($filters){
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.'insumos.'.$key.' = :'.$key;
                $i++;
            }
        }

        $sql = '
            select  insumos.*, unidade.id idUnidadeMedidas, unidade.nome nomeUnidadeMedidas
            from    pcp_insumos as insumos
                    join pcp_unidades_medidas as unidade on insumos.id_unidade_medidas = unidade.id
            '.$where.'
            order by id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while ($row = $stmt->fetch()) {
            $responseData[] = array(
                'id' => (int)$row->id,
                'nome' => $row->nome,
                'ativo' => $row->ativo,
                'idUnidadeMedidas' => $row->idUnidadeMedidas,
                'nomeUnidadeMedidas' => $row->nomeUnidadeMedidas,
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function createUpdateInsumo($request){
        try{
            // Validações
            if(!array_key_exists('nome', $request) or $request['nome'] === '' or $request['nome'] === null)
                throw new \Exception('Campo Nome é obrigatório.');
            if(!array_key_exists('ativo', $request) or $request['ativo'] === '' or $request['ativo'] === null)
                throw new \Exception('Campo Ativo é obrigatório.');
            if(!array_key_exists('unidademedidas', $request) or $request['unidademedidas'] === '' or $request['unidademedidas'] === null)
                throw new \Exception('Campo Unidade de Medidas é obrigatório.');    

            if($request['id']){
                // Edit
                $sql = '
                    update pcp_insumos
                    set
                        nome = :nome,
                        ativo = :ativo,
                        id_unidade_medidas = :unidademedidas
                    where id = :id';
                $msg = 'Insumo atualizado com sucesso.';
            }
            else{
                $sql = '
                    insert into pcp_insumos
                    set
                        nome = :nome,
                        ativo = :ativo,
                        id_unidade_medidas = :unidademedidas';
                $msg = 'Insumo cadastrado com sucesso.';
            }

            // Executing the statement
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':nome', $request['nome']);
            $stmt->bindParam(':ativo', $request['ativo']);
            $stmt->bindParam(':unidademedidas', $request['unidademedidas']);
            $stmt->execute();

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

    public function deleteInsumo($filters){
        try{
            $sql = 'delete from pcp_insumos where id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $filters['id']); 
            $stmt->execute();

            return json_encode(array(
                'success' => true,
                'msg' => 'Insumo removido com sucesso.'
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