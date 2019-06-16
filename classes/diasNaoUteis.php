<?php

class DiasNaoUteis{
    public function __construct($db){
        $this->pdo = $db;
        //require_once 'goods.php';
    }

    public function getDiasNaoUteis($filters){
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.$key.' >= :'.$key;
                $i++;
            }
        }

        $sql = '
            select * from pcp_dias_nao_uteis
            '.$where.'
            order by data;
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while ($row = $stmt->fetch()) {
            $responseData[] = array(
                'id' => (int)$row->id,
                'data' => $row->data,
                'nome' => $row->nome,
                'ativo' => $row->ativo
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function createUpdateDiaNaoUtil($request){
        try{
            // Validações
            if(!array_key_exists('nome', $request) or $request['nome'] === '' or $request['nome'] === null)
                throw new \Exception('Campo Nome é obrigatório.');
            if(!array_key_exists('data', $request) or $request['data'] === '' or $request['data'] === null)
                throw new \Exception('Campo Data é obrigatório.');
            if(!array_key_exists('ativo', $request) or $request['ativo'] === '' or $request['ativo'] === null)
                throw new \Exception('Campo Ativo é obrigatório.');
            
            // Verificação se a dia não útil informado já existe na base
            $sql = '
                select id from pcp_dias_nao_uteis
                where data = :data;
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':data', $request['data']);
            $stmt->execute();
            if(count($stmt->fetchAll()) > 0){
                throw new \Exception('Dia não útil já cadastrado.');
            }

            if($request['id']){
                // Edit
                $sql = '
                    update pcp_dias_nao_uteis
                    set
                        nome = :nome,
                        data = :data,
                        ativo = :ativo
                    where id = :id;
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id', $request['id']);
                $stmt->bindParam(':nome', $request['nome']);
                $stmt->bindParam(':data', $request['data']);
                $stmt->bindParam(':ativo', $request['ativo']);
                $stmt->execute();

                $msg = 'Dia não Útil atualizado com sucesso.';
            }
            else{
                $sql = '
                    insert into pcp_dias_nao_uteis
                    set
                        nome = :nome,
                        data = :data,
                        ativo = :ativo
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':nome', $request['nome']);
                $stmt->bindParam(':data', $request['data']);
                $stmt->bindParam(':ativo', $request['ativo']);
                $stmt->execute();

                $msg = 'Dia não Útil cadastrado com sucesso.';
            }

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

    public function deleteDiaNaoUtil($filters){
        try{
            $sql = '
                delete from pcp_dias_nao_uteis
                where id = :id
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $filters['id']); 
            $stmt->execute();

            return json_encode(array(
                'success' => true,
                'msg' => 'Dia não Útil removido com sucesso.'
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