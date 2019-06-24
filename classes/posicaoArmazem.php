<?php

class PosicaoArmazem{
    public function __construct($db){
        $this->pdo = $db;
    }

    public function getPosicaoArmazens($filters){
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.'posicao.'.$key.' = :'.$key;
                $i++;
            }
        }

        $sql = '
            select  posicao.id, posicao.posicao, posicao.ativo, 
                    almoxarifado.id idAlmoxarifado, almoxarifado.nome nomeAlmoxarifado
            from    pcp_posicao_armazem as posicao
                    join pcp_almoxarifado as almoxarifado on posicao.id_almoxarifado = almoxarifado.id
            '.$where.'
            order by id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while ($row = $stmt->fetch()) {
            $row->id = (int) $row->id;
            $row->idAlmoxarifado = (int) $row->idAlmoxarifado;
            $responseData[] = $row;
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function createUpdatePosicaoArmazem($request){
        try{
            // Validações
            if(!array_key_exists('posicao', $request) or $request['posicao'] === '' or $request['posicao'] === null)
                throw new \Exception('Campo Posição é obrigatório.');
            if(!array_key_exists('ativo', $request) or $request['ativo'] === '' or $request['ativo'] === null)
                throw new \Exception('Campo Ativo é obrigatório.');
            if(!array_key_exists('almoxarifado', $request) or $request['almoxarifado'] === '' or $request['almoxarifado'] === null)
                throw new \Exception('Campo Almoxarifado é obrigatório.');

            if($request['id']){
                // Edit
                $sql = '
                    update pcp_posicao_armazem
                    set
                        posicao = :posicao,
                        ativo = :ativo,
                        id_almoxarifado = :almoxarifado
                    where id = :id';
                $msg = 'Posição do armazém atualizado com sucesso.';
            }
            else{
                $sql = '
                    insert into pcp_posicao_armazem
                    set
                        posicao = :posicao,
                        ativo = :ativo,
                        id_almoxarifado = :almoxarifado';
                $msg = 'Posição do armazém cadastrado com sucesso.';
            }

            // Executing the statement
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':posicao', $request['posicao']);
            $stmt->bindParam(':ativo', $request['ativo']);
            $stmt->bindParam(':almoxarifado', $request['almoxarifado']); 
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

    public function deletePosicaoArmazem($filters){
        try{
            $sql = 'delete from pcp_posicao_armazem where id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $filters['id']); 
            $stmt->execute();

            return json_encode(array(
                'success' => true,
                'msg' => 'Posição do armazém removido com sucesso.'
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