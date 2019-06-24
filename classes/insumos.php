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
            select  insumos.id, insumos.nome, insumos.ativo, insumos.categoria, 
                    unidadesMedida.id idUnidadeMedida, unidadesMedida.nome nomeUnidadeMedida,
                    unidade.id idUnidade, unidade.nome nomeUnidade
            from    pcp_insumos as insumos
                    join pcp_unidades_medida as unidadesMedida on insumos.id_unidade_medida = unidadesMedida.id
                    join pcp_unidades as unidade on insumos.id_unidade = unidade.id
            '.$where.'
            order by id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while ($row = $stmt->fetch()) {
            $row->id = (int) $row->id;
            $row->idUnidade = (int) $row->idUnidade;
            $row->idUnidadeMedida = (int) $row->idUnidadeMedida;
            $responseData[] = $row;
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
            if(!array_key_exists('categoria', $request) or $request['categoria'] === '' or $request['categoria'] === null)
                throw new \Exception('Campo Categoria é obrigatório.');                
            if(!array_key_exists('unidade', $request) or $request['unidade'] === '' or $request['unidade'] === null)
                throw new \Exception('Campo Unidade é obrigatório.');                  
            if(!array_key_exists('unidademedida', $request) or $request['unidademedida'] === '' or $request['unidademedida'] === null)
                throw new \Exception('Campo Unidade de Medida é obrigatório.');    

            if($request['id']){
                // Edit
                $sql = '
                    update pcp_insumos
                    set
                        nome = :nome,
                        ativo = :ativo,
                        categoria =:categoria,
                        id_unidade_medida = :unidademedida,
                        id_unidade = :unidade
                    where id = :id';
                $msg = 'Insumo atualizado com sucesso.';
            }
            else{
                $sql = '
                    insert into pcp_insumos
                    set
                        nome = :nome,
                        ativo = :ativo,
                        categoria = :categoria,
                        id_unidade_medida = :unidademedida,
                        id_unidade = :unidade';
                $msg = 'Insumo cadastrado com sucesso.';
            }

            // Executing the statement
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':nome', $request['nome']);
            $stmt->bindParam(':ativo', $request['ativo']);
            $stmt->bindParam(':categoria', $request['categoria']);   
            $stmt->bindParam(':unidade', $request['unidade']);         
            $stmt->bindParam(':unidademedida', $request['unidademedida']);
            if($request['id']) $stmt->bindParam(':id', $request['id']);
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